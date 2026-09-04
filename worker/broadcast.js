import express from 'express';
import { makeWASocket, useMultiFileAuthState, DisconnectReason } from '@whiskeysockets/baileys';
import pino from 'pino';
import QRCode from 'qrcode-terminal';
import dotenv from 'dotenv';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

dotenv.config();

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const app = express();
app.use(express.json());

const logger = pino({ level: process.env.LOG_LEVEL || 'info' });

const SESSION_PATH = process.env.BAILEYS_SESSION_PATH || path.join(__dirname, '..', 'storage', 'app', 'wa-sessions');
const PORT = parseInt(process.env.BROADCAST_WORKER_PORT || '3001', 10);
const DELAY_MIN = parseInt(process.env.BAILEYS_DELAY_MIN || '3', 10);
const DELAY_MAX = parseInt(process.env.BAILEYS_DELAY_MAX || '10', 10);
const DAILY_LIMIT = parseInt(process.env.BROADCAST_DAILY_LIMIT || '50', 10);
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const WORKER_TOKEN = process.env.BROADCAST_WORKER_TOKEN || '';

let sock = null;
let isReady = false;

async function connectToWhatsApp() {
    const authPath = SESSION_PATH;

    if (!fs.existsSync(authPath)) {
        fs.mkdirSync(authPath, { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(authPath);

    sock = makeWASocket({
        auth: state,
        logger: pino({ level: process.env.BAILEYS_LOG_LEVEL || 'error' }),
        browser: ['SewaJas Broadcast Worker', 'Chrome', '1.0'],
    });

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            QRCode.generate(qr, { small: true });
            logger.info('QR Code received. Scan with WhatsApp.');
        }

        if (connection === 'close') {
            const reason = lastDisconnect?.error?.output?.statusCode;
            const isLoggedOut = reason === DisconnectReason.loggedOut;

            isReady = false;
            logger.warn('Connection closed. Reason:', reason, 'Should reconnect:', !isLoggedOut);

            if (isLoggedOut) {
                logger.warn('Logged out. Clearing session and stopping. Please restart worker to scan new QR.');
                return;
            }

            await new Promise(resolve => setTimeout(resolve, 3000));
            await connectToWhatsApp();
        } else if (connection === 'open') {
            logger.info('WhatsApp connection opened successfully');
            isReady = true;
        }
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', async (m) => {
        if (m.type !== 'notify') return;

        for (const msg of m.messages) {
            if (!msg.key || !msg.key.id) continue;

            const remoteJid = msg.key.remoteJid || '';
            const phone = remoteJid.split('@')[0];
            const messageId = msg.key.id;

            if (msg.message?.conversation || msg.message?.extendedTextMessage?.text) {
                const text = msg.message.conversation || msg.message.extendedTextMessage?.text || '';
                const trimmed = text.trim().toUpperCase();
                if (trimmed === 'STOP') {
                    logger.info('STOP keyword received', { phone });

                    try {
                        await fetch(`${LARAVEL_URL}/api/worker/opt-out`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Worker-Token': WORKER_TOKEN,
                            },
                            body: JSON.stringify({ phone, type: 'customer' }),
                        });
                    } catch (err) {
                        logger.error('Failed to call opt-out endpoint', { error: err.message });
                    }
                }
            }

            if (msg.status === 'played' || msg.status === 'read') {
                logger.info('Message read', { messageId, phone });
                await updateLogStatus(messageId, 'read', phone);
            } else if (msg.status === 'delivered') {
                logger.info('Message delivered', { messageId, phone });
                await updateLogStatus(messageId, 'delivered', phone);
            } else if (msg.status === 'failed' || msg.status === 'sender_invalid') {
                const errorText = msg.status === 'sender_invalid'
                    ? 'Invalid sender/number not registered on WhatsApp'
                    : `Message failed with status: ${msg.status}`;
                logger.error('Message failed', { messageId, phone, error: errorText });
                await updateLogStatus(messageId, 'failed', phone, errorText);
            }
        }
    });

    sock.ev.on('messages.update', async (updates) => {
        for (const update of updates) {
            const messageId = update?.key?.id;
            const remoteJid = update?.key?.remoteJid || '';
            const phone = remoteJid.split('@')[0];
            const statusNum = update?.status;

            const statusLabel = statusNum === 4 ? 'READ' :
                                statusNum === 3 ? 'DELIVERY_ACK' :
                                statusNum === 2 ? 'SERVER_ACK' :
                                statusNum === 1 ? 'PENDING' :
                                statusNum === 0 ? 'ERROR' : `UNKNOWN(${statusNum})`;

            logger.info('RAW messages.update event', {
                messageId,
                phone,
                status_num: statusNum,
                status_label: statusLabel,
                full_update: JSON.stringify(update),
            });

            if (!messageId) continue;

            if (statusNum === 4) {
                await updateLogStatus(messageId, 'read', phone);
            } else if (statusNum === 3) {
                await updateLogStatus(messageId, 'delivered', phone);
            } else if (statusNum === 2) {
                await updateLogStatus(messageId, 'submitted', phone);
            } else if (statusNum === 0) {
                await updateLogStatus(messageId, 'failed', phone, 'Message update error');
            }
        }
    });
}

async function updateLogStatus(messageId, status, phone, errorMessage = null) {
    try {
        const response = await fetch(`${LARAVEL_URL}/api/worker/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Worker-Token': WORKER_TOKEN,
            },
            body: JSON.stringify({
                provider_message_id: messageId,
                phone,
                status,
                error_message: errorMessage,
            }),
        });

        const responseText = await response.text();
        logger.info('update-status response', {
            messageId,
            status,
            phone,
            httpStatus: response.status,
            body: responseText,
        });
    } catch (err) {
        logger.error('Failed to update log status', { error: err.message });
    }
}

function getRandomDelay() {
    const delay = Math.floor(Math.random() * (DELAY_MAX - DELAY_MIN + 1)) + DELAY_MIN;
    return delay * 1000;
}

function formatPhoneNumber(phone) {
    let formatted = phone.replace(/\D/g, '');

    if (formatted.startsWith('0')) {
        formatted = '62' + formatted.substring(1);
    }

    if (!formatted.startsWith('62')) {
        formatted = '62' + formatted;
    }

    return formatted + '@s.whatsapp.net';
}

async function sendMessage(phone, message) {
    if (!sock || !isReady) {
        throw new Error('WhatsApp not connected');
    }

    const jid = formatPhoneNumber(phone);
    const result = await sock.sendMessage(jid, { text: message });

    console.log('RAW sendMessage result:', JSON.stringify(result, null, 2));
    logger.info('RAW sendMessage result', {
        phone,
        result_keys: result?.key ? Object.keys(result.key) : 'no key',
        result_key_id: result?.key?.id,
        result_status: result?.key?.status,
    });

    return {
        message_id: result?.key?.id || null,
        status: 'submitted',
    };
}

app.post('/api/broadcast/send', async (req, res) => {
    try {
        const { phone, message, campaign_id, log_id, provider } = req.body;

        if (!phone || !message) {
            return res.status(400).json({
                status: 'failed',
                error: 'Phone and message are required',
            });
        }

        if (!isReady) {
            return res.status(503).json({
                status: 'failed',
                error: 'WhatsApp not connected yet',
            });
        }

        const limitRes = await fetch(`${LARAVEL_URL}/api/worker/daily-limit?token=${WORKER_TOKEN}`);
        if (limitRes.ok) {
            const limitData = await limitRes.json();
            if (limitData.remaining <= 0) {
                return res.status(429).json({
                    status: 'failed',
                    error: 'Daily broadcast limit reached',
                });
            }
        }

        await new Promise(resolve => setTimeout(resolve, getRandomDelay()));

        const result = await sendMessage(phone, message);

        logger.info('Message submitted', {
            campaign_id,
            log_id,
            phone,
            message_id: result.message_id,
        });

        return res.json({
            status: result.status,
            message_id: result.message_id,
        });
    } catch (error) {
        logger.error('Failed to submit message', {
            error: error.message,
            phone: req.body.phone,
        });

        let errorMessage = error.message;
        if (error.message.includes('EADDRINUSE') || error.message.includes('EACCES')) {
            errorMessage = 'Worker busy or port conflict';
        } else if (error.message.includes('ENOTFOUND') || error.message.includes('ECONNREFUSED')) {
            errorMessage = 'Cannot reach WhatsApp servers';
        } else if (error.message.includes('Not connected')) {
            errorMessage = 'WhatsApp session not connected';
        }

        return res.json({
            status: 'failed',
            error: errorMessage,
        });
    }
});

app.get('/api/health', (req, res) => {
    res.json({
        status: 'ok',
        whatsapp_connected: isReady,
    });
});

async function startServer() {
    try {
        await connectToWhatsApp();
    } catch (err) {
        console.error('FATAL: connectToWhatsApp failed:', err);
        throw err;
    }

    app.listen(PORT, () => {
        logger.info(`Broadcast worker listening on port ${PORT}`);
        logger.info(`Session path: ${SESSION_PATH}`);
    });
}

startServer().catch((error) => {
    console.error('FATAL: Failed to start worker:', error);
    logger.error('Failed to start worker:', error);
    process.exit(1);
});

process.on('SIGINT', async () => {
    logger.info('Shutting down...');
    if (sock) {
        await sock.end();
    }
    process.exit(0);
});

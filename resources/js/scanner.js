// ════════════════════════════════════════════════════════════════
// QR SCANNER - CLEAN VERSION
// Using Html5Qrcode + navigator.mediaDevices.enumerateDevices()
// ════════════════════════════════════════════════════════════════

console.log('[QR Scanner] Module loading...');

// Global state
// Global state
let scanner = null;
let isScanning = false;
let selectedCameraId = null;
let availableCameras = [];
let scanHistory = [];

let qrTrackingAnimation = null;
let qrTrackingCanvas = null;
let qrTrackingContext = null;
let qrAlreadyHandled = false;

function loadScanHistory() {
  try {
      const savedHistory = localStorage.getItem('qr_history');

      if (savedHistory) {
          scanHistory = JSON.parse(savedHistory);
      }

      console.log('DEBUG loadScanHistory - hasil:', scanHistory);
      log('Scan history loaded:', scanHistory);
  } catch (err) {
      logWarn('Could not load scan history:', err);
      scanHistory = [];
  }
}
async function renderScanHistory() {
  const container = document.getElementById('scan-history-container');

  if (!container) {
      logWarn('Scan history container not found');
      return;
  }

  try {
      const response = await fetch('/qr-scan-history', {
          method: 'GET',
          headers: {
              'Accept': 'application/json'
          }
      });

      if (!response.ok) {
          throw new Error(`HTTP error: ${response.status}`);
      }

      const histories = await response.json();

      if (!histories || histories.length === 0) {
          container.innerHTML = `
              <div class="text-center py-6">
                  <p class="text-sm font-medium" style="color: var(--text-soft)">
                      Tidak ada riwayat
                  </p>
              </div>
          `;
          return;
      }

      container.innerHTML = histories.map((item) => {

          // Ambil ID rental dari URL QR
          let rentalId = null;

          try {
              const url = new URL(item.content);
              const match = url.pathname.match(/\/rentals\/(\d+)/);

              if (match) {
                  rentalId = match[1];
              }
          } catch (error) {
              console.warn('QR bukan URL rental:', item.content);
          }

          const title = rentalId
              ? `Rental #${rentalId}`
              : 'QR Code';

          const timestamp = item.scanned_at
              ? new Date(item.scanned_at).toLocaleString('id-ID')
              : '';

          return `
              <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 hover:bg-white transition">

                  <div class="flex items-start justify-between gap-3">

                      <div class="min-w-0 flex items-start gap-3">

                          <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center">
                              <svg class="w-5 h-5 text-blue-600"
                                  fill="none"
                                  stroke="currentColor"
                                  viewBox="0 0 24 24">
                                  <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                  </path>
                              </svg>
                          </div>

                          <div class="min-w-0">

                              <p class="text-sm font-semibold text-slate-700">
                                  ${title}
                              </p>

                              <p class="text-xs text-slate-500 mt-1">
                                  Penyewaan
                              </p>

                              <a
                                  href="${item.content}"
                                  class="text-xs text-blue-600 hover:text-blue-800 hover:underline mt-1 inline-block"
                              >
                                  Lihat detail rental
                              </a>

                          </div>

                      </div>

                      <span class="text-xs text-slate-400 whitespace-nowrap">
                          ${timestamp}
                      </span>

                  </div>

              </div>
          `;
      }).join('');

      console.log('QR scan history loaded from database:', histories);

  } catch (err) {
      logWarn('Could not load scan history:', err);

      container.innerHTML = `
          <div class="text-center py-6">
              <p class="text-sm font-medium text-red-500">
                  Gagal memuat riwayat scan
              </p>
          </div>
      `;
  }
}

const LS_DEVICE_KEY = 'qr_scanner_selected_device_id';

// ════════════════════════════════════════════════════════════════
// LOGGING
// ════════════════════════════════════════════════════════════════

function log(msg, data) {
  const time = new Date().toLocaleTimeString();
  console.log(`[QR Scanner ${time}] ${msg}`, data || '');
}

function logError(msg, err) {
  const time = new Date().toLocaleTimeString();
  console.error(`[QR Scanner ERROR ${time}] ${msg}`, err || '');
}

function logWarn(msg, data) {
  const time = new Date().toLocaleTimeString();
  console.warn(`[QR Scanner WARN ${time}] ${msg}`, data || '');
}

// ════════════════════════════════════════════════════════════════
// BROWSER SUPPORT CHECK
// ════════════════════════════════════════════════════════════════

function checkBrowserSupport() {
  if (!navigator.mediaDevices) {
    logError('navigator.mediaDevices not available');
    return false;
  }
  if (!navigator.mediaDevices.getUserMedia) {
    logError('getUserMedia not available');
    return false;
  }
  log('✓ Browser support OK');
  return true;
}

// ════════════════════════════════════════════════════════════════
// DEVICE ENUMERATION
// ════════════════════════════════════════════════════════════════

async function enumerateVideoDevices() {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    const videoDevices = devices.filter(device => device.kind === 'videoinput');
    log(`Found ${videoDevices.length} video input device(s)`, videoDevices);
    return videoDevices;
  } catch (err) {
    logError('Error enumerating devices:', err);
    return [];
  }
}

const VIRTUAL_KEYWORDS = ['virtual', 'obs', 'snap camera', 'manycam', 'xsplit', 'camo', 'epoccam'];

function isLikelyVirtualCamera(label) {
  if (!label) return false;
  const lower = label.toLowerCase();
  return VIRTUAL_KEYWORDS.some(keyword => lower.includes(keyword));
}

function detectMobile() {
  return /Android|iPhone|iPad|iPod|webOS/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 1 && window.innerWidth < 768);
}

async function populateCameraSelector(videoDevices) {
  const selectorWrapper = document.getElementById('camera-selector-wrapper');
  const selector = document.getElementById('camera-select');
  if (!selectorWrapper || !selector) return;

  availableCameras = videoDevices;

  if (videoDevices.length === 0) {
    selectorWrapper.style.display = 'none';
    return;
  }

  selectorWrapper.style.display = 'block';
  selector.innerHTML = '';

  const savedId = localStorage.getItem(LS_DEVICE_KEY);
  let defaultDeviceId = savedId && videoDevices.some(d => d.deviceId === savedId) ? savedId : null;

  if (!defaultDeviceId) {
    if (detectMobile()) {
      const backCamera = videoDevices.find(d => {
        const label = (d.label || '').toLowerCase();
        return label.includes('back') || label.includes('rear') || label.includes('environment');
      });
      defaultDeviceId = backCamera ? backCamera.deviceId : null;
    }

    if (!defaultDeviceId) {
      const physicalCamera = videoDevices.find(d => !isLikelyVirtualCamera(d.label));
      defaultDeviceId = physicalCamera ? physicalCamera.deviceId : videoDevices[0].deviceId;
    }
  }

  selectedCameraId = defaultDeviceId;

  videoDevices.forEach((device, index) => {
    const option = document.createElement('option');
    option.value = device.deviceId;
    option.textContent = device.label || `Kamera ${index + 1}`;
    if (device.deviceId === selectedCameraId) {
      option.selected = true;
    }
    selector.appendChild(option);
  });

  if (!savedId && defaultDeviceId) {
    localStorage.setItem(LS_DEVICE_KEY, defaultDeviceId);
  }

  saveCachedCameras(videoDevices);

  // Notify Alpine.js that camera list has been updated
  window.dispatchEvent(new CustomEvent('cameras-updated', { detail: { cameras: videoDevices } }));
}

// ════════════════════════════════════════════════════════════════
// GET CAMERAS
// ════════════════════════════════════════════════════════════════

function loadCachedCameras() {
  try {
    const data = localStorage.getItem('cached_cameras');
    if (data) return JSON.parse(data);
  } catch (e) {
    logWarn('Failed to load cached cameras:', e);
  }
  return [];
}

function saveCachedCameras(devices) {
  try {
    localStorage.setItem('cached_cameras', JSON.stringify(devices));
  } catch (e) {
    logWarn('Failed to cache cameras:', e);
  }
}

async function refreshCameraList() {
  let videoDevices = await enumerateVideoDevices();

  console.log('DEBUG videoDevices:', videoDevices);

  if (videoDevices.length === 0) {
    handleError(
      'NO_CAMERA',
      'Tidak ada kamera yang terdeteksi. Pastikan kamera terhubung dan izin diberikan di browser.'
    );
    return;
  }

  // Simpan daftar kamera
  availableCameras = videoDevices;

  // Isi pilihan kamera
  await populateCameraSelector(videoDevices);

  // Otomatis pilih kamera pertama
  if (!selectedCameraId && videoDevices.length > 0) {
    selectedCameraId =
      videoDevices[0].deviceId ||
      videoDevices[0].id ||
      null;

    console.log(
      'DEBUG selectedCameraId dari refreshCameraList:',
      selectedCameraId
    );
  }

  console.log('DEBUG availableCameras:', availableCameras);
}

async function getAvailableCameras() {
  try {
    if (!window.Html5Qrcode) {
      logError('Html5Qrcode not loaded');
      return [];
    }

    const cached = loadCachedCameras();

    if (cached.length > 0) {
      availableCameras = cached;

      console.log('DEBUG cached cameras:', cached);

      await populateCameraSelector(cached);

      // Otomatis pilih kamera pertama
      if (!selectedCameraId && cached[0]) {
        selectedCameraId =
          cached[0].deviceId ||
          cached[0].id ||
          null;

        console.log('DEBUG selectedCameraId dari cache:', selectedCameraId);
      }

      return availableCameras;
    }

    await refreshCameraList();

    console.log('DEBUG availableCameras setelah refresh:', availableCameras);

    if (availableCameras.length === 0) {
      handleError(
        'NO_CAMERA',
        'Tidak ada kamera yang terdeteksi. Pastikan kamera terhubung dan izin diberikan di browser.'
      );

      return [];
    }

    // Otomatis pilih kamera pertama
    if (!selectedCameraId && availableCameras[0]) {
      selectedCameraId =
        availableCameras[0].deviceId ||
        availableCameras[0].id ||
        null;

      console.log(
        'DEBUG selectedCameraId setelah refresh:',
        selectedCameraId
      );
    }

    return availableCameras;

  } catch (err) {
    logError('Error getting cameras:', err);
    return [];
  }
}

// ════════════════════════════════════════════════════════════════
// LOCAL STORAGE HELPERS
// ════════════════════════════════════════════════════════════════

function saveSelectedCamera(deviceId) {
  try {
    localStorage.setItem(LS_DEVICE_KEY, deviceId);
  } catch (err) {
    logWarn('Could not save camera selection:', err);
  }
}

function loadSavedCamera() {
  try {
    return localStorage.getItem(LS_DEVICE_KEY);
  } catch (err) {
    logWarn('Could not load saved camera:', err);
    return null;
  }
}

// ════════════════════════════════════════════════════════════════
// INITIALIZE SCANNER
// ════════════════════════════════════════════════════════════════

async function initializeScanner() {
  try {
    log('Creating Html5Qrcode instance...');

    scanner = new window.Html5Qrcode('qr-reader', {
      formatsToSupport: [
          window.Html5QrcodeSupportedFormats.QR_CODE
      ]
  });

    log('✓ Scanner instance created');
    log('Scanner type:', typeof scanner);
    log('Scanner.start:', typeof scanner.start);
    log('Scanner.stop:', typeof scanner.stop);
    log('Scanner.clear:', typeof scanner.clear);

    if (typeof scanner.start !== 'function') {
      logError('CRITICAL: scanner.start is not a function');
      logError('scanner object:', scanner);
      return false;
    }

    setupEventListeners();
    log('✓ Event listeners registered');

    return true;

  } catch (err) {
    logError('Failed to initialize scanner:', err);
    return false;
  }
}

// ════════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ════════════════════════════════════════════════════════════════

function setupEventListeners() {
  const cameraSelect = document.getElementById('camera-select');

  // Camera selector change - still handled by scanner.js
  if (cameraSelect) {
    cameraSelect.addEventListener('change', async (e) => {
      const newDeviceId = e.target.value;
      if (!newDeviceId || newDeviceId === selectedCameraId) return;

      selectedCameraId = newDeviceId;
      saveSelectedCamera(newDeviceId);
      log('Camera selector changed to:', newDeviceId);

      if (isScanning) {
        await restartCameraWithDevice(newDeviceId);
      }
    });
    log('✓ camera-select listener added');
  }

  // Button clicks are now handled by Alpine.js @click handlers
  log('✓ Button click handlers delegated to Alpine.js');
}

// ════════════════════════════════════════════════════════════════
// START CAMERA
// ════════════════════════════════════════════════════════════════

async function startCamera() {
  log('═══════════════════════════════════════════');
  log('START CAMERA BUTTON CLICKED');
  log('═══════════════════════════════════════════');

  if (!scanner) {
      logError('Scanner not initialized');
      handleError('NOT_READY', 'Scanner not ready. Refresh page.');
      return;
  }

  if (isScanning) {
      logWarn('Already scanning');
      return;
  }

  try {
    showLoading();

    // =====================================================
    // 1. MINTA IZIN KAMERA TERLEBIH DAHULU
    // =====================================================
    const permissionStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: false
    });

    // Stream ini hanya digunakan untuk mendapatkan izin.
    // Setelah izin didapat, hentikan stream sementara.
    permissionStream.getTracks().forEach(track => track.stop());

    log('✓ Izin kamera berhasil diberikan');

    // =====================================================
    // 2. AMBIL DAFTAR KAMERA SETELAH IZIN DIBERIKAN
    // =====================================================
    let cameras = await enumerateVideoDevices();

    console.log('DEBUG CAMERAS TERBARU:', cameras);

    console.log('DEBUG CAMERA[0]:', cameras[0]);
    console.log('DEBUG CAMERA[0].deviceId:', cameras[0]?.deviceId);
    console.log('DEBUG CAMERA[0].id:', cameras[0]?.id);
    console.log('DEBUG CAMERA[0].label:', cameras[0]?.label);

    if (!cameras || cameras.length === 0) {
        hideLoading();

        handleError(
            'NO_CAMERA',
            'Tidak ada kamera yang terdeteksi. Pastikan kamera terhubung dan izin kamera diberikan.'
        );

        return;
    }

    // Simpan daftar kamera terbaru
    availableCameras = cameras;

    // Isi dropdown kamera
    await populateCameraSelector(cameras);

    // Cari kamera yang memiliki deviceId
    let camera = cameras.find(c => c.deviceId);

    if (!camera) {
        hideLoading();

        handleError(
            'NO_CAMERA',
            'Kamera terdeteksi tetapi ID kamera tidak tersedia setelah izin diberikan.'
        );

        return;
    }

    // Kalau belum ada kamera terpilih,
    // otomatis pilih kamera pertama
    if (!selectedCameraId) {
        selectedCameraId = camera.deviceId;

        log('Auto-selected camera:', camera);
        log('Auto-selected camera ID:', selectedCameraId);
    }

    if (!selectedCameraId) {
        hideLoading();

        handleError(
            'NO_CAMERA',
            'Kamera ditemukan tetapi tidak dapat dipilih.'
        );

        return;
    }

    // Simpan kamera yang dipilih
    saveSelectedCamera(selectedCameraId);

    log('Starting camera...');
    log('Using camera ID:', selectedCameraId);


// Mulai kamera
// Hitung ukuran kamera terlebih dahulu
const scannerElement = document.getElementById('qr-reader');
const scannerWidth = scannerElement.clientWidth;
const scannerHeight = scannerElement.clientHeight;

const minEdge = Math.min(scannerWidth, scannerHeight);

// 85% dari sisi terkecil kamera
const qrSize = Math.floor(minEdge * 0.85);

await scanner.start(
    selectedCameraId,
    {
        fps: 10,

        qrbox: {
            width: qrSize,
            height: qrSize
        },

        disableFlip: false
    },
    onScanSuccess,
    onScanFailure
);
      log('DEBUG: scanner.start() selesai');

      // Tunggu video benar-benar aktif
      await waitForVideoPlaying();

      isScanning = true;
      qrAlreadyHandled = false;

      log('✓✓✓ Camera started ✓✓✓');
      
      // Mulai tracking posisi QR
      setTimeout(() => {
        startQrTracking();
      }, 300);
      
      log('✓✓✓ Camera started ✓✓✓');
      
      updateStatus('active');

      hideLoading();

      showToast('Camera started', 'success');

      window.dispatchEvent(
          new CustomEvent('scanner-state-changed', {
              detail: {
                  isScanning: true
              }
          })
      );

  } catch (err) {

      logError('Error starting camera:', err);

      hideLoading();

      isScanning = false;

      updateStatus('inactive');

      handleError(
          'START_ERROR',
          `Camera error: ${err.message || err}`
      );

      window.dispatchEvent(
          new CustomEvent('scanner-state-changed', {
              detail: {
                  isScanning: false
              }
          })
      );
  }
}

async function calculateProportionalQrbox() {
  const container = document.getElementById('qr-reader');

  if (!container) {
      logWarn('Container #qr-reader not found, using fallback qrbox 250');
      return { width: 250, height: 250 };
  }

  const startTime = Date.now();

  while (container.offsetWidth === 0 || container.offsetHeight === 0) {
      if (Date.now() - startTime > 500) {
          logWarn('Container dimensions still 0 after 500ms, using fallback qrbox 250');
          return { width: 250, height: 250 };
      }

      await new Promise(r => setTimeout(r, 50));
  }

  const size = Math.floor(
      Math.min(container.offsetWidth, container.offsetHeight) * 0.95
  );

  const finalSize = Math.max(200, size);

  log(
      'DEBUG: Container size:',
      container.offsetWidth,
      'x',
      container.offsetHeight,
      '→ qrbox:',
      finalSize
  );

  return {
      width: finalSize,
      height: finalSize
  };
}

async function waitForVideoPlaying() {
  log('DEBUG: waitForVideoPlaying dipanggil');
  return new Promise((resolve) => {
    const checkVideo = () => {
      const video = document.querySelector('#qr-reader video');
      if (video) {
        log('DEBUG: video element ditemukan, readyState=' + video.readyState + ', paused=' + video.paused + ', ended=' + video.ended);
        if (video.readyState >= 2 && !video.paused && !video.ended) {
          log('DEBUG: Video is playing (readyState >= 2)');
          resolve();
          return;
        }
        // Listen for playing event
        video.addEventListener('playing', () => {
          log('DEBUG: video playing event fired');
          resolve();
        }, { once: true });
        // Also listen for loadedmetadata as fallback
        video.addEventListener('loadedmetadata', () => {
          log('DEBUG: video loadedmetadata event fired');
        }, { once: true });
      } else {
        log('DEBUG: video element BELUM ditemukan, cek lagi 100ms');
        // Video element not ready yet, check again
        setTimeout(checkVideo, 100);
      }
    };
    checkVideo();

    // Timeout fallback
    setTimeout(() => {
      logWarn('DEBUG: fallback timeout hit (5 detik), resolving anyway');
      resolve();
    }, 5000);
  });
}

// ════════════════════════════════════════════════════════════════
// STOP CAMERA
// ════════════════════════════════════════════════════════════════

async function stopCamera() {

  if (qrTrackingAnimation) {
    cancelAnimationFrame(qrTrackingAnimation);
    qrTrackingAnimation = null;
  }

  log('═══════════════════════════════════════════');
  log('STOP CAMERA BUTTON CLICKED');
  log('═══════════════════════════════════════════');

  if (!scanner || !isScanning) {
    logWarn('Scanner not running');
    return;
  }

  try {
    log('Stopping scanner...');
    await scanner.stop();

    log('Clearing scanner...');
    await scanner.clear();

    // CRITICAL: Explicitly stop all MediaStream tracks (privacy fix)
    await stopMediaStreamTracks();

    isScanning = false;
    log('✓ Scanner stopped');
    updateStatus('inactive');
    showToast('Camera stopped', 'info');
    log('DEBUG: dispatching scanner-state-changed (stop), isScanning=' + isScanning);
    window.dispatchEvent(new CustomEvent('scanner-state-changed', { detail: { isScanning: false } }));

  } catch (err) {
    logError('Error stopping camera:', err);
    // Safety net: try to stop tracks even on error
    await stopMediaStreamTracks();
    isScanning = false;
    updateStatus('inactive');
  }
}

async function stopMediaStreamTracks() {
  try {
    const video = document.querySelector('#qr-reader video');
    if (video && video.srcObject) {
      const stream = video.srcObject;
      if (stream instanceof MediaStream) {
        log('DEBUG: Stopping MediaStream tracks...');
        stream.getTracks().forEach(track => {
          log('DEBUG: Stopping track:', track.kind, track.label);
          track.stop();
        });
        video.srcObject = null;
        log('✓ MediaStream tracks stopped');
      }
    } else {
      logWarn('DEBUG: No video element or srcObject found to stop tracks');
    }
  } catch (err) {
    logError('Error stopping MediaStream tracks:', err);
  }
}

// ════════════════════════════════════════════════════════════════
// RESTART CAMERA WITH NEW DEVICE
// ════════════════════════════════════════════════════════════════

async function restartCameraWithDevice(deviceId) {
  try {
    if (scanner && isScanning) {
      await scanner.stop();
      await scanner.clear();
      // CRITICAL: Stop old MediaStream tracks before starting new device
      await stopMediaStreamTracks();
      isScanning = false;
    }

    await scanner.start(
      deviceId,
      {
          fps: 10,
          disableFlip: false,
          videoConstraints: {
              width: { ideal: 1920 },
              height: { ideal: 1080 },
              frameRate: { ideal: 30 }
          }
      },
      onScanSuccess,
      onScanFailure
  );

    isScanning = true;
    qrAlreadyHandled = false;
    
    selectedCameraId = deviceId;
    log('✓ Camera restarted with device:', deviceId);
    updateStatus('active');
    showToast('Camera switched', 'success');
  } catch (err) {
    logError('Error restarting camera:', err);
    // Safety net: try to stop tracks on error
    await stopMediaStreamTracks();
    isScanning = false;
    updateStatus('inactive');
    handleError('SWITCH_ERROR', `Switch failed: ${err.message}`);
  }
}

// ════════════════════════════════════════════════════════════════
// SWITCH CAMERA
// ════════════════════════════════════════════════════════════════

async function switchCamera() {
  log('═══════════════════════════════════════════');
  log('SWITCH CAMERA BUTTON CLICKED');
  log('═══════════════════════════════════════════');

  if (!scanner || availableCameras.length < 2) {
    logWarn('Not enough cameras or scanner not ready');
    return;
  }

  try {
    const currentIdx = availableCameras.findIndex(c => c.deviceId === selectedCameraId);
    const nextIdx = (currentIdx + 1) % availableCameras.length;
    const nextCamera = availableCameras[nextIdx];

    log('Switching to camera:', nextCamera.label);

    selectedCameraId = nextCamera.deviceId;
    saveSelectedCamera(nextCamera.deviceId);

    const cameraSelect = document.getElementById('camera-select');
    if (cameraSelect) {
      cameraSelect.value = nextCamera.deviceId;
    }

    await restartCameraWithDevice(nextCamera.deviceId);

    log('✓ Camera switched to:', nextCamera.label);
    showToast(`Switched to ${nextCamera.label}`, 'success');

  } catch (err) {
    logError('Error switching camera:', err);
    handleError('SWITCH_ERROR', `Switch failed: ${err.message}`);
  }
}

// ════════════════════════════════════════════════════════════════
// QR SCAN CALLBACKS
// ════════════════════════════════════════════════════════════════

function onScanSuccess(decodedText, decodedResult) {

  // Jangan proses QR yang sama berkali-kali
  if (qrAlreadyHandled) {
    return;
  }

  qrAlreadyHandled = true;

  log('═══════════════════════════════════════════');
  log('QR CODE DETECTED:', decodedText);

  console.log('DEBUG QR RESULT:', decodedResult);

  log('═══════════════════════════════════════════');

  handleQRCode(decodedText);
}

function updateQrOverlay(bounds) {
  const overlay = document.getElementById('qr-overlay');
  const reader = document.getElementById('qr-reader');

  if (!overlay || !reader || !bounds) return;

  const video = reader.querySelector('video');

  if (!video) return;

  const readerRect = reader.getBoundingClientRect();

  const videoWidth = video.videoWidth;
  const videoHeight = video.videoHeight;

  if (!videoWidth || !videoHeight) return;

  const scale = Math.max(
    readerRect.width / videoWidth,
    readerRect.height / videoHeight
  );

  const displayedWidth = videoWidth * scale;
  const displayedHeight = videoHeight * scale;

  const offsetX =
    (readerRect.width - displayedWidth) / 2;

  const offsetY =
    (readerRect.height - displayedHeight) / 2;

  const x = bounds.x * scale + offsetX;
  const y = bounds.y * scale + offsetY;

  const width = bounds.width * scale;
  const height = bounds.height * scale;

  overlay.style.left = `${x + width / 2}px`;
  overlay.style.top = `${y + height / 2}px`;

  overlay.style.width = `${width}px`;
  overlay.style.height = `${height}px`;

  overlay.style.transform = 'translate(-50%, -50%)';

  console.log('QR POSITION:', {
    x,
    y,
    width,
    height
  });
}

function startQrTracking() {
  const reader = document.getElementById('qr-reader');
  const video = reader?.querySelector('video');

  if (!reader || !video || typeof jsQR !== 'function') {
    console.warn('QR tracker belum siap');
    return;
  }

  // Jangan membuat tracker lebih dari satu
  if (qrTrackingAnimation) {
    cancelAnimationFrame(qrTrackingAnimation);
  }

  if (!qrTrackingCanvas) {
    qrTrackingCanvas = document.createElement('canvas');
    qrTrackingContext = qrTrackingCanvas.getContext('2d', {
      willReadFrequently: true
    });
  }

  const canvas = qrTrackingCanvas;
  const context = qrTrackingContext;

  function track() {
    // Berhenti kalau kamera sudah tidak aktif
    if (!isScanning || video.readyState < 2) {
      qrTrackingAnimation = requestAnimationFrame(track);
      return;
    }

    const width = video.videoWidth;
    const height = video.videoHeight;

    if (!width || !height) {
      qrTrackingAnimation = requestAnimationFrame(track);
      return;
    }

    // Batasi ukuran canvas supaya tracking tidak terlalu berat
    const maxWidth = 640;
    const scale = Math.min(1, maxWidth / width);

    canvas.width = Math.floor(width * scale);
    canvas.height = Math.floor(height * scale);

    context.drawImage(
      video,
      0,
      0,
      canvas.width,
      canvas.height
    );

    const imageData = context.getImageData(
      0,
      0,
      canvas.width,
      canvas.height
    );

    const code = jsQR(
      imageData.data,
      imageData.width,
      imageData.height,
      {
        inversionAttempts: 'attemptBoth'
      }
    );

    if (code && code.location) {
      updateQrTrackingOverlay(
        code.location,
        width,
        height
      );
    }

    qrTrackingAnimation = requestAnimationFrame(track);
  }

  track();

  console.log('✓ QR realtime tracking started');
}

function updateQrTrackingOverlay(location, videoWidth, videoHeight) {
  const overlay = document.getElementById('qr-overlay');
  const reader = document.getElementById('qr-reader');
  const video = reader?.querySelector('video');

  if (!overlay || !reader || !video) return;

  const readerRect = reader.getBoundingClientRect();

  const scale = Math.max(
    readerRect.width / videoWidth,
    readerRect.height / videoHeight
  );

  const displayedWidth = videoWidth * scale;
  const displayedHeight = videoHeight * scale;

  // Posisi video karena object-fit: cover
  const offsetX =
    (readerRect.width - displayedWidth) / 2;

  const offsetY =
    (readerRect.height - displayedHeight) / 2;

  // Ambil 4 sudut QR
  const points = [
    location.topLeftCorner,
    location.topRightCorner,
    location.bottomRightCorner,
    location.bottomLeftCorner
  ];

  const xs = points.map(point =>
    point.x * (videoWidth / qrTrackingCanvas.width)
  );

  const ys = points.map(point =>
    point.y * (videoHeight / qrTrackingCanvas.height)
  );

  const minX = Math.min(...xs);
  const maxX = Math.max(...xs);
  const minY = Math.min(...ys);
  const maxY = Math.max(...ys);

  const x = minX * scale + offsetX;
  const y = minY * scale + offsetY;

  const width = (maxX - minX) * scale;
  const height = (maxY - minY) * scale;

  // Sedikit padding supaya kotak tidak terlalu mepet QR
  const padding = 10;

  overlay.style.left = `${x - padding}px`;
  overlay.style.top = `${y - padding}px`;

  overlay.style.width = `${width + padding * 2}px`;
  overlay.style.height = `${height + padding * 2}px`;

  overlay.style.transform = 'none';

  overlay.style.opacity = '1';
}

function onScanFailure(error) {
  if (!error.includes('NotFound')) {
    logWarn('Scan error:', error);
  }
}

// ════════════════════════════════════════════════════════════════
// HANDLE QR CODE
// ════════════════════════════════════════════════════════════════

async function handleQRCode(qrContent) {
  try {

    // Tampilkan efek hijau terlebih dahulu
    showSuccessAnimation();

    playBeep();

    if (navigator.vibrate) {
      navigator.vibrate([100, 50, 100]);
    }

    // Beri waktu agar efek hijau terlihat
    await new Promise(resolve => setTimeout(resolve, 500));

    // Setelah efek hijau terlihat, baru matikan kamera
    if (isScanning) {
      await stopCamera();
    }

    addToHistory(qrContent);
    updateResultPanel(qrContent);

    // Redirect setelah efek selesai
    setTimeout(() => {
      redirectToQR(qrContent);
    }, 500);

  } catch (err) {
    logError('Error handling QR code:', err);
  }
}

function redirectToQR(qrContent) {
  const invoiceRegex = /^(INV|RCPT-INV)(\d{12})$/i;
  const match = qrContent.match(invoiceRegex);

  if (match) {
    const url = `/rentals/scan/${qrContent}`;
    log('Redirecting to:', url);
    window.location.href = url;
  } else if (/^\d+$/.test(qrContent)) {
    const url = `/rentals/scan/${qrContent}`;
    log('Redirecting to (scan id -> scan-result):', url);
    window.location.href = url;
  } else if (qrContent.startsWith('http')) {
    log('Redirecting to:', qrContent);
    window.location.href = qrContent;
  } else {
    logWarn('Unknown QR format:', qrContent);
    showToast('QR format not recognized', 'warning');
  }
}

// ════════════════════════════════════════════════════════════════
// UI HELPERS
// ════════════════════════════════════════════════════════════════

function updateStatus(state) {
  const statusEl = document.getElementById('camera-status');
  if (!statusEl) return;

  if (state === 'active') {
    statusEl.innerHTML = `
      <div class="flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-green-500 animate-pulse"></span>
        <span class="text-sm text-green-600">Kamera Aktif</span>
      </div>
    `;
  } else {
    statusEl.innerHTML = `
      <div class="flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
        <span class="text-sm text-red-600">Kamera Tidak Aktif</span>
      </div>
    `;
  }
}

function showLoading() {
  const loader = document.getElementById('camera-loader');
  if (loader) loader.classList.remove('hidden');
}

function hideLoading() {
  const loader = document.getElementById('camera-loader');
  if (loader) loader.classList.add('hidden');
}

function showSuccessAnimation() {
  const reader = document.getElementById('qr-reader');
  if (!reader) return;

  reader.classList.add('ring-2', 'ring-green-500', 'ring-offset-2');
  setTimeout(() => {
    reader.classList.remove('ring-2', 'ring-green-500', 'ring-offset-2');
  }, 500);
}

function playBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.frequency.value = 800;
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);

    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.2);
  } catch (err) {
    logWarn('Could not play beep:', err);
  }
}

function showToast(msg, type = 'info') {
  const toast = document.createElement('div');
  toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded text-white text-sm z-50`;

  if (type === 'error') toast.classList.add('bg-red-500');
  else if (type === 'success') toast.classList.add('bg-green-500');
  else if (type === 'warning') toast.classList.add('bg-yellow-500');
  else toast.classList.add('bg-blue-500');

  toast.textContent = msg;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.remove();
  }, 3000);
}

function handleError(code, msg) {
  logError(`[${code}] ${msg}`);
  showToast(msg, 'error');
}

function updateResultPanel(qrContent) {
  const container = document.getElementById('scan-result-container');

  if (!container) return;

  container.innerHTML = `
      <div class="text-center py-4">
          <div class="text-green-600 text-lg font-semibold mb-2">
              ✓ QR Terbaca
          </div>

          <div class="text-sm text-bark bg-cream-warm rounded p-3 break-all">
              ${qrContent}
          </div>
      </div>
  `;
}
function loadLastScanResult() {
  const container = document.getElementById('scan-result-container');

  if (!container) return;

  try {
      const savedHistory = localStorage.getItem('qr_history');

      if (!savedHistory) {
          return;
      }

      const history = JSON.parse(savedHistory);

      if (!Array.isArray(history) || history.length === 0) {
          return;
      }

      const lastScan = history[0];

      updateResultPanel(lastScan.content);

      log('Last scan result loaded:', lastScan);
  } catch (err) {
      logWarn('Could not load last scan result:', err);
  }
}

async function addToHistory(qrContent) {
  try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const response = await fetch('/qr-scan-history', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
              content: qrContent
          })
      });

      if (!response.ok) {
          throw new Error(`HTTP error: ${response.status}`);
      }

      const history = await response.json();

      log('Scan history saved:', history);

  } catch (err) {
      logWarn('Could not save history:', err);
  }
}

// ════════════════════════════════════════════════════════════════
// PAGE INITIALIZATION
// ════════════════════════════════════════════════════════════════

async function initPage() {
  log('═══════════════════════════════════════════');
  log('QR SCANNER READY');
  log('═══════════════════════════════════════════');

  loadScanHistory();
  renderScanHistory();
  loadLastScanResult();

  const qrReader = document.getElementById('qr-reader');
  if (!qrReader) {
    log('SKIP: Not on scan page');
    return;
  }

  log('✓ Scanner container found');

  
  let waitCount = 0;
  while (!window.Html5Qrcode && waitCount < 150) {
    await new Promise(r => setTimeout(r, 100));
    waitCount++;
  }

  if (!window.Html5Qrcode) {
    logError('CRITICAL: Html5Qrcode library not loaded');
    handleError('LIBRARY', 'QR library failed to load. Please refresh the page.');
    return;
  }

  log('✓ Html5Qrcode library detected');
  log('Library version:', window.Html5Qrcode.VERSION || 'unknown');

  if (!checkBrowserSupport()) {
    handleError('BROWSER', 'Browser does not support camera access');
    return;
  }

  const initialized = await initializeScanner();
  if (!initialized) {
    handleError('INIT', 'Failed to initialize scanner');
    return;
  }

  // Populate camera dropdown on page load
  await getAvailableCameras();

  log('✓ Scanner initialized and ready');
  log('════════════════════════════════════════════');
}

// Auto-initialize on page load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPage);
} else {
  initPage();
}

// Cleanup on page unload
window.addEventListener('beforeunload', async () => {
  if (scanner && isScanning) {
    try {
      await scanner.stop();
      await scanner.clear();
      await stopMediaStreamTracks();
    } catch (err) {
      console.error('Error cleaning up scanner:', err);
    }
  }
});

// Export public API
window.QRScanner = {
  start: startCamera,
  stop: stopCamera,
  switch: switchCamera,
  getAvailableCameras: getAvailableCameras,
  getScanner: () => scanner,
  getStatus: () => ({
    isScanning,
    scanner: scanner ? 'initialized' : 'not initialized',
    cameras: availableCameras.length
  })
};

log('════════════════════════════════════════════');
log('QR Scanner module loaded');
log('Public API available at window.QRScanner');
log('════════════════════════════════════════════');

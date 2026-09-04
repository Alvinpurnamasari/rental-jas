@extends('Layouts.app')

@section('title', 'Buat Broadcast Campaign - SewaJas')
@section('page-title', 'Buat Broadcast Campaign')
@section('subtitle', 'Buat campaign broadcast multi-channel')

@section('content')
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card-sm lg:p-7">
        <form method="POST" action="{{ route('broadcast-campaigns.store') }}" class="space-y-5" id="campaignForm">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Nama Campaign</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" maxlength="120" placeholder="Contoh: Promo Akhir Pekan" required>
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Channel</label>
                <div class="flex flex-wrap gap-3">
                    @foreach($channels as $channel)
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 transition hover:border-blue-200">
                            <input type="radio" name="channel" value="{{ $channel['value'] }}" class="rounded border-slate-300" @checked(old('channel') === $channel['value'])>
                            <span class="text-sm font-semibold text-slate-700">{{ $channel['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('channel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Tipe Penerima</label>
                    <select name="recipient_type" id="recipient_type" class="form-input" required disabled>
                        <option value="">Pilih channel terlebih dahulu</option>
                        <option value="user" data-channel="in_app">User / Sales</option>
                        <option value="customer" data-channel="whatsapp">Customer</option>
                    </select>
                    @error('recipient_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <input type="hidden" name="recipient_type" id="recipient_type_hidden" value="{{ old('recipient_type') }}">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Target</label>
                    <select name="target_type" id="target_type" class="form-input" required>
                        <option value="">Pilih target</option>
                        @foreach($targetTypeOptions as $option)
                            @php
                                $channelRestriction = match($option['value']) {
                                    'sales' => 'in_app',
                                    'rental_status', 'product', 'category' => 'whatsapp',
                                    default => null,
                                };
                            @endphp
                            <option value="{{ $option['value'] }}" @if($channelRestriction) data-channel="{{ $channelRestriction }}" @endif @selected(old('target_type') === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    @error('target_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div id="filterContainer" class="hidden space-y-4">
                <div id="branchFilter" class="hidden">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Cabang</label>
                    <select name="target_filters[branch_id]" class="form-input">
                        <option value="">Semua cabang</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('target_filters.branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="customerFilter" class="hidden">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Pilih Pelanggan</label>
                    <select name="recipient_ids[]" class="form-input" multiple size="8">
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(in_array($customer->id, old('recipient_ids', [])))>{{ $customer->name }} ({{ $customer->phone }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Tahan Ctrl untuk pilih multiple.</p>
                </div>

                <div id="userFilter" class="hidden">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Pilih User</label>
                    <select name="recipient_ids[]" class="form-input" multiple size="8">
                        @foreach($users as $userItem)
                            <option value="{{ $userItem->id }}" @selected(in_array($userItem->id, old('recipient_ids', [])))>{{ $userItem->name }} ({{ $userItem->role }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Tahan Ctrl untuk pilih multiple.</p>
                </div>
            </div>

            <div id="whatsappFields">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Provider WhatsApp</label>
                <select name="provider" class="form-input">
                    <option value="">Pilih provider</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->provider_key }}" @selected(old('provider') === $provider->provider_key)>{{ $provider->label }} @if($provider->is_default) (Default) @endif</option>
                    @endforeach
                </select>
                    @error('provider') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Template Pesan</label>
                    <select name="template_id" id="template_id" class="form-input">
                        <option value="">Pilih template (opsional)</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" @selected(old('template_id') == $template->id)>{{ $template->name }} ({{ $template->category }})</option>
                        @endforeach
                    </select>
                    @error('template_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div id="templatePreview" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-2 text-xs font-semibold text-slate-500">Preview Template:</p>
                    <p id="templateContent" class="text-sm text-slate-700"></p>
                    <p id="templateVariables" class="mt-2 text-xs text-slate-500"></p>
                </div>
            </div>

            <div id="customMessageContainer">
                <label class="mb-2 block text-sm font-bold text-slate-700">Pesan Custom</label>
                <textarea name="custom_message_single" rows="5" class="form-input" placeholder="Tulis pesan custom (jika tidak menggunakan template)">{{ old('custom_message_single') }}</textarea>
                @error('custom_message_single') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div id="variantContainer" class="hidden">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-bold text-slate-700">Variasi Pesan (WhatsApp)</label>
                    <button type="button" id="addVariantBtn" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        + Tambah Variasi
                    </button>
                </div>
                <div id="variantsList" class="space-y-3">
                </div>
                <p class="mt-2 text-xs text-slate-500">Variabel yang tersedia: <code class="bg-slate-100 px-1 rounded">@{{ name }}</code> <code class="bg-slate-100 px-1 rounded">@{{ phone }}</code> <code class="bg-slate-100 px-1 rounded">@{{ sender_name }}</code></p>
                @error('custom_message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Jeda Antar Pesan (WhatsApp)</label>
                <select name="delay_seconds" class="form-input">
                    <option value="0" {{ old('delay_seconds', '30') == '0' ? 'selected' : '' }}>Tanpa jeda</option>
                    <option value="30" {{ old('delay_seconds', '30') == '30' ? 'selected' : '' }}>30 detik</option>
                    <option value="60" {{ old('delay_seconds', '30') == '60' ? 'selected' : '' }}>1 menit</option>
                    <option value="300" {{ old('delay_seconds', '30') == '300' ? 'selected' : '' }}>5 menit</option>
                    <option value="custom" {{ old('delay_seconds') == 'custom' ? 'selected' : '' }}>Custom...</option>
                </select>
                <div id="customDelayContainer" class="mt-2 {{ old('delay_seconds') == 'custom' ? '' : 'hidden' }}">
                    <input type="number" name="delay_seconds_custom" value="{{ old('delay_seconds_custom') }}" min="0" max="3600" class="form-input" placeholder="Masukkan jeda dalam detik (0-3600)">
                    @error('delay_seconds_custom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @error('delay_seconds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">Jeda ini diterapkan per pesan untuk menghindari deteksi spam WhatsApp.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold text-slate-700">Jadwalkan Pengiriman (Opsional)</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" class="form-input">
                @error('scheduled_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('broadcast-campaigns.index') }}" class="btn-ghost px-4 py-2">Batal</a>
                <button type="submit" class="btn-primary px-6 py-2">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Buat Campaign
                </button>
            </div>
        </form>
    </section>

    <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card-sm lg:p-6">
        <h3 class="mb-4 text-sm font-extrabold text-slate-900">Panduan</h3>
        <ul class="space-y-3 text-sm text-slate-600">
            <li class="flex gap-2">
                <i data-lucide="info" class="h-4 w-4 shrink-0 text-blue-600"></i>
                <span>Pilih tipe penerima dahulu, lalu pilih target yang sesuai.</span>
            </li>
            <li class="flex gap-2">
                <i data-lucide="info" class="h-4 w-4 shrink-0 text-blue-600"></i>
                <span>Kombinasi <strong>customer</strong> dengan target <strong>sales</strong> tidak valid.</span>
            </li>
            <li class="flex gap-2">
                <i data-lucide="info" class="h-4 w-4 shrink-0 text-blue-600"></i>
                <span>Jika memilih template, pesan custom tidak perlu diisi.</span>
            </li>
            <li class="flex gap-2">
                <i data-lucide="clock" class="h-4 w-4 shrink-0 text-orange-600"></i>
                <span>Biarkan kosong untuk mengirim segera, atau isi jadwal untuk pengiriman tertunda.</span>
            </li>
        </ul>
    </aside>
</div>
@endsection

@push('scripts')
<script>
const recipientTypeSelect = document.getElementById('recipient_type');
const recipientTypeHidden = document.getElementById('recipient_type_hidden');
const targetTypeSelect = document.getElementById('target_type');
const filterContainer = document.getElementById('filterContainer');
const branchFilter = document.getElementById('branchFilter');
const customerFilter = document.getElementById('customerFilter');
const userFilter = document.getElementById('userFilter');
const templateIdSelect = document.getElementById('template_id');
const templatePreview = document.getElementById('templatePreview');
const templateContent = document.getElementById('templateContent');
const templateVariables = document.getElementById('templateVariables');
const channelInputs = document.querySelectorAll('input[name="channel"]');
const whatsappFields = document.getElementById('whatsappFields');
const variantContainer = document.getElementById('variantContainer');
const variantsList = document.getElementById('variantsList');
const addVariantBtn = document.getElementById('addVariantBtn');
const customMessageContainer = document.getElementById('customMessageContainer');

const validCombinations = {
    customer: ['all', 'branch', 'rental_status', 'product', 'category', 'customer'],
    user: ['all', 'branch', 'sales'],
    both: ['all', 'branch'],
};

const templates = @json($templates);

function getSelectedChannel() {
    const checked = document.querySelector('input[name="channel"]:checked');
    return checked ? checked.value : null;
}

function isWhatsappSelected() {
    return getSelectedChannel() === 'whatsapp';
}

function updateRecipientTypeLock() {
    const selectedChannel = getSelectedChannel();
    
    if (selectedChannel === 'in_app') {
        recipientTypeSelect.value = 'user';
        recipientTypeSelect.disabled = true;
        if (recipientTypeHidden) recipientTypeHidden.value = 'user';
    } else if (selectedChannel === 'whatsapp') {
        recipientTypeSelect.value = 'customer';
        recipientTypeSelect.disabled = true;
        if (recipientTypeHidden) recipientTypeHidden.value = 'customer';
    } else {
        recipientTypeSelect.value = '';
        recipientTypeSelect.disabled = true;
        if (recipientTypeHidden) recipientTypeHidden.value = '';
    }
}

function updateWhatsappFields() {
    if (whatsappFields) {
        whatsappFields.style.display = isWhatsappSelected() ? 'block' : 'none';
    }
}

function updateVariantFields() {
    if (variantContainer && customMessageContainer) {
        if (isWhatsappSelected()) {
            variantContainer.classList.remove('hidden');
            customMessageContainer.classList.add('hidden');
            
            if (variantsList.children.length === 0) {
                addVariant();
            }
        } else {
            variantContainer.classList.add('hidden');
            customMessageContainer.classList.remove('hidden');
        }
    }
}

function addVariant() {
    if (!variantsList) return;
    
    const index = variantsList.children.length;
    const variantId = 'variant_' + Date.now() + '_' + index;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-start';
    div.innerHTML = `
        <textarea name="custom_message[]" rows="3" class="form-input flex-1" placeholder="Variasi pesan #${index + 1} (contoh: Halo @{{ name }}, ada promo!)"></textarea>
        <button type="button" class="remove-variant-btn mt-1 text-red-500 hover:text-red-700" data-variant-id="${variantId}">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
        </button>
    `;
    
    variantsList.appendChild(div);
    
    div.querySelector('.remove-variant-btn').addEventListener('click', function () {
        if (variantsList.children.length > 1) {
            div.remove();
            updateVariantNumbers();
        } else {
            alert('Minimal 1 variasi pesan.');
        }
    });
}

function updateVariantNumbers() {
    const variants = variantsList.querySelectorAll('textarea[name="custom_message[]"]');
    variants.forEach((textarea, index) => {
        textarea.placeholder = `Variasi pesan #${index + 1} (contoh: Halo @{{ name }}, ada promo!)`;
    });
}

function updateTargetOptions() {
    const recipientType = recipientTypeSelect.value;
    const allowedTargets = validCombinations[recipientType] || [];
    const selectedChannel = getSelectedChannel();

    const currentAllowed = selectedChannel === 'whatsapp' ? validCombinations.customer : allowedTargets;

    Array.from(targetTypeSelect.options).forEach(option => {
        if (option.value === '') {
            option.disabled = false;
            return;
        }

        const isAllowed = currentAllowed.includes(option.value);
        const channelMatch = !option.dataset.channel || option.dataset.channel === selectedChannel;
        option.disabled = !(isAllowed && channelMatch);
    });

    if (targetTypeSelect.value && targetTypeSelect.querySelector('option:checked')?.disabled) {
        targetTypeSelect.value = '';
    }

    updateFilters();
    updateWhatsappFields();
    updateVariantFields();
}

function updateFilters() {
    const recipientType = recipientTypeSelect.value;
    const targetType = targetTypeSelect.value;

    filterContainer.classList.add('hidden');
    branchFilter.classList.add('hidden');
    customerFilter.classList.add('hidden');
    userFilter.classList.add('hidden');

    if (!targetType) return;

    filterContainer.classList.remove('hidden');

    if (targetType === 'branch') {
        branchFilter.classList.remove('hidden');
    }

    if (targetType === 'customer') {
        customerFilter.classList.remove('hidden');
    }

    if ((recipientType === 'user' || recipientType === 'both') && (targetType === 'all' || targetType === 'branch' || targetType === 'sales')) {
        userFilter.classList.remove('hidden');
    }
}

function updateTemplatePreview() {
    const templateId = templateIdSelect.value;
    if (!templateId) {
        templatePreview.classList.add('hidden');
        return;
    }

    const template = templates.find(t => t.id == templateId);
    if (!template) return;

    templateContent.textContent = template.content;
    if (template.variables && template.variables.length > 0) {
        templateVariables.textContent = 'Variabel: ' + template.variables.join(', ');
        templateVariables.classList.remove('hidden');
    } else {
        templateVariables.classList.add('hidden');
    }
    templatePreview.classList.remove('hidden');
}

channelInputs.forEach(input => {
    input.addEventListener('change', function () {
        updateRecipientTypeLock();
        updateTargetOptions();
    });
});

if (addVariantBtn) {
    addVariantBtn.addEventListener('click', addVariant);
}

recipientTypeSelect.addEventListener('change', updateTargetOptions);
targetTypeSelect.addEventListener('change', updateFilters);
templateIdSelect.addEventListener('change', updateTemplatePreview);

document.getElementById('campaignForm').addEventListener('submit', function (e) {
    const selectedChannel = getSelectedChannel();
    if (!selectedChannel) {
        e.preventDefault();
        alert('Pilih minimal satu channel.');
        return;
    }
    if (selectedChannel === 'whatsapp') {
        const recipientType = recipientTypeSelect.value;
        const targetType = targetTypeSelect.value;
        if (recipientType !== 'customer') {
            e.preventDefault();
            alert('Channel WhatsApp hanya mendukung target Customer.');
            return;
        }
        if (targetType === 'sales') {
            e.preventDefault();
            alert('Channel WhatsApp tidak mendukung target Sales.');
            return;
        }
    }
    if (selectedChannel === 'in_app') {
        const recipientType = recipientTypeSelect.value;
        if (recipientType !== 'user' && recipientType !== 'both') {
            e.preventDefault();
            alert('Channel Notifikasi In-App hanya mendukung target User/Sales.');
            return;
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    updateRecipientTypeLock();
    updateTargetOptions();
    lucide.createIcons();

    const delaySelect = document.querySelector('select[name="delay_seconds"]');
    const customDelayContainer = document.getElementById('customDelayContainer');
    const customDelayInput = document.querySelector('input[name="delay_seconds_custom"]');

    if (delaySelect && customDelayContainer) {
        delaySelect.addEventListener('change', () => {
            if (delaySelect.value === 'custom') {
                customDelayContainer.classList.remove('hidden');
                if (customDelayInput) customDelayInput.focus();
            } else {
                customDelayContainer.classList.add('hidden');
            }
        });
    }

    const form = document.querySelector('form[action="{{ route('broadcast-campaigns.store') }}"]');
    if (form && delaySelect) {
        form.addEventListener('submit', () => {
            if (delaySelect.value === 'custom' && customDelayInput) {
                customDelayInput.name = 'delay_seconds';
            }
        });
    }
});
</script>
@endpush

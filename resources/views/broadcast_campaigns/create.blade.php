@extends('Layouts.app')

@section('title', 'Buat Broadcast Campaign - SewaJas')
@section('page-title', 'Buat Broadcast Campaign')
@section('subtitle', 'Buat campaign broadcast multi-channel')

@section('content')
<div class="w-full">
    <section class="w-full rounded-3xl border border-slate-200 bg-white p-5 shadow-card-sm lg:p-8">
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
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="template_id" class="block text-sm font-bold text-slate-700">Template Pesan</label>
                        <a href="{{ route('broadcast-campaigns.templates.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Kelola Template</a>
                    </div>
                    <select name="template_id" id="template_id" class="form-input">
                        <option value="">Pilih template (opsional)</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" @selected(old('template_id') == $template->id)>{{ $template->name }} ({{ $template->category }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">
                        Pilih template jika ingin menggunakan pesan yang sudah disimpan.
                    </p>
                    @error('template_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div id="templatePreview" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-2 text-xs font-semibold text-slate-500">Preview Template:</p>
                    <p id="templateContent" class="text-sm text-slate-700"></p>
                    <p id="templateVariables" class="mt-2 text-xs text-slate-500"></p>
                </div>
            </div>

            <div id="customMessageContainer">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label class="block text-sm font-bold text-slate-700">
                        Pesan Custom
                    </label>
            
                    <span class="text-xs font-semibold text-slate-400">
                        Isi jika tidak menggunakan template
                    </span>
                </div>
            
                <textarea
                    name="custom_message"
                    rows="5"
                    class="form-input"
                    placeholder="Tulis pesan custom di sini. Variabel: @{{name}}, @{{phone}}, @{{sender_name}}">{{ old('custom_message') }}</textarea>
            
                <p class="mt-1.5 text-xs text-slate-500">
                    Tulis pesan sendiri jika tidak menggunakan template.
                </p>
            
                @error('custom_message')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
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

        // Tampilkan pesan custom jika tidak memakai template
        if (customMessageContainer) {
            customMessageContainer.classList.remove('hidden');
        }

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

    // Sembunyikan pesan custom karena template sudah dipilih
    if (customMessageContainer) {
        customMessageContainer.classList.add('hidden');
    }
}

channelInputs.forEach(input => {
    input.addEventListener('change', function () {
        updateRecipientTypeLock();
        updateTargetOptions();
    });
});

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
});
</script>
@endpush

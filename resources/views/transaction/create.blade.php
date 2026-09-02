@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Header Action Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('transaction.index') }}">Bank Settings</a></li>
                    @if(isset($selectedBank))
                        <li class="breadcrumb-item"><a href="{{ route('transaction.log', $selectedBank->id) }}">Transaction Logs</a></li>
                    @endif
                    <li class="breadcrumb-item active fw-bold">{{ isset($transaction) ? 'Edit Transaction' : 'Create Multiple Entries' }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">{{ isset($transaction) ? 'Edit Transaction' : 'Create Multiple Transactions' }}</h4>
        </div>

        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
            <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-wallet me-1"></i> Bank Settings
            </a>
            @if(isset($selectedBank))
                <a href="{{ route('transaction.log', $selectedBank->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Transaction Logs
                </a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ isset($transaction) ? route('transaction.update', $transaction->id) : route('transaction.store') }}" method="POST" id="transactionForm" onsubmit="return validateForm()">
                @csrf
                <!-- Pass selectedBank ID for JavaScript referencing -->
                <input type="hidden" id="selected_bank_id_val" value="{{ $selectedBank->id ?? '' }}">

                <div class="row g-3 mb-4">
                    @if(isset($transaction))
                        <!-- Locked Fields Notice for Edit Mode -->
                        <div class="col-12 alert alert-warning py-2 mb-2">
                            <i class="bx bx-info-circle me-1"></i> Transaction Date, Flow Type, Direction, Bank Accounts, and Purpose are locked during edit mode. You can only change Amount, Remark 1, and Remark 2.
                        </div>
                    @endif

                    <!-- Bank Label Display Header -->
                    <div class="col-12 alert alert-secondary py-2 mb-2">
                        <strong>Selected Bank Account: </strong>
                        <span id="headerBankDisplay" class="text-dark fw-bold">
                            @if(isset($selectedBank))
                                {{ $selectedBank->owner_name }} - {{ $selectedBank->bank->short_name ?? '' }}
                            @elseif(isset($transaction))
                                {{ $transaction->bankSetting->owner_name ?? '' }} - {{ $transaction->bankSetting->bank->short_name ?? '' }}
                            @else
                                Please select below
                            @endif
                        </span>
                    </div>

                    <!-- Date -->
                    <div class="col-md-4">
                        <label class="form-label" for="transaction_date">Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control {{ isset($transaction) ? 'bg-light' : '' }}" value="{{ isset($transaction) ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') : date('Y-m-d') }}" {{ isset($transaction) ? 'disabled' : 'required' }}>
                        @if(isset($transaction))
                            <input type="hidden" name="transaction_date" value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') }}">
                        @endif
                    </div>

                    <!-- Transaction Flow Type -->
                    <div class="col-md-4">
                        <label class="form-label" for="type">Transaction Flow Type</label>
                        <select name="type" id="type" class="form-select {{ isset($transaction) ? 'bg-light' : '' }}" {{ isset($transaction) ? 'disabled' : 'required' }} onchange="handleTypeChange()">
                            <option value="" disabled {{ !isset($transaction) ? 'selected' : '' }}>Select Flow Type</option>
                            <option value="customer" {{ (isset($selectedBank) && !isset($transaction)) || (isset($transaction) && $transaction->type === 'customer') ? 'selected' : '' }}>Customer Transaction</option>
                            <option value="own" {{ (isset($transaction) && $transaction->type === 'own') ? 'selected' : '' }}>Own Account Transfer</option>
                        </select>
                        @if(isset($transaction))
                            <input type="hidden" name="type" value="{{ $transaction->type }}">
                        @endif
                    </div>

                    <!-- Direction -->
                    <div class="col-md-4">
                        <label class="form-label" for="transfer_direction">Direction (Bank In / Out)</label>
                        <select name="transfer_direction" id="transfer_direction" class="form-select {{ isset($transaction) ? 'bg-light' : '' }}" {{ isset($transaction) ? 'disabled' : 'required' }} onchange="handleTypeChange()">
                            <option value="+" {{ (isset($transaction) && $transaction->transfer_direction === '+') ? 'selected' : '' }}>Plus (+) [Bank In]</option>
                            <option value="-" {{ (isset($transaction) && $transaction->transfer_direction === '-') ? 'selected' : '' }}>Minus (-) [Bank Out]</option>
                        </select>
                        @if(isset($transaction))
                            <input type="hidden" name="transfer_direction" value="{{ $transaction->transfer_direction }}">
                        @endif
                    </div>

                    <!-- CUSTOMER ACCOUNT SELECTOR -->
                    <div class="col-md-12 customer-fields" style="display: {{ (isset($transaction) && $transaction->type === 'own') ? 'none' : 'block' }};">
                        <label class="form-label" for="bank_setting_id">Bank Account</label>
                        <select id="bank_setting_id_display" class="form-select bg-light" disabled>
                            @foreach($bankSettings as $bank)
                                <option value="{{ $bank->id }}" data-label="{{ $bank->owner_name }} - {{ $bank->bank->short_name ?? '' }}"
                                    {{ ((isset($selectedBank) && $selectedBank->id == $bank->id) || (isset($transaction) && $transaction->bank_setting_id == $bank->id)) ? 'selected' : '' }}>
                                    {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="bank_setting_id" id="bank_setting_id" value="{{ isset($transaction) ? $transaction->bank_setting_id : ($selectedBank->id ?? '') }}">
                    </div>

                    <!-- OWN ACCOUNT TRANSFER SELECTORS -->
                    <div class="col-md-6 own-fields" style="display: {{ (isset($transaction) && $transaction->type === 'own') ? 'block' : 'none' }};">
                        <label class="form-label fw-bold text-danger" id="label_source_bank" for="source_bank_id">From Bank Account (Bank Out -)</label>

                        <select name="source_bank_id" id="source_bank_id" class="form-select border-danger" onchange="filterBankOptions()">
                            <option value="" disabled {{ !isset($transaction) ? 'selected' : '' }}>Select Source Bank Account</option>
                            @foreach($bankSettings as $bank)
                                <option value="{{ $bank->id }}" {{ (isset($transaction) && $transaction->bank_setting_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                </option>
                            @endforeach
                        </select>

                        <select id="source_bank_id_display" class="form-select border-danger bg-light" disabled style="display: none;">
                            @foreach($bankSettings as $bank)
                                <option value="{{ $bank->id }}" {{ (isset($transaction) && $transaction->bank_setting_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 own-fields" style="display: {{ (isset($transaction) && $transaction->type === 'own') ? 'block' : 'none' }};">
                        <label class="form-label fw-bold text-success" id="label_target_bank" for="target_bank_id">To Bank Account (Bank In +)</label>

                        <select name="target_bank_id" id="target_bank_id" class="form-select border-success" onchange="filterBankOptions()">
                            <option value="" disabled {{ !isset($transaction) ? 'selected' : '' }}>Select Target Bank Account</option>
                            @foreach($bankSettings as $bank)
                                <option value="{{ $bank->id }}" {{ (isset($transaction) && $transaction->target_bank_setting_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                </option>
                            @endforeach
                        </select>

                        <select id="target_bank_id_display" class="form-select border-success bg-light" disabled style="display: none;">
                            @foreach($bankSettings as $bank)
                                <option value="{{ $bank->id }}" {{ (isset($transaction) && $transaction->target_bank_setting_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                <!-- MULTIPLE TRANSACTION ROWS MANAGER -->
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Transaction Entry Rows</label>
                        @if(!isset($transaction))
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                                <i class="bx bx-plus"></i> Add Row
                            </button>
                        @endif
                    </div>

                    <div id="transaction-rows-container">
                        <div class="row transaction-row g-2 mb-3 align-items-center border p-2 rounded bg-light">
                            <div class="col-md-3">
                                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control" name="{{ isset($transaction) ? 'amount' : 'items[0][amount]' }}" placeholder="0.00" value="{{ isset($transaction) ? $transaction->amount : '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                @if(isset($transaction))
                                    <select class="form-select bg-light" disabled>
                                        @foreach($purposes as $purpose)
                                            <option value="{{ $purpose->id }}" {{ $transaction->purpose_id == $purpose->id ? 'selected' : '' }}>{{ $purpose->title }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="purpose_id" value="{{ $transaction->purpose_id }}">
                                @else
                                    <select name="items[0][purpose_id]" class="form-select" required>
                                        <option value="" disabled selected>Select Purpose</option>
                                        @foreach($purposes as $purpose)
                                            <option value="{{ $purpose->id }}">{{ $purpose->title }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 1 (Details/Customer Name)</label>
                                <input type="text" class="form-control" name="{{ isset($transaction) ? 'remark_1' : 'items[0][remark_1]' }}" placeholder="Remark 1" value="{{ isset($transaction) ? $transaction->remark_1 : '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Remark 2</label>
                                <input type="text" class="form-control" name="{{ isset($transaction) ? 'remark_2' : 'items[0][remark_2]' }}" placeholder="Remark 2" value="{{ isset($transaction) ? $transaction->remark_2 : '' }}">
                            </div>
                            @if(!isset($transaction))
                                <div class="col-md-1 text-end pt-4">
                                    <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeRow(this)">X</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">{{ isset($transaction) ? 'Update Transaction' : 'Save All Transactions' }}</button>
                    <a href="{{ isset($selectedBank) ? route('transaction.log', $selectedBank->id) : route('transaction.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let rowIndex = 1;

    function addRow() {
        let container = document.getElementById('transaction-rows-container');
        let purposeOptions = `@foreach($purposes as $purpose)<option value="{{ $purpose->id }}">{{ $purpose->title }}</option>@endforeach`;

        let rowHtml = `
            <div class="row transaction-row g-2 mb-3 align-items-center border p-2 rounded bg-light">
                <div class="col-md-3">
                    <label class="form-label small">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="items[${rowIndex}][amount]" placeholder="0.00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                    <select name="items[${rowIndex}][purpose_id]" class="form-select" required>
                        <option value="" disabled selected>Select Purpose</option>
                        ${purposeOptions}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Remark 1</label>
                    <input type="text" class="form-control" name="items[${rowIndex}][remark_1]" placeholder="Remark 1">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Remark 2</label>
                    <input type="text" class="form-control" name="items[${rowIndex}][remark_2]" placeholder="Remark 2">
                </div>
                <div class="col-md-1 text-end pt-4">
                    <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeRow(this)">X</button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', rowHtml);
        rowIndex++;
        updateRemoveButtons();
    }

    function removeRow(button) {
        let rows = document.querySelectorAll('.transaction-row');
        if (rows.length > 1) {
            button.closest('.transaction-row').remove();
            updateRemoveButtons();
        }
    }

    function updateRemoveButtons() {
        let rows = document.querySelectorAll('.transaction-row');
        let removeBtns = document.querySelectorAll('.remove-row-btn');
        removeBtns.forEach(btn => {
            if (rows.length === 1) {
                btn.setAttribute('disabled', 'true');
            } else {
                btn.removeAttribute('disabled');
            }
        });
    }

    function handleTypeChange() {
        let typeElement = document.getElementById('type');
        if (!typeElement) return;

        let type = typeElement.value;
        let direction = document.getElementById('transfer_direction').value;
        let customerFields = document.querySelectorAll('.customer-fields');
        let ownFields = document.querySelectorAll('.own-fields');

        let sourceSelect = document.getElementById('source_bank_id');
        let sourceDisplay = document.getElementById('source_bank_id_display');
        let targetSelect = document.getElementById('target_bank_id');
        let targetDisplay = document.getElementById('target_bank_id_display');
        let selectedBankId = document.getElementById('selected_bank_id_val').value;

        let isEditMode = {{ isset($transaction) ? 'true' : 'false' }};

        if (type === 'own') {
            customerFields.forEach(el => el.style.display = 'none');
            ownFields.forEach(el => el.style.display = 'block');

            if (direction === '+') {
                // BANK IN: From = Counterpart (Bryan / target_bank_setting_id), To = Selected Bank (Sing Guy / bank_setting_id)
                document.getElementById('label_source_bank').innerText = "From Bank Account (Bank Out -)";
                document.getElementById('label_target_bank').innerText = "To Bank Account [Selected Bank] (Bank In +)";

                if (isEditMode) {
                    sourceSelect.style.display = 'none';
                    sourceDisplay.style.display = 'block';
                    @if(isset($transaction))
                    sourceDisplay.value = "{{ $transaction->target_bank_setting_id }}";
                    @endif
                    sourceDisplay.setAttribute('name', 'source_bank_id');
                    sourceSelect.removeAttribute('name');

                    targetSelect.style.display = 'none';
                    targetDisplay.style.display = 'block';
                    targetDisplay.value = "{{ isset($transaction) ? $transaction->bank_setting_id : '' }}";
                    targetDisplay.setAttribute('name', 'target_bank_id');
                    targetSelect.removeAttribute('name');
                } else {
                    sourceSelect.style.display = 'block';
                    sourceSelect.setAttribute('required', 'required');
                    sourceDisplay.style.display = 'none';
                    sourceSelect.setAttribute('name', 'source_bank_id');

                    targetSelect.style.display = 'none';
                    targetSelect.removeAttribute('required');
                    targetSelect.removeAttribute('name');

                    targetDisplay.style.display = 'block';
                    targetDisplay.value = selectedBankId;
                    targetDisplay.setAttribute('name', 'target_bank_id');
                }
            } else {
                // BANK OUT: From = Selected Bank (Sing Guy / bank_setting_id), To = Counterpart (Bryan / target_bank_setting_id)
                document.getElementById('label_source_bank').innerText = "From Bank Account [Selected Bank] (Bank Out -)";
                document.getElementById('label_target_bank').innerText = "To Bank Account (Bank In +)";

                if (isEditMode) {
                    sourceSelect.style.display = 'none';
                    sourceDisplay.style.display = 'block';
                    sourceDisplay.value = "{{ isset($transaction) ? $transaction->bank_setting_id : '' }}";
                    sourceDisplay.setAttribute('name', 'source_bank_id');
                    sourceSelect.removeAttribute('name');

                    targetSelect.style.display = 'none';
                    targetDisplay.style.display = 'block';
                    @if(isset($transaction))
                    targetDisplay.value = "{{ $transaction->target_bank_setting_id }}";
                    @endif
                    targetDisplay.setAttribute('name', 'target_bank_id');
                    targetSelect.removeAttribute('name');
                } else {
                    sourceSelect.style.display = 'none';
                    sourceSelect.removeAttribute('required');
                    sourceSelect.removeAttribute('name');

                    sourceDisplay.style.display = 'block';
                    sourceDisplay.value = selectedBankId;
                    sourceDisplay.setAttribute('name', 'source_bank_id');

                    targetSelect.style.display = 'block';
                    targetSelect.setAttribute('required', 'required');
                    targetSelect.setAttribute('name', 'target_bank_id');

                    targetDisplay.style.display = 'none';
                    targetDisplay.removeAttribute('name');
                }
            }
            filterBankOptions();
        } else if (type === 'customer') {
            customerFields.forEach(el => el.style.display = 'block');
            ownFields.forEach(el => el.style.display = 'none');
            if(sourceSelect) sourceSelect.removeAttribute('required');
            if(targetSelect) targetSelect.removeAttribute('required');
        }
    }

    // Automatically hide/prevent the selected account from appearing in the opposite selectable dropdown
    function filterBankOptions() {
        let isEditMode = {{ isset($transaction) ? 'true' : 'false' }};
        if (isEditMode) return;

        let selectedBankId = document.getElementById('selected_bank_id_val').value;
        let direction = document.getElementById('transfer_direction').value;

        let sourceSelect = document.getElementById('source_bank_id');
        let targetSelect = document.getElementById('target_bank_id');

        if (direction === '+') {
            // Bank In: user picks Source. Hide selectedBank from Source dropdown.
            for (let option of sourceSelect.options) {
                if (option.value === selectedBankId) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            }
        } else {
            // Bank Out: user picks Target. Hide selectedBank from Target dropdown.
            for (let option of targetSelect.options) {
                if (option.value === selectedBankId) {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'block';
                }
            }
        }
    }

    // Safety net form validation before submitting
    function validateForm() {
        let isEditMode = {{ isset($transaction) ? 'true' : 'false' }};
        if (isEditMode) return true;

        let type = document.getElementById('type').value;
        if (type !== 'own') return true;

        let selectedBankId = document.getElementById('selected_bank_id_val').value;
        let direction = document.getElementById('transfer_direction').value;
        let sourceVal = document.getElementById('source_bank_id').value;
        let targetVal = document.getElementById('target_bank_id').value;

        if (direction === '+' && sourceVal === selectedBankId) {
            alert('Error: You cannot select your own account as the Source account for a Bank In transfer.');
            return false;
        }
        if (direction === '-' && targetVal === selectedBankId) {
            alert('Error: You cannot select your own account as the Target account for a Bank Out transfer.');
            return false;
        }
        return true;
    }

    function updateHeaderDisplay() {
        let select = document.getElementById('bank_setting_id_display');
        if (select && select.options[select.selectedIndex]) {
            let option = select.options[select.selectedIndex];
            let label = option.getAttribute('data-label');
            if (label) {
                document.getElementById('headerBankDisplay').innerText = label;
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        handleTypeChange();
        updateHeaderDisplay();
        updateRemoveButtons();
    });
</script>
@endsection

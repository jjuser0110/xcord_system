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
                    <li class="breadcrumb-item active fw-bold">Create Multiple Entries</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Create Multiple Transactions</h4>
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
            <form action="{{ route('transaction.store') }}" method="POST" id="transactionForm">
                @csrf
                <!-- Pass selectedBank ID for JavaScript referencing -->
                <input type="hidden" id="selected_bank_id_val" value="{{ $selectedBank->id ?? '' }}">

                <div class="row g-3 mb-4">
                    <!-- Bank Label Display Header -->
                    <div class="col-12 alert alert-secondary py-2 mb-2">
                        <strong>Selected Bank Account: </strong>
                        <span id="headerBankDisplay" class="text-dark fw-bold">
                            @if(isset($selectedBank))
                                {{ $selectedBank->owner_name }} - {{ $selectedBank->bank->short_name ?? '' }}
                            @else
                                Please select below
                            @endif
                        </span>
                    </div>

                    <!-- Date -->
                    <div class="col-md-4">
                        <label class="form-label" for="transaction_date">Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <!-- Transaction Flow Type -->
                    <div class="col-md-4">
                        <label class="form-label" for="type">Transaction Flow Type</label>
                        <select name="type" id="type" class="form-select" required onchange="handleTypeChange()">
                            <option value="" disabled selected>Select Flow Type</option>
                            <option value="customer" {{ (isset($selectedBank) ? 'selected' : '') }}>Customer Transaction</option>
                            <option value="own">Own Account Transfer</option>
                        </select>
                    </div>

                    <!-- Direction -->
                    <div class="col-md-4">
                        <label class="form-label" for="transfer_direction">Direction (Bank In / Out)</label>
                        <select name="transfer_direction" id="transfer_direction" class="form-select" required onchange="handleTypeChange()">
                            <option value="+">Plus (+) [Bank In]</option>
                            <option value="-">Minus (-) [Bank Out]</option>
                        </select>
                    </div>

                    <!-- CUSTOMER ACCOUNT SELECTOR -->
                    <div class="col-md-12 customer-fields" style="display: none;">
                        <label class="form-label" for="bank_setting_id">Bank Account</label>
                        <select id="bank_setting_id_display" class="form-select bg-light" disabled>
                            @foreach($bankSettings as $bank)
                                @if(!isset($selectedBank) || $selectedBank->id == $bank->id)
                                    <option value="{{ $bank->id }}" data-label="{{ $bank->owner_name }} - {{ $bank->bank->short_name ?? '' }}"
                                        {{ (isset($selectedBank) && $selectedBank->id == $bank->id) ? 'selected' : '' }}>
                                        {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" name="bank_setting_id" id="bank_setting_id" value="{{ $selectedBank->id ?? '' }}">
                    </div>

                    <!-- OWN ACCOUNT TRANSFER SELECTORS (Dynamic Source/Target based on Direction) -->
                    <div class="col-md-6 own-fields" style="display: none;">
                        <label class="form-label fw-bold text-danger" id="label_source_bank" for="source_bank_id">From Bank Account (Bank Out -)</label>

                        <!-- Selectable dropdown for external accounts -->
                        <select name="source_bank_id" id="source_bank_id" class="form-select border-danger">
                            <option value="" disabled selected>Select Source Bank Account</option>
                            @foreach($bankSettings as $bank)
                                @if(!isset($selectedBank) || $selectedBank->id != $bank->id)
                                    <option value="{{ $bank->id }}">{{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})</option>
                                @endif
                            @endforeach
                        </select>

                        <!-- Disabled view when locked to selected bank -->
                        <select id="source_bank_id_display" class="form-select border-danger bg-light" disabled style="display: none;">
                            @foreach($bankSettings as $bank)
                                @if(!isset($selectedBank) || $selectedBank->id == $bank->id)
                                    <option value="{{ $bank->id }}" {{ (isset($selectedBank) && $selectedBank->id == $bank->id) ? 'selected' : '' }}>
                                        {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 own-fields" style="display: none;">
                        <label class="form-label fw-bold text-success" id="label_target_bank" for="target_bank_id">To Bank Account (Bank In +)</label>

                        <!-- Selectable dropdown for external accounts -->
                        <select name="target_bank_id" id="target_bank_id" class="form-select border-success">
                            <option value="" disabled selected>Select Target Bank Account</option>
                            @foreach($bankSettings as $bank)
                                @if(!isset($selectedBank) || $selectedBank->id != $bank->id)
                                    <option value="{{ $bank->id }}">{{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})</option>
                                @endif
                            @endforeach
                        </select>

                        <!-- Disabled view when locked to selected bank -->
                        <select id="target_bank_id_display" class="form-select border-success bg-light" disabled style="display: none;">
                            @foreach($bankSettings as $bank)
                                @if(!isset($selectedBank) || $selectedBank->id == $bank->id)
                                    <option value="{{ $bank->id }}" {{ (isset($selectedBank) && $selectedBank->id == $bank->id) ? 'selected' : '' }}>
                                        {{ $bank->owner_name }} - {{ $bank->bank->bank_name ?? '' }} (Balance: {{ number_format($bank->amount, 2) }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                <!-- MULTIPLE TRANSACTION ROWS MANAGER -->
                <div class="col-12 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0">Transaction Entry Rows</label>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                            <i class="bx bx-plus"></i> Add Row
                        </button>
                    </div>

                    <div id="transaction-rows-container">
                        <div class="row transaction-row g-2 mb-3 align-items-center border p-2 rounded bg-light">
                            <div class="col-md-3">
                                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="items[0][amount]" placeholder="0.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                <select name="items[0][purpose_id]" class="form-select" required>
                                    <option value="" disabled selected>Select Purpose</option>
                                    @foreach($purposes as $purpose)
                                        <option value="{{ $purpose->id }}">{{ $purpose->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 1 (Details/Customer Name)</label>
                                <input type="text" class="form-control" name="items[0][remark_1]" placeholder="Remark 1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Remark 2</label>
                                <input type="text" class="form-control" name="items[0][remark_2]" placeholder="Remark 2">
                            </div>
                            <div class="col-md-1 text-end pt-4">
                                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeRow(this)">X</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Save All Transactions</button>
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
        let type = document.getElementById('type').value;
        let direction = document.getElementById('transfer_direction').value;
        let customerFields = document.querySelectorAll('.customer-fields');
        let ownFields = document.querySelectorAll('.own-fields');

        let sourceSelect = document.getElementById('source_bank_id');
        let sourceDisplay = document.getElementById('source_bank_id_display');
        let targetSelect = document.getElementById('target_bank_id');
        let targetDisplay = document.getElementById('target_bank_id_display');

        if (type === 'own') {
            customerFields.forEach(el => el.style.display = 'none');
            ownFields.forEach(el => el.style.display = 'block');

            if (direction === '+') {
                // BANK IN: Selected Bank is Target (To), dropdown is Source (From)
                document.getElementById('label_source_bank').innerText = "From Bank Account (Bank Out -)";
                document.getElementById('label_target_bank').innerText = "To Bank Account [Selected Bank] (Bank In +)";

                sourceSelect.style.display = 'block';
                sourceSelect.setAttribute('required', 'required');
                sourceDisplay.style.display = 'none';
                sourceDisplay.removeAttribute('name');

                targetSelect.style.display = 'none';
                targetSelect.removeAttribute('required');
                targetSelect.removeAttribute('name');

                targetDisplay.style.display = 'block';
                targetDisplay.setAttribute('name', 'target_bank_id');
            } else {
                // BANK OUT: Selected Bank is Source (From), dropdown is Target (To)
                document.getElementById('label_source_bank').innerText = "From Bank Account [Selected Bank] (Bank Out -)";
                document.getElementById('label_target_bank').innerText = "To Bank Account (Bank In +)";

                sourceSelect.style.display = 'none';
                sourceSelect.removeAttribute('required');
                sourceSelect.removeAttribute('name');

                sourceDisplay.style.display = 'block';
                sourceDisplay.setAttribute('name', 'source_bank_id');

                targetSelect.style.display = 'block';
                targetSelect.setAttribute('required', 'required');
                targetSelect.setAttribute('name', 'target_bank_id');

                targetDisplay.style.display = 'none';
                targetDisplay.removeAttribute('name');
            }
        } else if (type === 'customer') {
            customerFields.forEach(el => el.style.display = 'block');
            ownFields.forEach(el => el.style.display = 'none');
            sourceSelect.removeAttribute('required');
            targetSelect.removeAttribute('required');
        }
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

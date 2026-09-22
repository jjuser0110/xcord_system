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
            <form action="{{ isset($transaction) ? route('transaction.update', $transaction->id) : route('transaction.store') }}" method="POST" id="transactionForm" onsubmit="return handleFormSubmit()">
                @csrf
                <!-- Pass selectedBank ID and Balance for JS Calculations -->
                <input type="hidden" id="selected_bank_id_val" value="{{ $selectedBank->id ?? '' }}">
                <input type="hidden" id="selected_bank_initial_balance" value="{{ $selectedBank->amount ?? 0 }}">
                <input type="hidden" id="selected_bank_name" value="{{ isset($selectedBank) ? $selectedBank->owner_name . ' - ' . optional($selectedBank->bank)->short_name : '' }}">
                <input type="hidden" name="bank_setting_id" id="bank_setting_id" value="{{ isset($transaction) ? $transaction->bank_setting_id : ($selectedBank->id ?? '') }}">

                <!-- Bank settings raw JSON dictionary lookup for label resolution and balances -->
                <script>
                    window.bankOptionsData = [
                        @foreach($bankSettings as $b)
                            { id: "{{ $b->id }}", name: "{{ $b->owner_name }} - {{ optional($b->bank)->short_name ?? '' }}", balance: "{{ $b->amount }}" },
                        @endforeach
                    ];
                </script>

                <div class="row g-3 mb-4">
                    @if(isset($transaction))
                        <div class="col-12 alert alert-warning py-2 mb-2">
                            <i class="bx bx-info-circle me-1"></i> Transaction Date, Flow Type, Direction, Bank Accounts, and Purpose are locked during edit mode. You can only change Amount, Remark 1, and Remark 2.
                        </div>
                    @endif

                    <!-- Bank Label Display Header -->
                    <div class="col-12 alert alert-secondary py-2 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Selected Bank Account: </strong>
                            <span id="headerBankDisplay" class="text-dark fw-bold">
                                @if(isset($selectedBank))
                                    {{ $selectedBank->owner_name }} - {{ $selectedBank->bank->short_name ?? '' }} (Balance: {{ number_format($selectedBank->amount, 2) }})
                                @elseif(isset($transaction))
                                    {{ $transaction->bankSetting->owner_name ?? '' }} - {{ $transaction->bankSetting->bank->short_name ?? '' }}
                                @else
                                    Please select below
                                @endif
                            </span>
                        </div>
                        <!-- Add Target Bank Acc Button (Only visible for own account transfer) -->
                        <div id="addTargetBankContainer" style="display: none;">
                            <button type="button" class="btn btn-sm btn-success fw-bold" onclick="addTransferBlock()">
                                <i class="bx bx-plus-circle me-1"></i> Add target bank acc
                            </button>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-4">
                        <label class="form-label" for="transaction_date">Transaction Date</label>
                        <input type="date" name="transaction_date" class="form-control {{ isset($transaction) ? 'bg-light' : '' }}" value="{{ old('transaction_date', isset($transaction) ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') : date('Y-m-d')) }}" {{ isset($transaction) ? 'disabled' : 'required' }}>
                        @if(isset($transaction))
                            <input type="hidden" name="transaction_date" value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') }}">
                        @endif
                    </div>

                    <!-- Transaction Flow Type -->
                    <div class="col-md-4">
                        <label class="form-label" for="type">Transaction Flow Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select {{ isset($transaction) ? 'bg-light' : '' }}" {{ isset($transaction) ? 'disabled' : 'required' }} onchange="handleTypeChange(true)">
                            <option value="" disabled {{ (!isset($transaction) && !old('type')) ? 'selected' : '' }}>Select Flow Type</option>
                            <option value="customer" {{ (old('type', $transaction->type ?? '') === 'customer') ? 'selected' : '' }}>External Transaction</option>
                            <option value="own" {{ (old('type', $transaction->type ?? '') === 'own') ? 'selected' : '' }}>Own Account Transfer</option>
                        </select>
                        @if(isset($transaction))
                            <input type="hidden" name="type" value="{{ $transaction->type }}">
                        @endif
                    </div>

                    <!-- Direction -->
                    <div class="col-md-4">
                        <label class="form-label" for="transfer_direction">Direction (Bank In / Out) <span class="text-danger">*</span></label>
                        <select name="transfer_direction" id="transfer_direction" class="form-select {{ isset($transaction) ? 'bg-light' : '' }}" {{ isset($transaction) ? 'disabled' : 'required' }} onchange="handleTypeChange(true)">
                            <option value="" disabled {{ (!isset($transaction) && !old('transfer_direction')) ? 'selected' : '' }}>Select Direction</option>
                            <option value="+" {{ (old('transfer_direction', $transaction->transfer_direction ?? '') === '+') ? 'selected' : '' }}>Plus (+) [Bank In]</option>
                            <option value="-" {{ (old('transfer_direction', $transaction->transfer_direction ?? '') === '-') ? 'selected' : '' }}>Minus (-) [Bank Out]</option>
                        </select>
                        @if(isset($transaction))
                            <input type="hidden" name="transfer_direction" value="{{ $transaction->transfer_direction }}">
                        @endif
                    </div>
                </div>

                <hr>

                <!-- CONTAINER FOR MULTI-TARGET OWN ACCOUNT BLOCKS -->
                <div id="transfer-blocks-container">
                    <!-- Dynamic blocks will be injected here -->
                </div>

                <!-- CUSTOMER TRANSACTION ENTRY ROWS (Fallback for External) -->
                <div id="customer-rows-wrapper" style="display: block;">
                    <div class="col-12 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label fw-bold mb-0">Transaction Entry Rows</label>
                            @if(!isset($transaction))
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomerRow()">
                                    <i class="bx bx-plus"></i> Add Row
                                </button>
                            @endif
                        </div>

                        <div id="transaction-rows-container" class="d-flex flex-column gap-3">
                            @php
                                $oldItems = old('items', []);
                            @endphp
                            @if(empty($oldItems) && !isset($transaction))
                                @php $oldItems = [['amount' => '', 'purpose_id' => '', 'remark_1' => '', 'remark_2' => '']]; @endphp
                            @endif

                            @if(isset($transaction))
                                <div class="transaction-row border p-3 rounded bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="text-muted small fw-bold">Entry Row #1</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control customer-amount-input" name="amount" placeholder="0.00" value="{{ old('amount', $transaction->amount) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control bg-light" value="{{ $transaction->purpose->title ?? '' }}" readonly>
                                            <input type="hidden" name="purpose_id" value="{{ $transaction->purpose_id }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Remark 1 (Details/Customer Name)</label>
                                            <input type="text" class="form-control" name="remark_1" placeholder="Remark 1" value="{{ old('remark_1', $transaction->remark_1) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small">Remark 2</label>
                                            <input type="text" class="form-control" name="remark_2" placeholder="Remark 2" value="{{ old('remark_2', $transaction->remark_2) }}">
                                        </div>
                                    </div>
                                </div>
                            @else
                                @foreach($oldItems as $i => $item)
                                    <div class="transaction-row border p-3 rounded bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="text-muted small fw-bold">Entry Row #{{ $i + 1 }}</span>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-dark fs-6 fake-wallet-badge px-3 py-2 text-nowrap" title="Fake Wallet Balance">Bal: 0.00</span>
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeCustomerRow(this)"><i class="bx bx-x"></i></button>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control customer-amount-input" name="items[{{ $i }}][amount]" placeholder="0.00" value="{{ $item['amount'] ?? '' }}" oninput="calculateFakeWallets()" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                                <select name="items[{{ $i }}][purpose_id]" class="form-select purpose-select" required>
                                                    <option value="" disabled {{ empty($item['purpose_id']) ? 'selected' : '' }}>Select Purpose</option>
                                                    @foreach($purposes as $purpose)
                                                        @php
                                                            $label = $purpose->title;
                                                            if ($purpose->show_on_received_from_provider && $purpose->provider_name) {
                                                                $label .= " (Received from Provider: {$purpose->provider_name})";
                                                            } elseif ($purpose->show_on_topup_to_provider && $purpose->provider_name) {
                                                                $label .= " (Topup to Provider: {$purpose->provider_name})";
                                                            } elseif ($purpose->show_on_transfer_for_merchant) {
                                                                $label .= " (Transfer for Merchant)";
                                                            }
                                                        @endphp
                                                        <option value="{{ $purpose->id }}" data-flow-type="{{ $purpose->money_flow_type }}" {{ (isset($item['purpose_id']) && $item['purpose_id'] == $purpose->id) ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Remark 1 (Details/Customer Name)</label>
                                                <input type="text" class="form-control" name="items[{{ $i }}][remark_1]" placeholder="Remark 1" value="{{ $item['remark_1'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Remark 2</label>
                                                <input type="text" class="form-control" name="items[{{ $i }}][remark_2]" placeholder="Remark 2" value="{{ $item['remark_2'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" id="submitBtn" class="btn btn-primary">{{ isset($transaction) ? 'Update Transaction' : 'Save All Transactions' }}</button>
                    <a href="{{ isset($selectedBank) ? route('transaction.log', $selectedBank->id) : route('transaction.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let blockIndex = 0;

    const purposeOptionsHtml = `@foreach($purposes as $purpose)
        @php
            $label = $purpose->title;
            if ($purpose->show_on_received_from_provider && $purpose->provider_name) {
                $label .= " (Received from Provider: {$purpose->provider_name})";
            } elseif ($purpose->show_on_topup_to_provider && $purpose->provider_name) {
                $label .= " (Topup to Provider: {$purpose->provider_name})";
            } elseif ($purpose->show_on_transfer_for_merchant) {
                $label .= " (Transfer for Merchant)";
            }
        @endphp
        <option value="{{ $purpose->id }}" data-flow-type="{{ $purpose->money_flow_type }}">{{ $label }}</option>
    @endforeach`;

    function getBankBalance(id) {
        if (!id) return 0;
        let found = window.bankOptionsData.find(b => b.id == id);
        return found ? parseFloat(found.balance) || 0 : 0;
    }

    function getBankOptionsHtml(selectedId = '', excludeId = '') {
        let html = '<option value="" disabled selected>Select Bank Account</option>';
        window.bankOptionsData.forEach(b => {
            if (excludeId && b.id == excludeId) {
                return;
            }
            let sel = (b.id == selectedId) ? 'selected' : '';
            html += `<option value="${b.id}" ${sel}>${b.name} (Balance: ${parseFloat(b.balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })})</option>`;
        });
        return html;
    }

    function addTransferBlock(prefillSource = '', prefillTarget = '', itemsData = null) {
        let container = document.getElementById('transfer-blocks-container');
        let selectedBankId = document.getElementById('selected_bank_id_val').value;
        let direction = document.getElementById('transfer_direction').value;

        let sourceLabel = direction === '+' ? 'From Bank Account' : 'From Bank Account [Selected Bank]';
        let targetLabel = direction === '+' ? 'To Bank Account [Selected Bank]' : 'To Bank Account';

        let sourceDisabledAttr = direction === '-' ? 'disabled bg-light' : '';
        let targetDisabledAttr = direction === '+' ? 'disabled bg-light' : '';

        let sourceRequiredAttr = direction === '+' ? 'required' : '';
        let targetRequiredAttr = direction === '-' ? 'required' : '';

        let excludeSourceId = (direction === '+') ? selectedBankId : '';
        let excludeTargetId = (direction === '-') ? selectedBankId : '';

        let subRowsHtml = '';
        if (!itemsData || itemsData.length === 0) {
            itemsData = [{ amount: '', purpose_id: '', remark_1: '', remark_2: '' }];
        }

        itemsData.forEach((item, subRowCnt) => {
            let purposeOptionsWithSelected = purposeOptionsHtml;
            if (item.purpose_id) {
                purposeOptionsWithSelected = purposeOptionsWithSelected.replace(`value="${item.purpose_id}"`, `value="${item.purpose_id}" selected`);
            }

            subRowsHtml += `
                <div class="sub-row border p-3 rounded bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small fw-bold">Entry Row #${subRowCnt + 1}</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger source-wallet-badge fs-6 px-2 py-1 text-nowrap" title="From Bank Running Balance">Src Bal: 0.00</span>
                            <span class="badge bg-success target-wallet-badge fs-6 px-2 py-1 text-nowrap" title="To Bank Running Balance">Target Bal: 0.00</span>
                            <button type="button" class="btn btn-danger btn-sm remove-sub-row-btn" onclick="removeSubRow(this)"><i class="bx bx-x"></i></button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control sub-amount-input" name="transfers[${blockIndex}][items][${subRowCnt}][amount]" placeholder="0.00" value="${item.amount || ''}" oninput="calculateFakeWallets()" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                            <select name="transfers[${blockIndex}][items][${subRowCnt}][purpose_id]" class="form-select purpose-select" required>
                                <option value="" disabled ${!item.purpose_id ? 'selected' : ''}>Select Purpose</option>
                                ${purposeOptionsWithSelected}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Remark 1 (Auto-Fill)</label>
                            <input type="text" class="form-control bg-light remark-1-input" name="transfers[${blockIndex}][items][${subRowCnt}][remark_1]" readonly placeholder="Auto-filled" value="${item.remark_1 || ''}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Remark 2</label>
                            <input type="text" class="form-control" name="transfers[${blockIndex}][items][${subRowCnt}][remark_2]" placeholder="Remark 2" value="${item.remark_2 || ''}">
                        </div>
                    </div>
                </div>`;
        });

        let blockHtml = `
            <div class="transfer-block border border-primary p-3 rounded mb-4 bg-white position-relative shadow-sm" data-block-id="${blockIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="text-primary fw-bold m-0"><i class="bx bx-transfer me-1"></i> Target Bank Flow Group #${blockIndex + 1}</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-block-btn" onclick="removeTransferBlock(this)">Remove Target Bank Group</button>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger source-label mb-1">${sourceLabel}</label>
                        <select name="transfers[${blockIndex}][source_bank_id]" class="form-select border-danger source-bank-select" ${sourceDisabledAttr} ${sourceRequiredAttr} onchange="updateBlockRemarks(${blockIndex}); calculateFakeWallets();">
                            ${getBankOptionsHtml(direction === '-' ? selectedBankId : prefillSource, excludeSourceId)}
                        </select>
                        ${direction === '-' ? `<input type="hidden" name="transfers[${blockIndex}][source_bank_id]" value="${selectedBankId}">` : ''}
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success target-label mb-1">${targetLabel}</label>
                        <select name="transfers[${blockIndex}][target_bank_id]" class="form-select border-success target-bank-select" ${targetDisabledAttr} ${targetRequiredAttr} onchange="updateBlockRemarks(${blockIndex}); calculateFakeWallets();">
                            ${getBankOptionsHtml(direction === '+' ? selectedBankId : prefillTarget, excludeTargetId)}
                        </select>
                        ${direction === '+' ? `<input type="hidden" name="transfers[${blockIndex}][target_bank_id]" value="${selectedBankId}">` : ''}
                    </div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label small fw-bold text-muted mb-0">Transaction Entry Rows for this Target</label>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="addSubRow(${blockIndex})"><i class="bx bx-plus"></i> Add Entry Row</button>
                    </div>

                    <div class="sub-rows-container d-flex flex-column gap-3">
                        ${subRowsHtml}
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', blockHtml);

        if (prefillSource) {
            let srcSel = container.querySelector(`.transfer-block[data-block-id="${blockIndex}"] .source-bank-select`);
            if (srcSel) srcSel.value = prefillSource;
        }
        if (prefillTarget) {
            let tgtSel = container.querySelector(`.transfer-block[data-block-id="${blockIndex}"] .target-bank-select`);
            if (tgtSel) tgtSel.value = prefillTarget;
        }

        blockIndex++;
        updateBlockRemarks(blockIndex - 1);
        filterPurposeOptions();
        updateBlockRemoveButtons();
        calculateFakeWallets();
    }

    function removeTransferBlock(btn) {
        let blocks = document.querySelectorAll('.transfer-block');
        if (blocks.length > 1) {
            btn.closest('.transfer-block').remove();
            updateBlockRemoveButtons();
            calculateFakeWallets();
        } else {
            alert('At least one target bank transfer group is required for Own Account Transfers.');
        }
    }

    function updateBlockRemoveButtons() {
        let blocks = document.querySelectorAll('.transfer-block');
        blocks.forEach(b => {
            let rmBtn = b.querySelector('.remove-block-btn');
            if (rmBtn) {
                rmBtn.style.display = blocks.length === 1 ? 'none' : 'block';
            }
        });
    }

    function addSubRow(bIndex, itemData = null) {
        let blockEl = document.querySelector(`.transfer-block[data-block-id="${bIndex}"]`);
        if (!blockEl) return;
        let container = blockEl.querySelector('.sub-rows-container');
        let subRowCnt = container.querySelectorAll('.sub-row').length;

        let purposeOptionsWithSelected = purposeOptionsHtml;
        if (itemData && itemData.purpose_id) {
            purposeOptionsWithSelected = purposeOptionsWithSelected.replace(`value="${itemData.purpose_id}"`, `value="${itemData.purpose_id}" selected`);
        }

        let subRowHtml = `
            <div class="sub-row border p-3 rounded bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="text-muted small fw-bold">Entry Row #${subRowCnt + 1}</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger source-wallet-badge fs-6 px-2 py-1 text-nowrap" title="From Bank Running Balance">Src Bal: 0.00</span>
                        <span class="badge bg-success target-wallet-badge fs-6 px-2 py-1 text-nowrap" title="To Bank Running Balance">Target Bal: 0.00</span>
                        <button type="button" class="btn btn-danger btn-sm remove-sub-row-btn" onclick="removeSubRow(this)"><i class="bx bx-x"></i></button>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control sub-amount-input" name="transfers[${bIndex}][items][${subRowCnt}][amount]" placeholder="0.00" value="${itemData ? itemData.amount || '' : ''}" oninput="calculateFakeWallets()" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                        <select name="transfers[${bIndex}][items][${subRowCnt}][purpose_id]" class="form-select purpose-select" required>
                            <option value="" disabled ${!itemData || !itemData.purpose_id ? 'selected' : ''}>Select Purpose</option>
                            ${purposeOptionsWithSelected}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remark 1 (Auto-Fill)</label>
                        <input type="text" class="form-control bg-light remark-1-input" name="transfers[${bIndex}][items][${subRowCnt}][remark_1]" readonly placeholder="Auto-filled" value="${itemData ? itemData.remark_1 || '' : ''}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remark 2</label>
                        <input type="text" class="form-control" name="transfers[${bIndex}][items][${subRowCnt}][remark_2]" placeholder="Remark 2" value="${itemData ? itemData.remark_2 || '' : ''}">
                    </div>
                </div>
            </div>`;

        container.insertAdjacentHTML('beforeend', subRowHtml);
        updateBlockRemarks(bIndex);
        filterPurposeOptions();
        calculateFakeWallets();
    }

    function removeSubRow(btn) {
        let subRow = btn.closest('.sub-row');
        let container = subRow.closest('.sub-rows-container');
        if (container.querySelectorAll('.sub-row').length > 1) {
            subRow.remove();
            calculateFakeWallets();
        } else {
            alert('Each transfer group must retain at least one entry row.');
        }
    }

    function updateBlockRemarks(bIndex) {
        let blockEl = document.querySelector(`.transfer-block[data-block-id="${bIndex}"]`);
        if (!blockEl) return;

        let direction = document.getElementById('transfer_direction').value;
        let sourceSelect = blockEl.querySelector('.source-bank-select');
        let targetSelect = blockEl.querySelector('.target-bank-select');

        let sourceBankName = '';
        let targetBankName = '';

        if (sourceSelect && sourceSelect.selectedIndex > 0) {
            sourceBankName = sourceSelect.options[sourceSelect.selectedIndex].text.split(' (Balance:')[0];
        }

        if (targetSelect && targetSelect.selectedIndex > 0) {
            targetBankName = targetSelect.options[targetSelect.selectedIndex].text.split(' (Balance:')[0];
        }

        // Fallback to selected bank header if dropdown is disabled/omitted
        let selectedBankName = document.getElementById('selected_bank_name').value;
        if (direction === '-' && (!sourceBankName || sourceSelect.disabled)) {
            sourceBankName = selectedBankName;
        } else if (direction === '+' && (!targetBankName || targetSelect.disabled)) {
            targetBankName = selectedBankName;
        }

        // Determine Remark 1 text based on user requirements:
        // Bank Out (-): Source remark = "To [Target]", Target remark = "From [Source]"
        // Bank In (+): Source remark = "To [Target]", Target remark = "From [Source]" (or vice versa depending on input context)
        let remarkText = '';
        if (direction === '-') {
            remarkText = targetBankName ? 'To ' + targetBankName : '';
        } else {
            remarkText = sourceBankName ? 'From ' + sourceBankName : '';
        }

        blockEl.querySelectorAll('.remark-1-input').forEach(input => {
            if (!input.value || input.value.startsWith('From ') || input.value.startsWith('To ')) {
                input.value = remarkText;
            }
        });
    }

    function calculateFakeWallets() {
        let type = document.getElementById('type').value;
        let direction = document.getElementById('transfer_direction').value;
        let selectedBankId = document.getElementById('selected_bank_id_val').value;
        let selectedBankInitial = parseFloat(document.getElementById('selected_bank_initial_balance').value) || 0;

        if (type === 'own') {
            let bankRunningBalances = {};

            window.bankOptionsData.forEach(b => {
                bankRunningBalances[b.id] = parseFloat(b.balance) || 0;
            });
            if (selectedBankId) {
                bankRunningBalances[selectedBankId] = selectedBankInitial;
            }

            document.querySelectorAll('.transfer-block').forEach(block => {
                let sourceSelect = block.querySelector('.source-bank-select');
                let targetSelect = block.querySelector('.target-bank-select');

                let sourceId = sourceSelect ? sourceSelect.value : '';
                let targetId = targetSelect ? targetSelect.value : '';

                block.querySelectorAll('.sub-row').forEach(subRow => {
                    let input = subRow.querySelector('.sub-amount-input');
                    let amt = parseFloat(input ? input.value : 0) || 0;

                    if (sourceId) {
                        if (bankRunningBalances[sourceId] === undefined) {
                            bankRunningBalances[sourceId] = getBankBalance(sourceId);
                        }
                        bankRunningBalances[sourceId] -= amt;
                    }

                    if (targetId) {
                        if (bankRunningBalances[targetId] === undefined) {
                            bankRunningBalances[targetId] = getBankBalance(targetId);
                        }
                        bankRunningBalances[targetId] += amt;
                    }

                    let sourceBadge = subRow.querySelector('.source-wallet-badge');
                    let targetBadge = subRow.querySelector('.target-wallet-badge');

                    if (sourceBadge && sourceId) {
                        let currentSourceBal = bankRunningBalances[sourceId];
                        sourceBadge.innerText = 'Src Bal: ' + currentSourceBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        sourceBadge.className = 'badge fs-6 source-wallet-badge px-2 py-1 text-nowrap ' + (currentSourceBal >= 0 ? 'bg-danger' : 'bg-warning text-dark');
                    }
                    if (targetBadge && targetId) {
                        let currentTargetBal = bankRunningBalances[targetId];
                        targetBadge.innerText = 'Target Bal: ' + currentTargetBal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                });
            });
        } else {
            let runningBalance = selectedBankInitial;
            document.querySelectorAll('.customer-amount-input').forEach(input => {
                let amt = parseFloat(input.value) || 0;
                if (direction === '+') {
                    runningBalance += amt;
                } else {
                    runningBalance -= amt;
                }
                let badge = input.closest('.transaction-row').querySelector('.fake-wallet-badge');
                if (badge) {
                    badge.innerText = 'Bal: ' + runningBalance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    badge.className = 'badge fs-6 fake-wallet-badge px-3 py-2 text-nowrap ' + (runningBalance >= selectedBankInitial ? 'bg-success' : 'bg-warning text-dark');
                }
            });
        }
    }

    function handleTypeChange(isUserTriggered = false) {
        let type = document.getElementById('type').value;
        let direction = document.getElementById('transfer_direction').value;

        let customerRowsWrapper = document.getElementById('customer-rows-wrapper');
        let transferBlocksContainer = document.getElementById('transfer-blocks-container');
        let addTargetBankContainer = document.getElementById('addTargetBankContainer');

        let isEditMode = {{ isset($transaction) ? 'true' : 'false' }};

        if (!type || !direction) {
            customerRowsWrapper.style.display = 'none';
            transferBlocksContainer.style.display = 'none';
            if (addTargetBankContainer) addTargetBankContainer.style.display = 'none';
            return;
        }

        if (isUserTriggered && !isEditMode) {
            transferBlocksContainer.innerHTML = '';
            blockIndex = 0;

            let customerRowsContainer = document.getElementById('transaction-rows-container');
            customerRowsContainer.innerHTML = `
                <div class="transaction-row border p-3 rounded bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <span class="text-muted small fw-bold">Entry Row #1</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-dark fs-6 fake-wallet-badge px-3 py-2 text-nowrap" title="Fake Wallet Balance">Bal: 0.00</span>
                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeCustomerRow(this)"><i class="bx bx-x"></i></button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control customer-amount-input" name="items[0][amount]" placeholder="0.00" oninput="calculateFakeWallets()" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                            <select name="items[0][purpose_id]" class="form-select purpose-select" required>
                                <option value="" disabled selected>Select Purpose</option>
                                ${purposeOptionsHtml}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Remark 1</label>
                            <input type="text" class="form-control" name="items[0][remark_1]" placeholder="Remark 1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Remark 2</label>
                            <input type="text" class="form-control" name="items[0][remark_2]" placeholder="Remark 2">
                        </div>
                    </div>
                </div>`;
        }

        if (type === 'own') {
            customerRowsWrapper.style.display = 'none';
            transferBlocksContainer.style.display = 'block';
            if (addTargetBankContainer && !isEditMode) addTargetBankContainer.style.display = 'block';

            // Disable customer inputs so they are not submitted/validated
            customerRowsWrapper.querySelectorAll('input, select').forEach(el => {
                el.removeAttribute('required');
                el.disabled = true;
            });

            if (transferBlocksContainer.children.length === 0 && !isEditMode) {
                blockIndex = 0;
                addTransferBlock();
            } else {
                let selectedBankId = document.getElementById('selected_bank_id_val').value;
                let excludeSourceId = (direction === '+') ? selectedBankId : '';
                let excludeTargetId = (direction === '-') ? selectedBankId : '';

                document.querySelectorAll('.transfer-block').forEach((block, idx) => {
                    let sourceLbl = block.querySelector('.source-label');
                    let targetLbl = block.querySelector('.target-label');
                    let sourceSelect = block.querySelector('.source-bank-select');
                    let targetSelect = block.querySelector('.target-bank-select');

                    if (direction === '+') {
                        if (sourceLbl) sourceLbl.innerText = 'From Bank Account';
                        if (targetLbl) targetLbl.innerText = 'To Bank Account [Selected Bank]';
                        if (sourceSelect) {
                            let currentVal = sourceSelect.value;
                            sourceSelect.disabled = false;
                            sourceSelect.className = 'form-select border-danger source-bank-select';
                            sourceSelect.innerHTML = getBankOptionsHtml(currentVal, excludeSourceId);
                            sourceSelect.setAttribute('required', 'required');
                        }
                        if (targetSelect) {
                            targetSelect.disabled = true;
                            targetSelect.className = 'form-select border-success target-bank-select bg-light';
                            targetSelect.innerHTML = getBankOptionsHtml(selectedBankId, excludeTargetId);
                            targetSelect.removeAttribute('required');
                        }
                    } else {
                        if (sourceLbl) sourceLbl.innerText = 'From Bank Account [Selected Bank]';
                        if (targetLbl) targetLbl.innerText = 'To Bank Account';
                        if (sourceSelect) {
                            sourceSelect.disabled = true;
                            sourceSelect.className = 'form-select border-danger source-bank-select bg-light';
                            sourceSelect.innerHTML = getBankOptionsHtml(selectedBankId, excludeSourceId);
                            sourceSelect.removeAttribute('required');
                        }
                        if (targetSelect) {
                            let currentVal = targetSelect.value;
                            targetSelect.disabled = false;
                            targetSelect.className = 'form-select border-success target-bank-select';
                            targetSelect.innerHTML = getBankOptionsHtml(currentVal, excludeTargetId);
                            targetSelect.setAttribute('required', 'required');
                        }
                    }
                    updateBlockRemarks(idx);
                });
            }
        } else {
            customerRowsWrapper.style.display = 'block';
            transferBlocksContainer.style.display = 'none';
            if (addTargetBankContainer) addTargetBankContainer.style.display = 'none';

            // Disable transfer inputs so they are not submitted/validated
            transferBlocksContainer.querySelectorAll('input, select').forEach(el => {
                el.removeAttribute('required');
                el.disabled = true;
            });

            // Enable customer inputs
            customerRowsWrapper.querySelectorAll('input, select').forEach(el => {
                el.disabled = false;
                // Check if the input is NOT a remark field before setting required
                if (!el.name.includes('remark_1') && !el.name.includes('remark_2')) {
                    el.setAttribute('required', 'required');
                } else {
                    el.removeAttribute('required');
                }
            });
        }
        filterPurposeOptions();
        calculateFakeWallets();
    }

    function filterPurposeOptions() {
        let direction = document.getElementById('transfer_direction').value;
        document.querySelectorAll('.purpose-select').forEach(select => {
            for (let option of select.options) {
                if (!option.value) continue;
                let flowType = option.getAttribute('data-flow-type');
                if (direction === '+') {
                    option.style.display = (flowType === 'bank_in' || flowType === 'both') ? 'block' : 'none';
                } else if (direction === '-') {
                    option.style.display = (flowType === 'bank_out' || flowType === 'both') ? 'block' : 'none';
                } else {
                    option.style.display = 'block';
                }
            }
        });
    }

    function addCustomerRow() {
        let container = document.getElementById('transaction-rows-container');
        let rowCount = container.querySelectorAll('.transaction-row').length;
        let html = `
            <div class="transaction-row border p-3 rounded bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="text-muted small fw-bold">Entry Row #${rowCount + 1}</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark fs-6 fake-wallet-badge px-3 py-2 text-nowrap" title="Fake Wallet Balance">Bal: 0.00</span>
                        <button type="button" class="btn btn-danger btn-sm remove-row-btn" onclick="removeCustomerRow(this)"><i class="bx bx-x"></i></button>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control customer-amount-input" name="items[${rowCount}][amount]" placeholder="0.00" oninput="calculateFakeWallets()" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                        <select name="items[${rowCount}][purpose_id]" class="form-select purpose-select" required>
                            <option value="" disabled selected>Select Purpose</option>
                            ${purposeOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remark 1</label>
                        <input type="text" class="form-control" name="items[${rowCount}][remark_1]" placeholder="Remark 1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Remark 2</label>
                        <input type="text" class="form-control" name="items[${rowCount}][remark_2]" placeholder="Remark 2">
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        filterPurposeOptions();
        calculateFakeWallets();
    }

    function removeCustomerRow(btn) {
        let container = document.getElementById('transaction-rows-container');
        if (container.querySelectorAll('.transaction-row').length > 1) {
            btn.closest('.transaction-row').remove();
            calculateFakeWallets();
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        @if(old('type') === 'own' && empty($transaction))
            @php
                $oldTransfers = old('transfers', []);
            @endphp
            @if(!empty($oldTransfers))
                @foreach($oldTransfers as $tIndex =>$tBlock)
                    @php
                        $pSrc =$tBlock['source_bank_id'] ?? '';
                        $pTgt =$tBlock['target_bank_id'] ?? '';
                        $pItems =$tBlock['items'] ?? [];
                    @endphp
                    addTransferBlock("{{ $pSrc }}", "{{ $pTgt }}", @json($pItems));
                @endforeach
            @else
                addTransferBlock();
            @endif
        @endif

        handleTypeChange(false);
        filterPurposeOptions();
        calculateFakeWallets();
    });

    function handleFormSubmit() {
        if (typeof showLoading === 'function') {
            showLoading();
        }
        return true;
    }
</script>
@endsection

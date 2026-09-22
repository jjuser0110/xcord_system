@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb & Header Action Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('transaction.index') }}">Bank Settings</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('transaction.log', $transaction->bank_setting_id) }}">Transaction Logs</a></li>
                    <li class="breadcrumb-item active fw-bold">Edit Transaction</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Edit Transaction</h4>
        </div>

        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2">
            <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-wallet me-1"></i> Bank Settings
            </a>
            <a href="{{ route('transaction.log', $transaction->bank_setting_id) }}" class="btn btn-outline-primary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Transaction Logs
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('transaction.update', $transaction->id) }}" method="POST" id="transactionEditForm">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-12 alert alert-warning py-2 mb-2">
                        <i class="bx bx-info-circle me-1"></i> Transaction Date, Flow Type, Direction, Bank Accounts, and Purpose are locked.
                        @if($transaction->type === 'own')
                            For Own Account transactions, Remark 1 is also locked. You can only update the <strong>Amount</strong> and <strong>Remark 2</strong>.
                        @else
                            You can update the <strong>Amount</strong>, <strong>Remark 1</strong>, and <strong>Remark 2</strong>.
                        @endif
                    </div>

                    <!-- Selected Bank Account Display Header -->
                    <div class="col-12 alert alert-secondary py-2 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Selected Bank Account: </strong>
                            <span class="text-dark fw-bold">
                                {{ optional($transaction->bankSetting)->owner_name }} - {{ optional(optional($transaction->bankSetting)->bank)->short_name }}
                                <span class="badge bg-dark ms-2">Balance: {{ number_format(optional($transaction->bankSetting)->amount, 2) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Transaction Date -->
                    <div class="col-md-4">
                        <label class="form-label">Transaction Date</label>
                        <input type="date" class="form-control bg-light" value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d') }}" disabled>
                    </div>

                    <!-- Transaction Flow Type -->
                    <div class="col-md-4">
                        <label class="form-label">Transaction Flow Type</label>
                        <input type="text" class="form-control bg-light" value="{{ $transaction->type === 'own' ? 'Own Account Transfer' : 'External Transaction' }}" disabled>
                        <input type="hidden" name="type" value="{{ $transaction->type }}">
                    </div>

                    <!-- Direction -->
                    <div class="col-md-4">
                        <label class="form-label">Direction (Bank In / Out)</label>
                        <input type="text" class="form-control bg-light" value="{{ $transaction->transfer_direction === '+' ? 'Plus (+) [Bank In]' : 'Minus (-) [Bank Out]' }}" disabled>
                        <input type="hidden" name="transfer_direction" value="{{ $transaction->transfer_direction }}">
                    </div>
                </div>

                <hr>

                <!-- OWN ACCOUNT TRANSFER SECTION -->
                @if($transaction->type === 'own')
                    @php
                        $sourceBankId = $transaction->transfer_direction === '-' ? $transaction->bank_setting_id : $transaction->target_bank_setting_id;
                        $targetBankId = $transaction->transfer_direction === '+' ? $transaction->bank_setting_id : $transaction->target_bank_setting_id;

                        $sourceBank = \App\Models\BankSetting::with('bank')->find($sourceBankId);
                        $targetBank = \App\Models\BankSetting::with('bank')->find($targetBankId);
                    @endphp

                    <div class="border border-primary p-3 rounded mb-4 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-primary fw-bold m-0"><i class="bx bx-transfer me-1"></i> Own Account Transfer Details</h6>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger d-flex justify-content-between align-items-center">
                                    <span>From Bank Account</span>
                                    @if($sourceBankId == $transaction->bank_setting_id)
                                        <span class="badge bg-primary text-white">Selected Log Bank</span>
                                    @endif
                                </label>
                                <input type="text" class="form-control bg-light" value="{{ optional($sourceBank)->owner_name }} - {{ optional(optional($sourceBank)->bank)->short_name }} (Balance: {{ number_format(optional($sourceBank)->amount, 2) }})" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-success d-flex justify-content-between align-items-center">
                                    <span>To Bank Account</span>
                                    @if($targetBankId == $transaction->bank_setting_id)
                                        <span class="badge bg-primary text-white">Selected Log Bank</span>
                                    @endif
                                </label>
                                <input type="text" class="form-control bg-light" value="{{ optional($targetBank)->owner_name }} - {{ optional(optional($targetBank)->bank)->short_name }} (Balance: {{ number_format(optional($targetBank)->amount, 2) }})" disabled>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control" name="amount" value="{{ old('amount', $transaction->amount) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" value="{{ $transaction->purpose->title ?? '' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 1 (Locked)</label>
                                <!-- Remark 1 is readonly for own account transfers[cite: 19] -->
                                <input type="text" class="form-control bg-light" name="remark_1" value="{{ old('remark_1', $transaction->remark_1) }}" readonly placeholder="Remark 1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 2</label>
                                <input type="text" class="form-control" name="remark_2" value="{{ old('remark_2', $transaction->remark_2) }}" placeholder="Remark 2">
                            </div>
                        </div>
                    </div>

                <!-- EXTERNAL TRANSACTION SECTION -->
                @else
                    <div class="border p-3 rounded mb-4 bg-light">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <label class="form-label fw-bold mb-0">Bank Account: <span class="text-dark">{{ optional($transaction->bankSetting)->owner_name }} - {{ optional(optional($transaction->bankSetting)->bank)->short_name }} (Balance: {{ number_format(optional($transaction->bankSetting)->amount, 2) }})</span></label>
                            <span class="badge bg-primary text-white">Selected Log Bank</span>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="999999999999.99" class="form-control" name="amount" value="{{ old('amount', $transaction->amount) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Purpose <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" value="{{ $transaction->purpose->title ?? '' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 1 (Details/Customer Name)</label>
                                <!-- Remark 1 is fully editable for external transactions[cite: 19] -->
                                <input type="text" class="form-control" name="remark_1" value="{{ old('remark_1', $transaction->remark_1) }}" placeholder="Remark 1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Remark 2</label>
                                <input type="text" class="form-control" name="remark_2" value="{{ old('remark_2', $transaction->remark_2) }}" placeholder="Remark 2">
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Update Transaction</button>
                    <a href="{{ route('transaction.log', $transaction->bank_setting_id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

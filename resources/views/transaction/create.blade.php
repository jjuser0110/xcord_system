@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Record Transaction</h4>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('transaction.store') }}" method="POST" class="row g-3">
                @csrf

                <!-- Date -->
                <div class="col-md-6">
                    <label class="form-label" for="transaction_date">Transaction Date</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <!-- Transaction Type -->
                <div class="col-md-6">
                    <label class="form-label" for="type">Transaction Flow Type</label>
                    <select name="type" id="type" class="form-select" required onchange="handleTypeChange()">
                        <option value="" disabled selected>Select Type</option>
                        <option value="customer">Customer Transaction</option>
                        <option value="own">Own Account Transfer (Bank Out & Bank In)</option>
                    </select>
                </div>

                <!-- CUSTOMER FIELDS (Bank Account & Direction) - Hidden by default -->
                <div class="col-md-6 customer-fields" style="display: none;">
                    <label class="form-label" for="bank_setting_id">Bank Account</label>
                    <select name="bank_setting_id" id="bank_setting_id" class="form-select">
                        <option value="" disabled selected>Select Bank Account</option>
                        @foreach($bankSettings as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank->bank_name }} - {{ $bank->account_no }} ({{ $bank->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 customer-fields" style="display: none;">
                    <label class="form-label" for="transfer_direction">Direction</label>
                    <select name="transfer_direction" id="transfer_direction" class="form-select">
                        <option value="+">Plus (+) [Bank In]</option>
                        <option value="-">Minus (-) [Bank Out]</option>
                    </select>
                </div>

                <!-- CUSTOMER DETAILS SECTION - Hidden by default -->
                <div class="col-12 customer-fields card border-light bg-label-secondary p-3 mt-3 mb-2" style="display: none;">
                    <h6 class="fw-bold text-primary mb-3">Customer Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_name">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Enter customer name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="customer_phone">Customer Phone</label>
                            <input type="text" name="customer_phone" class="form-control" placeholder="Phone number">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="customer_address">Customer Address</label>
                            <textarea name="customer_address" class="form-control" rows="2" placeholder="Customer address details"></textarea>
                        </div>
                    </div>
                </div>

                <!-- OWN ACCOUNT FIELDS -->
                <div class="col-md-6 own-fields" style="display: none;">
                    <label class="form-label fw-bold text-danger" for="source_bank_id">From Bank Account (Bank Out -)</label>
                    <select name="source_bank_id" id="source_bank_id" class="form-select border-danger">
                        <option value="" disabled selected>Select Source Bank Acc</option>
                        @foreach($bankSettings as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank->bank_name }} - {{ $bank->account_no }} ({{ $bank->type }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 own-fields" style="display: none;">
                    <label class="form-label fw-bold text-success" for="target_bank_id">To Bank Account (Bank In +)</label>
                    <select name="target_bank_id" id="target_bank_id" class="form-select border-success">
                        <option value="" disabled selected>Select Target Bank Acc</option>
                        @foreach($bankSettings as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank->bank_name }} - {{ $bank->account_no }} ({{ $bank->type }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount -->
                <div class="col-md-6">
                    <label class="form-label" for="amount">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
                </div>

                <!-- Purpose -->
                <div class="col-md-6">
                    <label class="form-label" for="purpose_id">Purpose</label>
                    <select name="purpose_id" class="form-select" required>
                        <option value="" disabled selected>Select Purpose</option>
                        @foreach($purposes as $purpose)
                            <option value="{{ $purpose->id }}">{{ $purpose->title }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Color Palette Picker -->
                <div class="col-12">
                    <label class="form-label d-block fw-semibold">Row Highlight Color Status</label>
                    <div class="d-flex flex-wrap gap-2 pt-1">
                        @php
                            $palette = [
                                'white'      => ['label' => 'White (Default)', 'hex' => '#ffffff'],
                                'red'        => ['label' => 'Red (Stop Use)', 'hex' => '#ff3e1d'],
                                'pink'       => ['label' => 'Pink (Less Use)', 'hex' => '#e83e8c'],
                                'blue'       => ['label' => 'Blue', 'hex' => '#03c3ec'],
                                'lightblue'  => ['label' => 'Light Blue', 'hex' => '#7ad3ff'],
                                'green'      => ['label' => 'Green (Active)', 'hex' => '#71dd37'],
                                'lightgreen' => ['label' => 'Light Green (Feeding)', 'hex' => '#b1f08a'],
                            ];
                        @endphp

                        @foreach($palette as $val => $info)
                            <label class="m-0 cursor-pointer">
                                <input type="radio" name="column_color" value="{{ $info['hex'] }}" class="color-option-input d-none" {{ $val == 'white' ? 'checked' : '' }} required>
                                <span class="color-swatch-card d-inline-flex align-items-center gap-2 p-2 border rounded bg-white shadow-sm">
                                    <span class="color-swatch-circle rounded-circle border" style="width: 20px; height: 20px; background-color: {{ $info['hex'] }}; display: inline-block;"></span>
                                    <span class="fw-medium text-dark small">{{ $info['label'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Remarks (Textareas) -->
                <div class="col-md-6">
                    <label class="form-label" for="remark_1">Remark 1</label>
                    <textarea name="remark_1" class="form-control" rows="2" placeholder="Primary note"></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="remark_2">Remark 2</label>
                    <textarea name="remark_2" class="form-control" rows="2" placeholder="Secondary note"></textarea>
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                    <a href="{{ route('transaction.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .color-option-input:checked + .color-swatch-card {
        border-color: #696cff !important;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25) !important;
        background-color: #f8f9fa !important;
    }
</style>

<script>
function handleTypeChange() {
    let type = document.getElementById('type').value;
    let customerFields = document.querySelectorAll('.customer-fields');
    let ownFields = document.querySelectorAll('.own-fields');

    if (type === 'own') {
        customerFields.forEach(el => el.style.display = 'none');
        ownFields.forEach(el => el.style.display = 'block');

        document.getElementById('bank_setting_id').removeAttribute('required');
        document.getElementById('transfer_direction').removeAttribute('required');
        document.getElementById('customer_name').removeAttribute('required');

        document.getElementById('source_bank_id').setAttribute('required', 'required');
        document.getElementById('target_bank_id').setAttribute('required', 'required');
    } else if (type === 'customer') {
        customerFields.forEach(el => el.style.display = 'block');
        ownFields.forEach(el => el.style.display = 'none');

        document.getElementById('bank_setting_id').setAttribute('required', 'required');
        document.getElementById('transfer_direction').setAttribute('required', 'required');
        document.getElementById('customer_name').setAttribute('required', 'required');

        document.getElementById('source_bank_id').removeAttribute('required');
        document.getElementById('target_bank_id').removeAttribute('required');
    }
}
</script>
@endsection

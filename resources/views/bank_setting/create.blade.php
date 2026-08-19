@extends('layouts.app')

@section('content')
<style>
    .color-option-input { display: none; }
    .color-swatch-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px 16px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        user-select: none;
    }
    .color-swatch-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }
    .color-swatch-circle {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-block;
        border: 1px solid rgba(0,0,0,0.15);
    }
    .color-option-input:checked + .color-swatch-card {
        border-color: #696cff !important;
        box-shadow: 0 0 0 3px rgba(105, 108, 255, 0.25);
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="breadcrumb-wrapper m-0">
            <a class="text-muted fw-light" href="{{route('bank_setting.index')}}">Bank Account /</a>
            @if (isset($bank_setting)) Edit @else Create @endif
        </h4>
        @if (isset($bank_setting))
            <a href="{{ route('bank_setting.log', $bank_setting) }}" class="btn btn-info text-white">
                <i class="bx bx-history me-1"></i> History Log
            </a>
        @endif
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Bank Account Details</h5>
                <div class="card-body">
                    <form class="row g-3" enctype="multipart/form-data"
                          @if (isset($bank_setting))
                              method="post" action="{{ route('bank_setting.update', $bank_setting) }}"
                          @else
                              method="post" action="{{ route('bank_setting.store') }}"
                          @endif
                          onsubmit="return validateBankForm()">
                    @csrf

                    <!-- Bank Selection -->
                    <div class="col-md-6">
                        <label class="form-label" for="bank_id">Bank</label>
                        <select name="bank_id" id="bank_id" class="form-select" required onchange="updateCountry()">
                            <option value="" disabled selected>Select Bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}"
                                        data-country-id="{{ $bank->country_id ?? '' }}"
                                    {{ (isset($bank_setting) && $bank_setting->bank_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->bank_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Country Selection (Disabled so user cannot change it manually; uses hidden input to pass value) -->
                    <div class="col-md-6">
                        <label class="form-label" for="country_id_display">Country (Auto-selected)</label>
                        <select id="country_id_display" class="form-select bg-light" disabled>
                            <option value="" disabled selected>Select Bank First</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <!-- Hidden input to submit the actual country_id value through the form -->
                        <input type="hidden" name="country_id" id="country_id" value="{{ $bank_setting->country_id ?? '' }}">
                    </div>

                    <!-- Account Type (Acc A, Acc B, Acc C) -->
                    <div class="col-md-6">
                        <label class="form-label" for="type">Account Category / Type</label>
                        <input type="text" class="form-control" placeholder="Saving Account" name="type" value="{{ $bank_setting->type ?? '' }}"/>
                    </div>

                    <!-- Account Number -->
                    <div class="col-md-6">
                        <label class="form-label" for="account_no">Account No</label>
                        <input type="text" class="form-control" placeholder="1234567890" name="account_no" value="{{ $bank_setting->account_no ?? '' }}" required/>
                    </div>

                    <!-- Owner Name -->
                    <div class="col-md-6">
                        <label class="form-label" for="owner_name">Owner Name</label>
                        <input type="text" class="form-control" placeholder="John Doe" name="owner_name" value="{{ $bank_setting->owner_name ?? '' }}" required/>
                    </div>

                    <!-- Capital -->
                    <div class="col-md-6">
                        <label class="form-label" for="capital">Capital</label>
                        <input type="number" step="0.01" min="0" id="capital" class="form-control" placeholder="0.00" name="capital" value="{{ $bank_setting->capital ?? '' }}" required/>
                        <div id="capital-error" class="text-danger small mt-1" style="display: none;">Capital cannot be negative or empty.</div>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label class="form-label" for="phone_number">Phone Number</label>
                        <input type="text" id="phone_number" class="form-control" placeholder="+60123456789" name="phone_number" value="{{ $bank_setting->phone_number ?? '' }}" required/>
                        <div id="phone-error" class="text-danger small mt-1" style="display: none;">Please enter a valid phone number.</div>
                    </div>

                    <!-- Expired / Use Date -->
                    <div class="col-md-6">
                        <label class="form-label" for="expired_date">Expired / Use Date</label>
                        <input type="date" id="expired_date" class="form-control" name="expired_date" value="{{ $bank_setting->expired_date ?? '' }}" required/>
                        <div id="date-error" class="text-danger small mt-1" style="display: none;">Warning: The expiration date is set in the past.</div>
                    </div>

                    <!-- Visual Color Palette Picker -->
                    <div class="col-12">
                        <label class="form-label d-block fw-semibold">Account Color Status</label>
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
                                $selectedColor = isset($bank_setting) ? $bank_setting->color : 'white';
                            @endphp

                            @foreach($palette as $val => $info)
                                <label class="m-0">
                                    <input type="radio" name="color" value="{{ $val }}" class="color-option-input"
                                           {{ $selectedColor == $val ? 'checked' : '' }} required>
                                    <span class="color-swatch-card">
                                        <span class="color-swatch-circle" style="background-color: {{ $info['hex'] }};"></span>
                                        <span class="fw-medium text-dark small">{{ $info['label'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr>
                    <div class="col-12">
                        <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updateCountry() {
        let bankSelect = document.getElementById('bank_id');
        let selectedOption = bankSelect.options[bankSelect.selectedIndex];
        let countryId = selectedOption.getAttribute('data-country-id');

        // Set value to both display dropdown and hidden field
        document.getElementById('country_id_display').value = countryId;
        document.getElementById('country_id').value = countryId;
    }

    // Run on page load in case it's edit mode
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('bank_id').value) {
            updateCountry();
        }
    });

    function validateBankForm() {
        let isValid = true;

        let capitalInput = document.getElementById('capital');
        let capitalError = document.getElementById('capital-error');
        if (capitalInput.value === '' || parseFloat(capitalInput.value) < 0) {
            capitalError.style.display = 'block';
            isValid = false;
        } else {
            capitalError.style.display = 'none';
        }

        let phoneInput = document.getElementById('phone_number');
        let phoneError = document.getElementById('phone-error');
        let phoneVal = phoneInput.value.trim();
        let phoneRegex = /^[0-9+\-\s()]{7,20}$/;
        if (phoneVal !== '' && !phoneRegex.test(phoneVal)) {
            phoneError.style.display = 'block';
            isValid = false;
        } else {
            phoneError.style.display = 'none';
        }

        let dateInput = document.getElementById('expired_date');
        if (dateInput.value !== '') {
            let selectedDate = new Date(dateInput.value);
            let today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                let confirmPast = confirm("The expiration date selected is in the past. Do you still want to proceed?");
                if (!confirmPast) {
                    isValid = false;
                }
            }
        }

        if (isValid && typeof showLoading === 'function') {
            showLoading();
        }

        return isValid;
    }
</script>
@endsection

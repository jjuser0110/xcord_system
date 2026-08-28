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
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Bank Account Details</h5>
                <div class="card-body">
                    <form class="row g-3" method="post"
                          action="{{ isset($bank_setting) ? route('bank_setting.update', $bank_setting) : route('bank_setting.store') }}">
                    @csrf

                    <!-- Bank Selection (Locked on Edit) -->
                    <div class="col-md-6">
                        <label class="form-label">Bank</label>
                        <select name="bank_id" id="bank_id" class="form-select {{ isset($bank_setting) ? 'bg-light' : '' }}" required
                                {{ isset($bank_setting) ? 'disabled' : '' }} onchange="updateCountry()">
                            <option value="" disabled selected>Select Bank</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" data-country-id="{{ $bank->country_id ?? '' }}"
                                    {{ (isset($bank_setting) && $bank_setting->bank_id == $bank->id) ? 'selected' : '' }}>
                                    {{ $bank->bank_name }}
                                </option>
                            @endforeach
                        </select>
                        @if(isset($bank_setting))
                            <input type="hidden" name="bank_id" value="{{ $bank_setting->bank_id }}">
                        @endif
                    </div>

                    <!-- Country Selection (Locked on Edit) -->
                    <div class="col-md-6">
                        <label class="form-label">Country (Auto-selected)</label>
                        <select id="country_id_display" class="form-select bg-light" disabled>
                            <option value="" disabled selected>Select Bank First</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ (isset($bank_setting) && $bank_setting->country_id == $country->id) ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="country_id" id="country_id" value="{{ $bank_setting->country_id ?? '' }}">
                    </div>

                    <!-- Owner Name (Locked on Edit) -->
                    <div class="col-md-6">
                        <label class="form-label">Owner Name</label>
                        <input type="text" class="form-control {{ isset($bank_setting) ? 'bg-light' : '' }}"
                               name="owner_name" value="{{ $bank_setting->owner_name ?? '' }}"
                               {{ isset($bank_setting) ? 'readonly' : 'required' }} />
                    </div>

                    <!-- Capital / Initial Balance -->
                    <div class="col-md-6">
                        <label class="form-label">Capital</label>
                        <input type="number" step="0.01" min="0" class="form-control {{ isset($bank_setting) ? 'bg-light' : '' }}"
                               name="capital" value="{{ $bank_setting->capital ?? '0.00' }}"
                               {{ isset($bank_setting) ? 'readonly' : 'required' }} />
                    </div>

                    <!-- Current Amount Balance (Visible only on Edit) -->
                    @if(isset($bank_setting))
                    <div class="col-md-6">
                        <label class="form-label">Current Amount Balance</label>
                        <input type="number" step="0.01" class="form-control bg-light"
                               value="{{ $bank_setting->amount }}" disabled />
                    </div>
                    @endif

                    <hr class="mt-4">

                    <!-- Multiple Phone Numbers & Expiration Manager -->
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0">Phone Numbers & Expiration Dates (Optional)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPhoneRow()">
                                <i class="bx bx-plus"></i> Add Phone Row
                            </button>
                        </div>
                        <div id="phone-rows-container">
                            @if(isset($bank_setting) && $bank_setting->phoneNumbers->count() > 0)
                                @foreach($bank_setting->phoneNumbers as $index => $phone)
                                    <div class="row phone-row g-2 mb-2 align-items-center">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="phones[{{ $index }}][phone_number]" placeholder="Phone Number (e.g. +60123456789)" value="{{ $phone->phone_number }}">
                                        </div>
                                        <div class="col-md-5">
                                            <input type="date" class="form-control" name="phones[{{ $index }}][expired_date]" value="{{ $phone->expired_date }}">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-sm w-100 h-100 remove-phone-btn" onclick="removePhoneRow(this)">Remove</button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="row phone-row g-2 mb-2 align-items-center">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="phones[0][phone_number]" placeholder="Phone Number (e.g. +60123456789)">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="date" class="form-control" name="phones[0][expired_date]">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm w-100 h-100 remove-phone-btn" onclick="removePhoneRow(this)">Remove</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Visual Color Palette Picker -->
                    <div class="col-12 mt-4">
                        <label class="form-label d-block fw-semibold">Account Color Status</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            @php
                                $palette = [
                                    'white'      => ['label' => 'White (Default)', 'hex' => '#ffffff'],
                                    'red'        => ['label' => 'Red (Stop Use)', 'hex' => '#ff3e1d'],
                                    'pink'       => ['label' => 'Pink (Less Use)', 'hex' => '#ffdbeb'],
                                    'blue'       => ['label' => 'Blue', 'hex' => '#2a5d96'],
                                    'lightblue'  => ['label' => 'Light Blue', 'hex' => '#7ad3ff'],
                                    'green'      => ['label' => 'Green (Active)', 'hex' => '#6aae46'],
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
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let phoneIndex = {{ isset($bank_setting) ? max(1, $bank_setting->phoneNumbers->count()) : 1 }};

    function addPhoneRow() {
        let container = document.getElementById('phone-rows-container');
        let rowHtml = `
            <div class="row phone-row g-2 mb-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="phones[${phoneIndex}][phone_number]" placeholder="Phone Number">
                </div>
                <div class="col-md-5">
                    <input type="date" class="form-control" name="phones[${phoneIndex}][expired_date]">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm w-100 h-100 remove-phone-btn" onclick="removePhoneRow(this)">Remove</button>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', rowHtml);
        phoneIndex++;
        updateRemoveButtonStates();
    }

    function removePhoneRow(button) {
        let rows = document.querySelectorAll('.phone-row');
        if (rows.length > 1) {
            button.closest('.phone-row').remove();
            updateRemoveButtonStates();
        }
    }

    function updateRemoveButtonStates() {
        let rows = document.querySelectorAll('.phone-row');
        let removeButtons = document.querySelectorAll('.remove-phone-btn');

        removeButtons.forEach(btn => {
            if (rows.length === 1) {
                btn.setAttribute('disabled', 'true');
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                btn.removeAttribute('disabled');
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    function updateCountry() {
        let bankSelect = document.getElementById('bank_id');
        let selectedOption = bankSelect.options[bankSelect.selectedIndex];
        let countryId = selectedOption.getAttribute('data-country-id');
        document.getElementById('country_id_display').value = countryId;
        document.getElementById('country_id').value = countryId;
    }

    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('bank_id').value) {
            updateCountry();
        }
        updateRemoveButtonStates();
    });
</script>
@endsection

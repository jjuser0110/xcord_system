@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('purpose.index')}}">Purpose /</a>
         @if (isset($purpose)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Purpose Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($purpose)) method="post" action="{{ route('purpose.update',$purpose) }}" @else method="post" action="{{ route('purpose.store') }}" @endif onsubmit="showLoading()">
                @csrf

                <div class="col-md-6">
                    <label class="form-label" for="title">Purpose Title</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Deposit"
                    name="title"
                    value="{{$purpose->title??''}}"
                    required/>
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Country Assignment</label>

                    <!-- Global Checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1"
                            {{ (isset($purpose) && $purpose->is_global) ? 'checked' : '' }} onchange="handleGlobalChange()">
                        <label class="form-check-label fw-bold text-primary" for="is_global">
                            Link to All Countries (Global)
                        </label>
                    </div>

                    <!-- Checkboxes for individual countries -->
                    <div class="row" id="country-checkboxes-container">
                        @php
                            $selectedCountryIds = isset($selectedCountries) ? $selectedCountries : [];
                        @endphp
                        @foreach($countries as $country)
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input country-checkbox" type="checkbox" name="country_ids[]" value="{{ $country->id }}" id="country_{{ $country->id }}"
                                        {{ in_array($country->id, $selectedCountryIds) ? 'checked' : '' }} onchange="handleCountryCheckboxChange()">
                                    <label class="form-check-label" for="country_{{ $country->id }}">
                                        {{ $country->name }} ({{ $country->currency_code }})
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Money Flow Type Checkboxes -->
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold d-block">Money Flow Type <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4">
                        @php
                            // Handle existing values whether stored as an array, string, or comma-separated/json
                            $selectedFlows = old('money_flow_type', isset($purpose) ? (is_array($purpose->money_flow_type) ? $purpose->money_flow_type : [$purpose->money_flow_type]) : []);
                            if(in_array('both', $selectedFlows)) {
                                $selectedFlows = ['bank_in', 'bank_out']; // 'both' covers both directions
                            }
                        @endphp

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="money_flow_type[]" id="flow_bank_in" value="bank_in"
                                {{ in_array('bank_in', $selectedFlows) ? 'checked' : '' }}
                                {{ isset($purpose) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="flow_bank_in">Bank In</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="money_flow_type[]" id="flow_bank_out" value="bank_out"
                                {{ in_array('bank_out', $selectedFlows) ? 'checked' : '' }}
                                {{ isset($purpose) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="flow_bank_out">Bank Out</label>
                        </div>
                    </div>

                    @if(isset($purpose))
                        @foreach((array)$purpose->money_flow_type as $val)
                            <input type="hidden" name="money_flow_type[]" value="{{ $val }}">
                        @endforeach
                        <small class="text-muted">Money flow type cannot be modified after creation.</small>
                    @else
                        <small class="text-muted">Select at least one money flow type.</small>
                    @endif
                </div>

                <!-- Provider Settlement Option Section -->
                <hr>
                <div class="col-12">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="has_provider_settlement" name="has_provider_settlement" value="1"
                            {{ (isset($purpose) && $purpose->has_provider_settlement) ? 'checked' : '' }}
                            {{ (isset($hasTransactions) && $hasTransactions) ? 'disabled' : '' }} onchange="handleProviderChange()">
                        <label class="form-check-label fw-bold text-dark" for="has_provider_settlement">
                            Show on Provider IN/OUT
                        </label>
                        @if(isset($hasTransactions) && $hasTransactions)
                            <div class="text-danger small mt-1">Provider settlement settings cannot be changed because transactions have already been recorded using this purpose.</div>
                        @endif
                    </div>

                    <div class="mb-3" id="provider-name-container" style="{{ (isset($purpose) && $purpose->has_provider_settlement) ? '' : 'display: none;' }}">
                        <label class="form-label" for="provider_name">Provider Name</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="e.g. RM, FIUU"
                            id="provider_name"
                            name="provider_name"
                            value="{{ $purpose->provider_name ?? '' }}"
                            {{ (isset($hasTransactions) && $hasTransactions) ? 'readonly' : '' }} />
                    </div>
                </div>

                <!-- Dashboard Report Mappings -->
                <div class="col-12" id="dashboard-mappings-container" style="{{ (isset($purpose) && $purpose->has_provider_settlement) ? '' : 'display: none;' }}">
                    <hr>
                    <label class="form-label fw-bold text-dark mb-2">Provider Settlement Options</label>

                    <!-- Group 1: Provider Settlement Mappings -->
                    <div class="p-3 border rounded bg-light mb-3">
                        <span class="d-block fw-semibold text-muted small mb-2 text-uppercase">Provider Settlement Options</span>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_on_received_from_provider" name="show_on_received_from_provider" value="1"
                                        {{ (isset($purpose) && $purpose->show_on_received_from_provider) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_on_received_from_provider">
                                        Dashboard Received from Provider
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_on_topup_to_provider" name="show_on_topup_to_provider" value="1"
                                        {{ (isset($purpose) && $purpose->show_on_topup_to_provider) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_on_topup_to_provider">
                                        Dashboard Topup to Provider
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Group 2: Transfer for Merchant (Stays Alone) -->
                <div class="col-12">
                    <hr>
                    <span class="d-block fw-semibold text-muted small mb-2 text-uppercase">Merchant Settlement Options</span>
                    <div class="p-3 border rounded bg-light">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_on_transfer_for_merchant" name="show_on_transfer_for_merchant" value="1"
                                        {{ (isset($purpose) && $purpose->show_on_transfer_for_merchant) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="show_on_transfer_for_merchant">
                                        Dashboard Transfer for Merchant
                                    </label>
                                </div>
                            </div>
                        </div>
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
<!-- / Content -->
@endsection

@section('scripts')
<script>
    function handleGlobalChange() {
        const isGlobal = document.getElementById('is_global').checked;
        const checkboxes = document.querySelectorAll('.country-checkbox');

        checkboxes.forEach(cb => {
            if (isGlobal) {
                cb.checked = false;
                cb.disabled = true;
            } else {
                cb.disabled = false;
            }
        });
    }

    function handleCountryCheckboxChange() {
        const checkboxes = document.querySelectorAll('.country-checkbox');
        const isGlobalCheckbox = document.getElementById('is_global');

        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

        if (anyChecked) {
            isGlobalCheckbox.checked = false;
            isGlobalCheckbox.disabled = true;
        } else {
            isGlobalCheckbox.disabled = false;
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('is_global').checked) {
            handleGlobalChange();
        } else {
            handleCountryCheckboxChange();
        }

        // Trigger on load to match initial state correctly
        handleProviderChange();
    });

    function handleProviderChange() {
        const isChecked = document.getElementById('has_provider_settlement').checked;
        const container = document.getElementById('provider-name-container');
        const inputField = document.getElementById('provider_name');
        const dashboardMappings = document.getElementById('dashboard-mappings-container');

        if (isChecked) {
            container.style.display = 'block';
            dashboardMappings.style.display = 'block';
            inputField.required = true;
        } else {
            container.style.display = 'none';
            dashboardMappings.style.display = 'none';
            inputField.required = false;
            inputField.value = '';

            // Optionally uncheck the provider-related mappings if provider settlement is unchecked
            document.getElementById('show_on_received_from_provider').checked = false;
            document.getElementById('show_on_topup_to_provider').checked = false;
        }
    }
</script>
@endsection

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

                {{-- <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea
                    class="form-control"
                    placeholder="Enter description here..."
                    name="description"
                    rows="4">{{$purpose->description??''}}</textarea>
                </div> --}}
                <!-- Provider Settlement Option Section -->
                <!-- Provider Settlement Option Section -->
                <hr>
                <div class="col-12">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="has_provider_settlement" name="has_provider_settlement" value="1"
                            {{ (isset($purpose) && $purpose->has_provider_settlement) ? 'checked' : '' }}
                            {{ (isset($hasTransactions) && $hasTransactions) ? 'disabled' : '' }} onchange="handleProviderChange()">
                        <label class="form-check-label fw-bold text-dark" for="has_provider_settlement">
                            Enable Provider Settlement for this Purpose
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
    });

    function handleProviderChange() {
        const isChecked = document.getElementById('has_provider_settlement').checked;
        const container = document.getElementById('provider-name-container');
        const inputField = document.getElementById('provider_name');

        if (isChecked) {
            container.style.display = 'block';
            inputField.required = true;
        } else {
            container.style.display = 'none';
            inputField.required = false;
            inputField.value = '';
        }
    }
</script>
@endsection

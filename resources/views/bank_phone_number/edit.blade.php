@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">
            <a href="{{ route('bank_phone_number.index') }}">Bank Phone Numbers</a> /
        </span> Edit
    </h4>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">Edit Bank Phone Number</h5>
                <div class="card-body">
                    <!-- Bank Account Info Display Header -->
                    <div class="alert alert-secondary py-2 mb-4">
                        <strong>Bank Account: </strong>
                        <span class="text-dark fw-bold">
                            {{ $bank_phone_number->bankSetting->owner_name ?? '-' }} - {{ $bank_phone_number->bankSetting->bank->short_name ?? '-' }}
                        </span>
                    </div>

                    <form class="row g-3" method="POST" action="{{ route('bank_phone_number.update', $bank_phone_number) }}" onsubmit="showLoading()">
                        @csrf

                        <!-- Contact Number -->
                        <div class="col-md-6">
                            <label class="form-label" for="phone_number">Contact Number <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="phone_number"
                                name="phone_number"
                                value="{{ old('phone_number', $bank_phone_number->phone_number) }}"
                                required />
                        </div>

                        <!-- Expired Date -->
                        <div class="col-md-6">
                            <label class="form-label" for="expired_date">Expired Date</label>
                            <input
                                type="date"
                                class="form-control"
                                id="expired_date"
                                name="expired_date"
                                value="{{ old('expired_date', $bank_phone_number->expired_date ? \Carbon\Carbon::parse($bank_phone_number->expired_date)->format('Y-m-d') : '') }}" />
                        </div>

                        <hr>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('bank_phone_number.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

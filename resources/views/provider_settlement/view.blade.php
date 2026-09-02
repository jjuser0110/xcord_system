@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{ route('provider_settlement.index') }}">Provider Settlement /</a> Details
    </h4>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Provider Settlement Details </h5>
                    <a href="{{ route('provider_settlement.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to Listing
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <!-- Provider Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Provider Name</label>
                            <input type="text" class="form-control" value="{{ $providerSettlement->provider_name ?? '-' }}" readonly disabled />
                        </div>

                        <!-- Settlement Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Settlement Amount</label>
                            <input type="text" class="form-control text-success {{ $providerSettlement->settlement_amount >= 0 ? 'text-success' : 'text-danger' }}" value="{{ $providerSettlement->settlement_amount >= 0 ? '+ ' : '- ' }}{{ number_format(abs($providerSettlement->settlement_amount), 2) }}" readonly disabled />
                        </div>

                        <!-- Purpose -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Purpose</label>
                            <input type="text" class="form-control" value="{{ $providerSettlement->purpose->title ?? '-' }}" readonly disabled />
                        </div>

                        <!-- Bank Account Setting -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bank Setting / Bank Name</label>
                            @php
                                $bankSetting = $providerSettlement->transaction?->bankSetting;
                                $bankLabel = $bankSetting ? ($bankSetting->owner_name . ' - ' . ($bankSetting->bank?->short_name ?? '-')) : ($providerSettlement->bank_name ?? '-');
                            @endphp
                            <input type="text" class="form-control" value="{{ $bankLabel }}" readonly disabled />
                        </div>

                        <!-- Country -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Country</label>
                            <input type="text" class="form-control" value="{{ $providerSettlement->country->name ?? '-' }}" readonly disabled />
                        </div>

                        <!-- Transaction Reference No -->
                        {{-- <div class="col-md-6">
                            <label class="form-label fw-bold">Transaction Number</label>
                            <input type="text" class="form-control font-monospace text-primary" value="{{ $providerSettlement->transaction?->transaction_no ?? '-' }}" readonly disabled />
                        </div> --}}

                        <!-- Created At -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Created At</label>
                            <input type="text" class="form-control" value="{{ $providerSettlement->created_at->format('d/m/Y H:i') }}" readonly disabled />
                        </div>

                        <!-- Created By -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Created By</label>
                            <input type="text" class="form-control" value="{{ $providerSettlement->created_by?->name ?? $providerSettlement->created_by?->username ?? '-' }}" readonly disabled />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

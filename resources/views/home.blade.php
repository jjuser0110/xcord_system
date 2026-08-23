@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Financial /</span> Dashboard</h4>
            </div>
            <div>
                <span class="badge bg-label-primary fs-6">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </div>

    <!-- 1. Daily Performance Metrics Cards -->
    <div class="row">
        <!-- Transfer to Own Bank (Today) -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-export"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($todayTransferToOwn ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Transfer to Own Bank</p>
                    <p class="mb-0">
                        <small class="text-muted">Today's Total Outflow</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Received from Own Bank (Today) -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-import"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($todayReceiveFromOwn ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Received from Own Bank</p>
                    <p class="mb-0">
                        <small class="text-muted">Today's Total Inflow</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Transfer to Customer $$ (Today) -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-money"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($todayTransferToCustomer ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Transfer to Customer $$</p>
                    <p class="mb-0">
                        <small class="text-muted">Today's Withdrawals</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Receive from Customer $$ (Today) -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-wallet"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($todayReceiveFromCustomer ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Receive from Customer $$</p>
                    <p class="mb-0">
                        <small class="text-muted">Today's Deposits</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Additional Finance Insights (Monthly Summaries & Quick Stats) -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">Monthly Capital Performance</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-primary text-primary"><i class="bx bx-pie-chart-alt"></i></span>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">Total System Capital</small>
                                    <h5 class="mb-0 fw-semibold">{{ number_format($totalSystemCapital ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-success text-success"><i class="bx bx-transfer"></i></span>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">Total Monthly Volume</small>
                                    <h5 class="mb-0 fw-semibold">{{ number_format($monthlyVolume ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Quick Actions</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-around">
                    <a href="{{ route('transaction.create') }}" class="btn btn-primary w-100 mb-2">
                        <i class="bx bx-plus me-1"></i> New Transaction
                    </a>
                    <a href="{{ route('transaction.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bx bx-list-ul me-1"></i> View Transaction Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Bank Accounts Capital Overview -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Bank Accounts Capital Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($bankSettings as $bankSetting)
                            @php
                                $bgColor = strtolower(trim($bankSetting->color ?? '#696cff'));

                                // Define common light background keywords or hex codes
                                $lightColors = ['white', '#ffffff', '#fff', '#f8f9fa', '#e9ecef', '#d1e7dd', '#fff3cd', '#f8d7da', '#cff4fc'];

                                // Check if it's explicitly in our list, or starts with a light hex code pattern (rough brightness check or direct match)
                                $isLightBg = in_array($bgColor, $lightColors) || str_starts_with($bgColor, '#f') || str_starts_with($bgColor, '#e');
                            @endphp
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="card p-3 h-100 shadow-sm {{ $isLightBg ? 'text-dark border' : 'text-white' }}" style="background-color: {{ $bankSetting->color ?? '#696cff' }};">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold {{ $isLightBg ? 'text-dark' : 'text-white' }}">
                                            {{ $bankSetting->bank->bank_name ?? 'Bank' }}
                                        </h6>
                                        <small class="badge {{ $isLightBg ? 'bg-secondary text-white' : 'bg-dark bg-opacity-25 text-white' }}">
                                            {{ $bankSetting->account_no }}
                                        </small>
                                    </div>

                                    <p class="mb-2 small {{ $isLightBg ? 'text-muted' : 'text-white-50' }}">
                                        {{ $bankSetting->holder_name ?? '' }}
                                    </p>

                                    <div class="mt-auto pt-2 border-top {{ $isLightBg ? 'border-secondary border-opacity-25' : 'border-light border-opacity-25' }}">
                                        <small class="{{ $isLightBg ? 'text-muted' : 'text-white-50' }} d-block">Current Capital</small>
                                        <h3 class="mb-0 fw-semibold {{ $isLightBg ? 'text-dark' : 'text-white' }}">
                                            {{ number_format($bankSetting->capital, 2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-3">
                                No bank settings found.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title m-0">Midnight Bank Capital Snapshots ({{ now()->format('d M Y') }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Bank Name</th>
                                    <th>Account No</th>
                                    <th>Recorded Capital</th>
                                    <th>Snapshot Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailySnapshots as $snapshot)
                                    <tr>
                                        <td>{{ optional($snapshot->bankSetting->bank)->bank_name ?? 'N/A' }}</td>
                                        <td>{{ optional($snapshot->bankSetting)->account_no ?? 'N/A' }}</td>
                                        <td>{{ number_format($snapshot->capital, 2) }}</td>
                                        <td>{{ $snapshot->snapshot_date }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No snapshots recorded for today yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

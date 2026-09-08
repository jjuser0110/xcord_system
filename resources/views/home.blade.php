@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold py-3 mb-0"><span class="text-muted fw-light">Financial /</span> Dashboard</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Flexible Filter Form -->
                <form method="GET" action="{{ route('home') }}" id="dashboardFilterForm" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                    <select name="filter_type" id="filter_type" class="form-select form-select-sm w-auto" onchange="triggerFilterLoading()">
                        <option value="month" {{ ($filterType ?? 'month') == 'month' ? 'selected' : '' }}>By Month</option>
                        <option value="date_range" {{ ($filterType ?? '') == 'date_range' ? 'selected' : '' }}>By Date Range</option>
                    </select>

                    @if(($filterType ?? 'month') == 'month')
                        <input type="month" name="month" value="{{ $currentMonth ?? now()->format('Y-m') }}" class="form-control form-control-sm w-auto" onchange="triggerFilterLoading()">
                    @else
                        <input type="date" name="start_date" value="{{ $startDate ?? '' }}" class="form-control form-control-sm w-auto" onchange="triggerFilterLoading()" placeholder="Start Date">
                        <input type="date" name="end_date" value="{{ $endDate ?? '' }}" class="form-control form-control-sm w-auto" onchange="triggerFilterLoading()" placeholder="End Date">
                    @endif
                </form>

                <div>
                    <span class="badge bg-label-primary fs-6">{{ now()->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Daily Performance Metrics Cards -->
    <div class="row">
        <!-- Transfer to Own Bank -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-export"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($transferToOwn ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Transfer to Own Bank</p>
                    <p class="mb-0">
                        <small class="text-muted">Total Outflow</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Received from Own Bank -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-import"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($receiveFromOwn ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Received from Own Bank</p>
                    <p class="mb-0">
                        <small class="text-muted">Total Inflow</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Transfer for Merchant -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-store"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($transferForMerchant ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Transfer for Merchant</p>
                    <p class="mb-0">
                        <small class="text-muted">Merchant Settlements</small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Expenses -->
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-wallet"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($expenses ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1">Expenses</p>
                    <p class="mb-0">
                        <small class="text-muted">Total Expenses</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Additional Finance Insights (Monthly Summaries & Quick Stats) -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="card-title m-0 me-2">Provider Reports</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Received from Provider -->
                        <div class="col-md-6 col-12">
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-info text-info"><i class="bx bx-down-arrow-circle"></i></span>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">Received from Provider</small>
                                    <h5 class="mb-0 fw-semibold">{{ number_format($receiveFromProvider ?? 0, 2) }}</h5>
                                </div>
                            </div>
                        </div>

                        <!-- TopUp to Provider -->
                        <div class="col-md-6 col-12">
                            <div class="d-flex align-items-center">
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-danger text-danger"><i class="bx bx-up-arrow-circle"></i></span>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted d-block">TopUp to Provider</small>
                                    <h5 class="mb-0 fw-semibold">{{ number_format($topUpToProvider ?? 0, 2) }}</h5>
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
                    <a href="{{ route('transaction.index') }}" class="btn btn-primary w-100">
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
                    <h5 class="card-title m-0">Bank Accounts Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($bankSettings as $bankSetting)
                            @php
                                $bgColor = strtolower(trim($bankSetting->color ?? '#696cff'));
                                $lightColors = ['white', '#ffffff', '#fff', '#f8f9fa', '#e9ecef', '#d1e7dd', '#fff3cd', '#f8d7da', '#cff4fc'];
                                $isLightBg = in_array($bgColor, $lightColors) || str_starts_with($bgColor, '#f') || str_starts_with($bgColor, '#e');
                            @endphp
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="card p-3 h-100 shadow-sm {{ $isLightBg ? 'text-dark border' : 'text-white' }}" style="background-color: {{ $bankSetting->color ?? '#696cff' }};">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold {{ $isLightBg ? 'text-dark' : 'text-white' }}">
                                            {{ $bankSetting->bank->bank_name ?? 'Bank' }}
                                        </h6>

                                    </div>

                                    <p class="mb-2 small {{ $isLightBg ? 'text-muted' : 'text-white-50' }}">
                                        {{ $bankSetting->owner_name ?? '' }}
                                    </p>

                                    <div class="mt-auto pt-2 border-top {{ $isLightBg ? 'border-secondary border-opacity-25' : 'border-light border-opacity-25' }}">
                                        <small class="{{ $isLightBg ? 'text-muted' : 'text-white-50' }} d-block">Current Capital</small>
                                        <h3 class="mb-0 fw-semibold {{ $isLightBg ? 'text-dark' : 'text-white' }}">
                                            {{ number_format($bankSetting->amount, 2) }}
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
                                    <th>Recorded Capital</th>
                                    <th>Snapshot Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailySnapshots as $snapshot)
                                    <tr>
                                        <td>{{ optional($snapshot->bankSetting->bank)->bank_name ?? 'N/A' }}</td>
                                        <td>{{ number_format($snapshot->bankSetting->amount, 2) }}</td>
                                        <td>{{ $snapshot->snapshot_date }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No snapshots recorded for today yet.</td>
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
<script>
function triggerFilterLoading() {
    if (typeof showLoading === 'function') {
        showLoading();
    }
    document.getElementById('dashboardFilterForm').submit();
}
</script>
@endsection

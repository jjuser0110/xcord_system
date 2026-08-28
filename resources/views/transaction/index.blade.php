@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <div>
            <h4 class="fw-bold m-0">
                <span class="text-muted fw-light">Transaction /</span> Bank Setting Lists
            </h4>
            <!-- Dynamic Date Range Display -->
            <small class="text-primary fw-semibold">
                <i class="bx bx-calendar me-1"></i> Period: {{ $startDate }} - {{ $endDate }}
            </small>
        </div>

        <!-- Month Filter Form -->
        <form method="GET" action="{{ route('transaction.index') }}" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 fw-semibold">Month:</label>
            <input type="month" name="month" value="{{ $currentMonth }}" class="form-control form-control-sm" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bank Setting</th>
                            <th>Current Balance Amount</th>
                            <th>Count (This Month)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bankSettings as $setting)
                            <tr>
                                <td>{{ $setting->id }}</td>
                                <td><strong>{{ $setting->owner_name }} - {{ $setting->bank->short_name ?? '-' }}</strong></td>
                                <td>{{ number_format($setting->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-label-primary">{{ $setting->month_transaction_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <div class="d-inline-block text-nowrap">
                                        <a href="{{ route('transaction.log', ['bank_setting' => $setting->id, 'month' => $currentMonth]) }}" class="btn btn-sm btn-icon item-edit me-4" onclick="showLoading()" title="View">
                                            <i class="bx bx-show me-1"></i> View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No bank accounts found for the current country scope.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links with Bootstrap 5 Style -->
            @if(method_exists($bankSettings, 'links'))
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $bankSettings->firstItem() ?? 0 }} to {{ $bankSettings->lastItem() ?? 0 }} of {{ $bankSettings->total() }} entries
                    </div>
                    <div>
                        {{ $bankSettings->appends(['month' => $currentMonth])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

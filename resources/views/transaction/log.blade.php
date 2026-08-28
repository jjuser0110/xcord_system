@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a href="{{ route('transaction.index', ['month' => $currentMonth]) }}">Bank Setting Lists</a></li>
            <li class="breadcrumb-item active fw-bold">Transaction Logs</li>
        </ol>
    </nav>

    <!-- Clean Header Card -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center justify-content-between g-3">
                <!-- Left Column: Bank Info & Period -->
                <div class="col-md-7">
                    <h5 class="fw-bold mb-1">
                        {{ $bank_setting->owner_name }}
                        <span class="text-muted fw-normal font-monospace fs-6">({{ $bank_setting->bank->short_name ?? '-' }})</span>
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                        <span>Current Balance: <strong class="text-success">{{ number_format($bank_setting->amount, 2) }}</strong></span>
                        <span class="text-dark">•</span>
                        <span><i class="bx bx-calendar me-1"></i> Period: <strong class="text-dark">{{ \Carbon\Carbon::parse($currentMonth)->startOfMonth()->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($currentMonth)->endOfMonth()->format('d.m.Y') }}</strong></span>
                    </div>
                </div>

                <!-- Right Column: Actions & Month Filter -->
                <div class="col-md-5 text-md-end d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                    <a href="{{ route('transaction.index', ['month' => $currentMonth]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back
                    </a>

                    <form method="GET" action="{{ route('transaction.log', $bank_setting->id) }}" class="d-inline-flex align-items-center">
                        <input type="month" name="month" value="{{ $currentMonth }}" class="form-control form-control-sm" onchange="this.form.submit()">
                    </form>

                    <a href="{{ route('transaction.create', ['bank_setting_id' => $bank_setting->id]) }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Add
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Transaction Table Card -->
    <div class="card">
        <div class="card-body px-3 py-3">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">Date</th>
                            <th class="py-2">In</th>
                            <th class="py-2">Out</th>
                            <th class="py-2">Balance</th>
                            <th class="py-2">Type</th>
                            <th class="py-2">Purpose</th>
                            <th class="py-2">Remark 1</th>
                            <th class="py-2">Remark 2</th>
                            <th class="py-2">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <!-- Smaller Date Text -->
                                <td class="small text-muted">{{ $tx->transaction_date }}</td>

                                <!-- IN Column -->
                                <td>
                                    @if($tx->transfer_direction == '+')
                                        <span class="text-success fw-bold">+ {{ number_format($tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- OUT Column -->
                                <td>
                                    @if($tx->transfer_direction == '-')
                                        <span class="text-danger fw-bold">- {{ number_format($tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- Larger, Prominent Balance Column -->
                                <td style="font-size: 0.9rem;">
                                    @if($tx->type === 'own' && $tx->target_bank_setting_id == $bank_setting->id)
                                        <span class="text-secondary" style="font-size: 0.8rem;">{{ number_format($tx->target_end_balance, 2) }}</span>
                                        {{-- <span class="text-secondary" style="font-size: 0.8rem;">Start: {{ number_format($tx->target_start_balance, 2) }}</span> --}}
                                    @else
                                        <span class="text-secondary" style="font-size: 0.8rem;">{{ number_format($tx->end_balance, 2) }}</span>
                                        {{-- <span class="text-secondary" style="font-size: 0.8rem;">Start: {{ number_format($tx->start_balance, 2) }}</span> --}}
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-label-primary px-2 py-1" style="font-size: 0.7rem;">
                                        {{ strtolower($tx->type) === 'customer' ? 'CUST' : strtoupper($tx->type) }}
                                    </span>
                                </td>
                                <td>{{ $tx->purpose->title ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 150px;" title="{{ $tx->remark_1 }}">{{ $tx->remark_1 ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 150px;" title="{{ $tx->remark_2 }}">{{ $tx->remark_2 ?? '-' }}</td>
                                <td class="small text-muted">{{ $tx->creator->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3 text-muted">No transaction logs found for this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="1" class="text-end">Monthly Total:</td>
                            <td class="text-success">+ {{ number_format($totalIn ?? 0, 2) }}</td>
                            <td class="text-danger">- {{ number_format($totalOut ?? 0, 2) }}</td>
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination Links & Summary Footer -->
            <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} entries
                </div>

                <div>
                    {{ $transactions->appends(['month' => $currentMonth])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

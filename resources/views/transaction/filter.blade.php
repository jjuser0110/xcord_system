@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item"><a href="{{ route('transaction.index', ['month' => $currentMonth]) }}">Bank Setting Lists</a></li>
            <li class="breadcrumb-item active fw-bold">Filtered Transactions</li>
        </ol>
    </nav>

    <!-- Header Card with Search Summary -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-1">Transaction Search Results</h4>
                    <p class="text-muted mb-0 small">
                        Period: <strong>{{ $currentMonth }}</strong> |
                        Remark 1: <strong>{{ $remark1 ?? 'Any' }}</strong> |
                        Remark 2: <strong>{{ $remark2 ?? 'Any' }}</strong> |
                        Amount: <strong>{{ $amount ?? 'Any' }}</strong>
                    </p>
                </div>
                <div>
                    <a href="{{ route('transaction.index', ['month' => $currentMonth]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-arrow-back me-1"></i> Back to Bank Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Results Table Card -->
    <div class="card">
        <div class="card-body px-3 py-3">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">Bank Account</th>
                            <th class="py-2">Date</th>
                            <th class="py-2">Direction</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Type</th>
                            <th class="py-2">Purpose</th>
                            <th class="py-2">Remark 1</th>
                            <th class="py-2">Remark 2</th>
                            <th class="py-2">Created At</th>
                            <th class="py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="fw-semibold">
                                    {{ optional($tx->bankSetting)->owner_name }} - {{ optional(optional($tx->bankSetting)->bank)->short_name ?? '-' }}
                                </td>
                                <td class="small text-muted">{{ $tx->transaction_date }}</td>
                                <td>
                                    @if($tx->transfer_direction == '+')
                                        <span class="badge bg-label-success">Bank In (+)</span>
                                    @else
                                        <span class="badge bg-label-danger">Bank Out (-)</span>
                                    @endif
                                </td>
                                <td class="fw-bold {{ $tx->transfer_direction == '+' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->transfer_direction == '+' ? '+' : '-' }} {{ number_format($tx->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge bg-label-primary px-2 py-1" style="font-size: 0.65rem;">
                                        {{ strtoupper($tx->type) }}
                                    </span>
                                </td>
                                <td class="small">{{ optional($tx->purpose)->title ?? '-' }}</td>
                                <td class="small text-truncate" style="max-width: 150px;" title="{{ $tx->remark_1 }}">{{ $tx->remark_1 ?? '-' }}</td>
                                <td class="small text-truncate" style="max-width: 150px;" title="{{ $tx->remark_2 }}">{{ $tx->remark_2 ?? '-' }}</td>
                                <td class="small text-muted">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    @if(\Carbon\Carbon::parse($tx->created_at)->isToday())
                                        <!-- Edit Button if created today -->
                                        <a href="{{ route('transaction.edit', array_merge(['transaction' => $tx->id, 'from' => 'filter'], request()->query())) }}"
                                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                        onclick="showLoading()"
                                        title="Edit Transaction">
                                            <i class="bx bx-edit-alt fs-5 text-primary"></i>
                                        </a>
                                    @else
                                        <!-- View Button if created before today (bringing to edit.blade.php in view-only mode) -->
                                        <a href="{{ route('transaction.edit', array_merge(['transaction' => $tx->id, 'mode' => 'view', 'from' => 'filter'], request()->query())) }}"
                                        class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect"
                                        onclick="showLoading()"
                                        title="View Transaction Detail">
                                            <i class="bx bx-show fs-5 text-secondary"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No matching transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            @if(method_exists($transactions, 'links'))
                <div class="card-footer d-flex justify-content-end align-items-center">
                    <div>
                        {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

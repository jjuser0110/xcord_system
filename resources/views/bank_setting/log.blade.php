@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                Bank Logs
            </h4>
            <span class="text-muted">
                <i class="bx bx-building-house me-1"></i> {{ $bankSetting->bank->bank_name ?? 'Bank' }} &mdash;
                <strong class="text-dark">{{ $bankSetting->account_no }}</strong>
            </span>
        </div>
        <div class="mt-3 mt-md-0">
            <a href="{{ route('bank_setting.edit', $bankSetting) }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Bank Setting
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <span class="text-muted d-block mb-1">Account Owner</span>
                <h5 class="mb-0 text-dark font-weight-semibold">{{ $bankSetting->owner_name ?? '-' }}</h5>
            </div>
            <div class="text-sm-end">
                <span class="text-muted d-block mb-1">Current Balance</span>
                <span class="badge bg-label-info fs-5 px-3 py-2">
                    {{ number_format($bankSetting->capital, 2) }}
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Transaction History</h5>
        </div>
        <div class="card-body pt-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Created At</th>
                            <th>Type</th>
                            <th>Remark</th>
                            <th>Start</th>
                            <th>Amount</th>
                            <th>End</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $isPositive = in_array($log->type, ['Deposit', 'Transfer In']);
                            @endphp
                            <tr>
                                <td>{{ $log->created_at }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $isPositive ? 'success' : 'danger' }}">
                                        {{ $log->type }}
                                    </span>
                                </td>
                                <td>{{ $log->remark ?? '-' }}</td>
                                <td>{{ number_format($log->start_balance, 2) }}</td>
                                <td class="{{ $isPositive ? 'text-success' : 'text-danger' }} fw-semibold">
                                    {{ $isPositive ? '+' : '-' }}{{ number_format($log->amount, 2) }}
                                </td>
                                <td>{{ number_format($log->end_balance, 2) }}</td>
                                <td>{{ $log->creator->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No logs found for this bank account.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

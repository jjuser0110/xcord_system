@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="fw-bold py-3 mb-0">Transaction Logs</h4>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('transaction.create') }}" class="btn btn-primary">+ New Transaction</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date / No</th>
                            <th>Bank Account(s)</th>
                            <th>Type / Purpose</th>
                            <th>Remark 1 / Remark 2</th>
                            <th>Start</th>
                            <th>Amount</th>
                            <th>End</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td>
                                    <strong>{{ $tx->transaction_date }}</strong><br>
                                    <small class="text-muted">{{ $tx->transaction_no }}</small>
                                </td>
                                <td>
                                    @if($tx->type === 'own')
                                        <div class="text-danger"><strong>Out:</strong> {{ $tx->bankSetting->bank->bank_name ?? '' }} ({{ $tx->bankSetting->account_no ?? '' }})</div>
                                        <div class="text-success mt-1"><strong>In:</strong> {{ $tx->targetBankSetting->bank->bank_name ?? '' }} ({{ $tx->targetBankSetting->account_no ?? '' }})</div>
                                    @else
                                        {{ $tx->bankSetting->bank->bank_name ?? '' }} ({{ $tx->bankSetting->account_no ?? '' }})
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
                                        <span class="badge bg-label-primary text-uppercase">{{ $tx->type }}</span>
                                        <span class="badge bg-label-{{ $tx->transfer_direction == '+' ? 'success' : 'danger' }}">
                                            @if($tx->type === 'own')
                                                Transfer
                                            @else
                                                {{ $tx->transfer_direction == '+' ? 'Deposit' : 'Withdraw' }}
                                            @endif
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $tx->purpose->name ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $tx->remark_1 }}</div>
                                    <small class="text-muted">{{ $tx->remark_2 }}</small>
                                </td>
                                <td>
                                    @if($tx->type === 'own')
                                        <div class="text-danger">Out: {{ number_format($tx->start_balance, 2) }}</div>
                                        <div class="text-success">In: {{ number_format($tx->target_start_balance, 2) }}</div>
                                    @else
                                        {{ number_format($tx->start_balance, 2) }}
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $tx->transfer_direction == '+' ? 'text-success' : 'text-danger' }}">
                                        {{ $tx->transfer_direction }} {{ number_format($tx->amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($tx->type === 'own')
                                        <div class="text-danger">Out: {{ number_format($tx->end_balance, 2) }}</div>
                                        <div class="text-success">In: {{ number_format($tx->target_end_balance, 2) }}</div>
                                    @else
                                        {{ number_format($tx->end_balance, 2) }}
                                    @endif
                                </td>
                                <td>{{ $tx->creator->name ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

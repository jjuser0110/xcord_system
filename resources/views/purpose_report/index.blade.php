
@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">Reports /</span> Purpose Transaction Report
    </h4>


    {{-- Summary Cards & Filter Bar --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-row flex-nowrap justify-content-between align-items-center gap-3">

            {{-- Summary Totals --}}
            <div class="d-flex flex-row flex-nowrap align-items-center gap-3"
                style="flex: 1 1 auto; min-width: 0;">

                <div class="pe-3 border-end text-nowrap">
                    <span class="text-muted d-block mb-1" style="font-size: 11px;">
                        Total In ({{ $currentDate ?: 'All Time' }})
                    </span>
                    <h6 class="fw-bold mb-0 text-success" style="font-size: 15px;">
                        + {{ number_format($totalIn, 2) }}
                    </h6>
                </div>

                <div class="pe-3 border-end text-nowrap">
                    <span class="text-muted d-block mb-1" style="font-size: 11px;">
                        Total Out ({{ $currentDate ?: 'All Time' }})
                    </span>
                    <h6 class="fw-bold mb-0 text-danger" style="font-size: 15px;">
                        - {{ number_format($totalOut, 2) }}
                    </h6>
                </div>

                <div class="text-nowrap">
                    <span class="text-muted d-block mb-1" style="font-size: 11px;">
                        Net Total
                    </span>
                    <h6 class="fw-bold mb-0 {{ $netTotal >= 0 ? 'text-primary' : 'text-danger' }}"
                        style="font-size: 15px;">
                        {{ $netTotal >= 0 ? '+ ' : '- ' }}{{ number_format(abs($netTotal), 2) }}
                    </h6>
                </div>

            </div>

            {{-- Filters --}}
            <form method="GET"
                action="{{ route('purpose_report.index') }}"
                class="d-flex flex-row flex-nowrap align-items-end gap-2"
                style="flex: 0 0 auto;">

                {{-- Purpose --}}
                <div style="width: 190px;">
                    <label for="purpose_id"
                        class="form-label mb-1 fw-semibold text-nowrap"
                        style="font-size: 11px;">
                        PURPOSE:
                    </label>

                    <select name="purpose_id"
                            id="purpose_id"
                            class="form-select form-select-sm"
                            style="font-size: 12px;"
                            onchange="this.form.submit()">
                        <option value="">All Purposes</option>

                        @foreach($purposes as $purpose)
                            <option value="{{ $purpose->id }}"
                                {{ (isset($currentPurposeId) && $currentPurposeId == $purpose->id) ? 'selected' : '' }}>
                                {{ $purpose->title }}{{ !empty($purpose->provider_name) ? ' (' . $purpose->provider_name . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Start Date --}}
                <div style="width: 150px;">
                    <label for="start_date"
                        class="form-label mb-1 fw-semibold text-nowrap"
                        style="font-size: 11px;">
                        START DATE:
                    </label>

                    <input type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ $startDate }}"
                        max="{{ $endDate }}"
                        class="form-control form-control-sm"
                        style="font-size: 12px;">
                </div>

                {{-- End Date --}}
                <div style="width: 150px;">
                    <label for="end_date"
                        class="form-label mb-1 fw-semibold text-nowrap"
                        style="font-size: 11px;">
                        END DATE:
                    </label>

                    <input type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ $endDate }}"
                        min="{{ $startDate }}"
                        class="form-control form-control-sm"
                        style="font-size: 12px;">
                </div>

                {{-- Buttons --}}
                <div class="d-flex flex-row align-items-center gap-1">
                    <button type="submit"
                            class="btn btn-primary btn-sm text-nowrap"
                            style="font-size: 12px;">
                        Apply
                    </button>

                    <a href="{{ route('purpose_report.index') }}"
                    class="btn btn-outline-secondary btn-sm text-nowrap"
                    style="font-size: 12px;">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>


    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-column flex-md-row">
            <h5 class="card-title mb-0">Daily Transactions by Purpose</h5>
        </div>

        <div class="card-body px-3 py-3">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">Bank Setting</th>
                            <th class="py-2">Direction</th>
                            <th class="py-2">Amount</th>
                            <th class="py-2">Purpose</th>
                            <th class="py-2">Remarks</th>
                            <th class="py-2">Transaction Date</th>
                            <th class="py-2">Created By</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($transactions as $row)
                            <tr>
                                <td>
                                    @php
                                        $bankSetting = $row->bankSetting;
                                    @endphp

                                    @if($bankSetting)
                                        <strong>
                                            {{ $bankSetting->owner_name }} -
                                            {{ $bankSetting->bank?->short_name ?? '-' }}
                                        </strong>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    <span class="badge {{ $row->transfer_direction === '+' ? 'bg-label-success' : 'bg-label-warning' }}">
                                        {{ $row->transfer_direction === '+' ? 'In (+)' : 'Out (-)' }}
                                    </span>
                                </td>

                                <td class="fw-bold {{ $row->transfer_direction === '+' ? 'text-success' : 'text-danger' }}">
                                    {{ $row->transfer_direction === '+' ? '+ ' : '- ' }}{{ number_format($row->amount, 2) }}
                                </td>

                                <td>
                                    <span class="badge bg-label-primary">
                                        {{ optional($row->purpose)->title ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="text-truncate d-inline-block"
                                          style="max-width: 150px;"
                                          title="{{ $row->remark_1 }}">
                                        {{ $row->remark_1 ?? '-' }}
                                    </span>
                                </td>

                                <td class="small text-muted">
                                    {{ \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y') }}
                                </td>

                                <td class="small text-muted">
                                    {{ $row->creator?->name ?? $row->creator?->username ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-3 text-muted">
                                    No transactions found for the selected purpose and date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links Footer -->
            @if(method_exists($transactions, 'links'))
                <div class="card-footer d-flex justify-content-end align-items-center">
                    <div>
                        {{ $transactions->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

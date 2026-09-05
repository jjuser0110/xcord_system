@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">Provider Settlement /</span> Listing
    </h4>
    <div class="card mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
                <span class="text-muted small d-block mb-1">Total Settlement Amount ({{ $currentMonth }})</span>
                <h3 class="fw-bold mb-0 {{ $totalSum >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $totalSum >= 0 ? '+ ' : '- ' }}{{ number_format(abs($totalSum), 2) }}
                </h3>
            </div>

            <!-- Month Filter Form Inside Card -->
            <form method="GET" action="{{ route('provider_settlement.index') }}" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 fw-semibold small text-nowrap">Filter Month:</label>
                <input type="month" name="month" value="{{ $currentMonth }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-column flex-md-row">
            <h5 class="card-title mb-0">Provider Settlements</h5>
        </div>

        <div class="card-body px-3 py-3">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">ID</th>
                            <th class="py-2">Bank Setting</th>
                            <th class="py-2">Settlement</th>
                            <th class="py-2">Provider</th>
                            <th class="py-2">Created At</th>
                            <th class="py-2">Created By</th>
                            <th class="py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>
                                @php
                                    $bankSetting = $row->transaction?->bankSetting;
                                @endphp

                                @if($bankSetting)
                                    <strong>{{ $bankSetting->owner_name }} - {{ $bankSetting->bank?->short_name ?? '-' }}</strong>
                                @else
                                    {{ $row->bank_name ?? '-' }}
                                @endif
                            </td>
                            <td class="fw-bold {{ $row->settlement_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $row->settlement_amount >= 0 ? '+ ' : '- ' }}{{ number_format(abs($row->settlement_amount), 2) }}
                            </td>
                            <td>
                                <span class="badge bg-label-primary">{{ $row->provider_name }}</span>
                            </td>
                            <td class="small text-muted">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small text-muted">{{ $row->created_by?->name ?? $row->created_by?->username ?? '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('provider_settlement.show', $row->id) }}" class="btn btn-sm btn-icon" title="View Details">
                                    <i class="bx bx-show"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted">No provider settlements found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links & Entry Count Footer -->
            @if(method_exists($settlements, 'links'))
                <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="text-muted small">
                        Showing {{ $settlements->firstItem() ?? 0 }} to {{ $settlements->lastItem() ?? 0 }} of {{ $settlements->total() }} entries
                    </div>
                    <div>
                        {{ $settlements->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

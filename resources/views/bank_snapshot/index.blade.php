@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <li class="breadcrumb-item active fw-bold">Daily Bank Settings Balance</li>
        </ol>
    </nav>

    <!-- Header Card with Date Picker Form -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center justify-content-between g-3">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-1">Daily Bank Settings Balance</h5>
                    <p class="text-muted small mb-0">Select a date to check historical bank snapshots.</p>
                </div>

                <div class="col-md-6 text-md-end">
                    <form method="GET" action="{{ route('bank_snapshot.index') }}" class="d-inline-flex align-items-center gap-2">
                        <label for="date" class="form-label small mb-0 fw-bold">Pick Date:</label>
                        <input type="date" name="date" id="date" value="{{ $selectedDate }}" class="form-control form-control-sm" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Box -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card bg-label-primary p-3">
                <span>Total Capital on {{ $selectedDate }}:</span>
                <h4 class="fw-bold mb-0 text-primary">{{ number_format($totalCapital ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card">
        <div class="card-body px-3 py-3">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered table-sm align-middle" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="py-2">#</th>
                            <th class="py-2">Bank Setting</th>
                            <th class="py-2">Snapshot Date</th>
                            <th class="py-2">Capital</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $index => $snapshot)
                            <tr>
                                <td>{{ $snapshots->firstItem() + $index }}</td>
                                <td class="fw-bold">{{ $snapshot->bankSetting->owner_name }} - {{ $snapshot->bankSetting->bank->short_name ?? '-' }}</td>
                                <td class="small text-muted">{{ $snapshot->snapshot_date }}</td>
                                <td>
                                    <span class="text-success fw-bold">{{ number_format($snapshot->capital, 2) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No bank snapshots found for <strong>{{ $selectedDate }}</strong>. Make sure the cron job has run for this date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="text-muted small">
                    Showing {{ $snapshots->firstItem() ?? 0 }} to {{ $snapshots->lastItem() ?? 0 }} of {{ $snapshots->total() }} entries
                </div>
                <div>
                    {{ $snapshots->appends(['date' => $selectedDate])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

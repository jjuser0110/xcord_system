@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">Master Setting /</span> Bank Accounts
    </h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bank Accounts List</h5>
            <a href="{{ route('bank_setting.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Add Bank Account
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Color</th>
                            <th>Bank Account <small class="text-muted">(Owner - Short Name - Amount)</small></th>
                            <th>Country</th>
                            <th>Capital</th>
                            <th>Current Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $paletteMap = [
                                'white'      => ['hex' => '#ffffff', 'text' => '#333333'],
                                'red'        => ['hex' => '#ff3e1d', 'text' => '#ffffff'],
                                'pink'       => ['hex' => '#ffdbeb', 'text' => '#333333'],
                                'blue'       => ['hex' => '#2a5d96', 'text' => '#ffffff'],
                                'lightblue'  => ['hex' => '#7ad3ff', 'text' => '#333333'],
                                'green'      => ['hex' => '#6aae46', 'text' => '#ffffff'],
                                'lightgreen' => ['hex' => '#b1f08a', 'text' => '#333333'],
                            ];
                        @endphp
                        @forelse($bank_settings as $setting)
                        @php
                            $colorKey = strtolower(trim($setting->color ?? 'white'));
                            $bgHex = $paletteMap[$colorKey]['hex'] ?? '#ffffff';
                            $textHex = $paletteMap[$colorKey]['text'] ?? '#333333';
                        @endphp
                        <tr>
                            <td>{{ $setting->id }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $bgHex }}; color: {{ $textHex }}; border: 1px solid #ddd;">
                                    {{ ucfirst($colorKey) }}
                                </span>
                            </td>
                            <!-- Bank Account column format -->
                            <td><strong>{{ $setting->owner_name }} - {{ $setting->bank->short_name ?? '-' }} - {{ number_format($setting->amount, 2) }}</strong></td>
                            <td>{{ $setting->country->currency_code ?? '-' }}</td>
                            <td>{{ number_format($setting->capital, 2) }}</td>

                            <!-- Clickable Amount Column to trigger adjustment modal -->
                            <td>
                                <a href="javascript:void(0);" class="fw-bold text-primary text-decoration-underline"
                                   onclick="openAmountModal('{{ $setting->id }}', '{{ $setting->amount }}', '{{ $setting->owner_name }} - {{ $setting->bank->bank_name ?? '' }}')">
                                    {{ number_format($setting->amount, 2) }} <i class="bx bx-edit fs-small"></i>
                                </a>
                            </td>
                            <td>
                                @if($setting->is_active)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-block text-nowrap">
                                    <a href="{{ route('bank_setting.edit', $setting) }}" class="btn btn-sm btn-icon item-edit me-4" onclick="showLoading()" title="Edit">
                                        <i class="bx bx-edit-alt"></i>Edit
                                    </a>

                                    <button type="button" class="btn btn-sm btn-icon item-delete" style="color: red;" onclick="if(confirm('Are you sure?')){window.location.href='{{ route('bank_setting.destroy',$setting) }}'}" title="Delete">
                                        <i class="bx bx-trash"></i>Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No bank account settings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Amount Adjustment Modal -->
<div class="modal fade" id="amountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="amountAdjustmentForm" method="POST" action="{{ route('bank_setting.updateAmount', 'PLACEHOLDER') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBankTitle">Adjust Bank Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Amount Balance</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" id="modal_amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <select class="form-select" disabled>
                            <option value="" disabled>Select Purpose</option>
                            @foreach($purposes as $purpose)
                                <option value="{{ $purpose->id }}" {{ $purpose->id == 2 ? 'selected' : '' }}>
                                    {{ $purpose->title }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="purpose_id" value="2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <textarea class="form-control" name="remark" rows="3" placeholder="Reason for balance change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openAmountModal(bankSettingId, currentAmount, bankInfo) {
        document.getElementById('modalBankTitle').innerText = 'Adjust Amount: ' + bankInfo;
        document.getElementById('modal_amount').value = currentAmount;

        // Grab the template route generated by Blade and swap out the placeholder safely
        let actionUrl = document.getElementById('amountAdjustmentForm').getAttribute('action');
        // If it was already replaced previously, ensure we reset or cleanly substitute:
        let baseRoute = "{{ route('bank_setting.updateAmount', 'PLACEHOLDER') }}";

        document.getElementById('amountAdjustmentForm').action = baseRoute.replace('PLACEHOLDER', bankSettingId);

        var myModal = new bootstrap.Modal(document.getElementById('amountModal'));
        myModal.show();
    }
</script>
@endsection

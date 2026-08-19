@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">Master Setting /</span> Bank Accounts
    </h4>

    <div class="card">
        <div class="card-header flex-column flex-md-row">
            <div class="row">
                <div class="col-6">
                    <h5>Bank Accounts List</h5>
                </div>
                <div class="col-6 text-end">
                    <a href="{{ route('bank_setting.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Bank Account
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Color</th>
                            <th>Bank</th>
                            <th>Type</th>
                            <th>Account No & Owner</th>
                            <th>Country</th>
                            <th>Capital</th>
                            <th>Phone / Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bank_settings as $setting)
                        <tr>
                            <td>{{ $setting->id }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $setting->color }}; color: #000; border: 1px solid #ddd;">
                                    {{ ucfirst($setting->color) }}
                                </span>
                            </td>
                            <td><strong>{{ $setting->bank->bank_name ?? '-' }}</strong></td>
                            <td>{{ $setting->type ?? '-' }}</td>
                            <td>
                                {{ $setting->account_no ?? '-' }} <br>
                                <small class="text-muted">{{ $setting->owner_name ?? '' }}</small>
                            </td>
                            <td>{{ $setting->country->name ?? '-' }}</td>
                            <td>{{ number_format($setting->capital, 2) }}</td>
                            <td>
                                {{ $setting->phone_number ?? '-' }} <br>
                                <small class="text-muted">Exp: {{ $setting->expired_date ?? '-' }}</small>
                            </td>
                            <td>
                                @if($setting->is_active)
                                    <span class="badge bg-label-success">Active</span>
                                @else
                                    <span class="badge bg-label-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        Action
                                    </button>
                                    <div class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('bank_setting.edit', $setting) }}">
                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            @if($setting->is_active)
                                                <a class="dropdown-item" onclick="if(confirm('Are you sure you want to deactivate?')){showLoading();window.location.href='{{ route('bank_setting.inactive',$setting) }}'}">
                                                    <i class="bx bx-power-off me-1"></i> Deactivate
                                                </a>
                                            @else
                                                <a class="dropdown-item" onclick="if(confirm('Are you sure you want to activate?')){showLoading();window.location.href='{{ route('bank_setting.active',$setting) }}'}">
                                                    <i class="bx bx-check-circle me-1"></i> Activate
                                                </a>
                                            @endif
                                        </li>
                                        <li>
                                            <a class="dropdown-item" style="color:red;cursor:pointer" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('bank_setting.destroy',$setting) }}'}">Delete</a>
                                        </li>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center">No bank account settings found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

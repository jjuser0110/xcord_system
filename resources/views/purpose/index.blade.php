@extends('layouts.app')
@section('content')
    <!-- Content -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4"><span class="text-muted fw-light">Purpose </span></h4>

        <!-- Table Card -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Purpose Listing</h5>
                </div>
                <div class="dt-action-buttons text-end pt-3 pt-md-0">
                    <div class="dt-buttons">
                        <a class="dt-button create-new btn btn-primary" type="button" href="{{route('purpose.create')}}" onclick="showLoading()">
                            <span><i class="bx bx-plus me-sm-1"></i>
                                <span class="d-none d-sm-inline-block">Add New Record</span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>Purpose Title</th>
                            <th>Country/Currency</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purpose as $row)
                        <tr>
                            <td>{{$row?->title??""}}</td>
                            <td>
                                @if($row->is_global)
                                    <span class="badge bg-label-primary">All Countries (Global)</span>
                                @else
                                    @forelse($row->countries as $country)
                                        <span class="badge bg-label-secondary me-1">{{ $country->name }} ({{ $country->currency_code }})</span>
                                    @empty
                                        <span class="badge bg-label-warning">None Assigned</span>
                                    @endforelse
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-block text-nowrap">
                                    <a href="{{ route('purpose.edit',$row) }}" class="btn btn-sm btn-icon item-edit me-4" onclick="showLoading()" title="Edit">
                                        <i class="bx bx-edit-alt"></i>Edit
                                    </a>

                                    <button type="button" class="btn btn-sm btn-icon item-delete" style="color: red;" onclick="if(confirm('Are you sure you want to delete?')){showLoading();window.location.href='{{ route('purpose.destroy',$row) }}'}" title="Delete">
                                        <i class="bx bx-trash"></i>Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Laravel Pagination Links -->
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing {{ $purpose->firstItem() ?? 0 }} to {{ $purpose->lastItem() ?? 0 }} of {{ $purpose->total() }} entries
                </div>
                <div>
                    {{ $purpose->links() }}
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->
@endsection

@section('page-js')
@endsection

@section('scripts')
@endsection

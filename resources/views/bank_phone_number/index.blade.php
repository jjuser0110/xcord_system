@extends('layouts.app')

@section('content')
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="py-3 breadcrumb-wrapper mb-4">
            <span class="text-muted fw-light">Bank /</span> Phone Numbers Listing
        </h4>

        <!-- DataTable with Buttons -->
        <div class="card">
            <div class="card-header flex-column flex-md-row">
                <div class="head-label">
                    <h5 class="card-title mb-0">Bank Phone Numbers</h5>
                </div>
            </div>
            <div class="card-datatable text-nowrap">
                <table class="dt-column-search table table-bordered" id="mytable">
                    <thead>
                        <tr>
                            <th>Bank (Owner - Short Name)</th>
                            <th>Contact Number</th>
                            <th>Expired Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($phoneNumbers as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->bankSetting->owner_name ?? '-' }}</strong> -
                                <span class="text-muted">{{ $row->bankSetting->bank->short_name ?? '-' }}</span>
                            </td>
                            <td>{{ $row->phone_number ?? '-' }}</td>
                            <td>
                                @if($row->expired_date)
                                    <span class="{{ \Carbon\Carbon::parse($row->expired_date)->isPast() ? 'text-danger fw-bold' : '' }}">
                                        {{ \Carbon\Carbon::parse($row->expired_date)->format('d.m.Y') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="d-inline-block text-nowrap">
                                    <a href="{{ route('bank_phone_number.edit',$row) }}" class="btn btn-sm btn-icon item-edit me-4" onclick="showLoading()" title="Edit">
                                        <i class="bx bx-edit-alt"></i>Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- / Content -->
@endsection

@section('page-js')
@endsection

@section('scripts')
  <script>
    $(function(){
      var table = $('#mytable').DataTable({
        responsive: true,
        pageLength: 10,
        displayLength: 7,
        lengthMenu: [7, 10, 25, 50, 75, 100],
      });
    });
  </script>
@endsection

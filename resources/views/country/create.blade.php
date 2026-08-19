@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('country.index')}}">Country /</a>
         @if (isset($country)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Country Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($country)) method="post" action="{{ route('country.update',$country) }}" @else method="post" action="{{ route('country.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="name">Country Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Malaysia"
                    name="name"
                    value="{{$country->name??''}}"
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="currency_code">Currency</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="MYR"
                    name="currency_code"
                    value="{{$country->currency_code??''}}"
                    required/>
                </div>
                <hr>
                <div class="col-12">
                    <button type="submit" name="submitButton" class="btn btn-primary">Submit</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@section('scripts')
@endsection

@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('purpose.index')}}">Purpose /</a>
         @if (isset($purpose)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Purpose Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($purpose)) method="post" action="{{ route('purpose.update',$purpose) }}" @else method="post" action="{{ route('purpose.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="title">Purpose Title</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Deposit"
                    name="title"
                    value="{{$purpose->title??''}}"
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="country_id">Country</label>
                    <select name="country_id" id="country_id" class="form-select" required>
                        <option value="" disabled selected>Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                {{ (isset($purpose) && $purpose->country_id == $country->id) ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea
                    class="form-control"
                    placeholder="Enter description here..."
                    name="description"
                    rows="4"
                    required>{{$purpose->description??''}}</textarea>
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

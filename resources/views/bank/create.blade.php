@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('bank.index')}}">Bank /</a>
         @if (isset($bank)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">Bank Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($bank)) method="post" action="{{ route('bank.update',$bank) }}" @else method="post" action="{{ route('bank.store') }}" @endif onsubmit="showLoading()">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="Maybank"
                    name="bank_name"
                    value="{{$bank->bank_name??''}}"
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="short_name">Short Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="MBB"
                    name="short_name"
                    value="{{$bank->short_name??''}}"
                    required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="country_id">Country</label>
                    <select name="country_id" id="country_id" class="form-select" required @if(isset($disableCountry) && $disableCountry) disabled @endif>
                        <option value="" disabled {{ !isset($bank) && !isset($disableCountry) ? 'selected' : '' }}>Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                {{ ((isset($bank) && $bank->country_id == $country->id) || (isset($disableCountry) && $disableCountry)) ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>

                    @if(isset($disableCountry) && $disableCountry)
                        <input type="hidden" name="country_id" value="{{ $countries->first()->id ?? '' }}">
                    @endif
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

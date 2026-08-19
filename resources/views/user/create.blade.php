@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <a class="text-muted fw-light" href="{{route('user.index')}}">User /</a>
         @if (isset($user)) Edit @else Create @endif
    </h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
            <h5 class="card-header">User Details</h5>
            <div class="card-body">
                <form class="row g-3" enctype="multipart/form-data" @if (isset($user)) method="post" action="{{ route('user.update',$user) }}" @else method="post" action="{{ route('user.store') }}" @endif onsubmit="showLoading()">
                @csrf

                <div class="col-md-6">
                    <label class="form-label" for="name">Name</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="John Doe"
                    name="name"
                    value="{{$user->name??''}}"
                    required/>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="username">Username</label>
                    <input
                    type="text"
                    class="form-control"
                    placeholder="johndoe"
                    name="username"
                    value="{{$user->username??''}}"
                    required/>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input
                    type="email"
                    class="form-control"
                    placeholder="john@example.com"
                    name="email"
                    value="{{$user->email??''}}"/>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="password">Password @if(isset($user)) <small class="text-muted">(Leave blank to keep current)</small> @endif</label>
                    <input
                    type="password"
                    class="form-control"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    name="password"
                    @if(!isset($user)) required @endif/>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="role_id">Role</label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <option value="" disabled selected>Select Role</option>
                        @foreach($roles as $id => $title)
                            <option value="{{ $id }}"
                                {{ (isset($user) && $user->role_id == $id) ? 'selected' : '' }}>
                                {{ $title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="country_id">Country</label>
                    <select name="country_id" id="country_id" class="form-select" required>
                        <option value="" disabled selected>Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                {{ (isset($user) && $user->country_id == $country->id) ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
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

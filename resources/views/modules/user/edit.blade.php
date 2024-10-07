@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Edit')

@section('breadcrumb')
@stop

@section('body')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card mx-4">
                    <div class="card-body p-4">
                        <h1>Create Account</h1>
                        <p class="text-muted">{{ __('coreui::coreui.register_message') }}</p>

                        <form action="{{ route('user.update', ['id' => $profile->id]) }}" method="post">
                            @csrf
                            @method('put')
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                </div>

                                <input id="name" type="text"
                                    class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name"
                                    value="{{ $profile->name }}" placeholder="{{ __('coreui::coreui.full_name') }}"
                                    required autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('name') }}</strong></span>
                                @endif
                                <input type="hidden" value="{{ $profile->users->id }}" name="user_id">
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                </div>

                                <input id="email" type="email"
                                    class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                                    value="{{ $profile->users->email }}" placeholder="{{ __('coreui::coreui.email') }}"
                                    required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('email') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-map-marked-alt"></i></span>
                                </div>

                                <input id="address" type="text"
                                    class="form-control {{ $errors->has('address') ? ' is-invalid' : '' }}"
                                    value="{{ $profile->address }}" name="address"
                                    placeholder="{{ __('coreui::coreui.address') }}" required>
                                @if ($errors->has('address'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('address') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-mobile-alt"></i></span>
                                </div>

                                <input id="hp" type="text"
                                    class="form-control{{ $errors->has('hp') ? ' is-invalid' : '' }}" name="hp"
                                    value="{{ $profile->hp }}" placeholder="{{ __('coreui::coreui.hp') }}" required>
                                @if ($errors->has('hp'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('hp') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-umbrella-beach"></i></span>
                                </div>

                                <input id="hobi" type="text"
                                    class="form-control{{ $errors->has('hobi') ? ' is-invalid' : '' }}" name="hoby"
                                    value="{{ $profile->hoby }}" placeholder="{{ __('coreui::coreui.hoby') }}"
                                    required>
                                @if ($errors->has('hobi'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('hobi') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-user-lock"></i></span>
                                </div>

                                <select class="browser-default custom-select" name="role">
                                    @foreach ($profile->users->roles as $role)
                                        <option value="{{ $role->name }}" selected>
                                            {{ $role->name }}</option>
                                    @endforeach
                                    @foreach ($position_roles->roles as $role)
                                        <option value={{ $role->name }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-building"></i></span>
                                </div>

                                <select class="browser-default custom-select" name="position">
                                    <option value="{{ $profile->position->id }}" selected>
                                        {{ $profile->position->position }}</option>
                                    @foreach ($position_roles->position as $position)
                                        <option value={{ $position->id }}>{{ $position->position }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-block btn-success" type="submit">Update User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <p>Javamas 2021</p>
@endsection

@push('js')

@endpush

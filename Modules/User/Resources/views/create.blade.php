@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Dashboard')

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

                        <form action="{{ route('user.store') }}" method="post">
                            @csrf

                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                                </div>

                                <input id="name" type="text"
                                    class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="name"
                                    value="{{ old('name') }}" placeholder="{{ __('coreui::coreui.full_name') }}"
                                    required autofocus>

                                @if ($errors->has('name'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('name') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                </div>

                                <input id="email" type="email"
                                    class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email"
                                    value="{{ old('email') }}" placeholder="{{ __('coreui::coreui.email') }}" required>

                                @if ($errors->has('email'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('email') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                </div>

                                <input id="password" type="password"
                                    class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                                    placeholder="{{ __('coreui::coreui.password') }}" name="password" required>

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback"
                                        role="alert"><strong>{{ $errors->first('password') }}</strong></span>
                                @endif
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-redo"></i></span>
                                </div>

                                <input id="password-confirm" type="password" class="form-control"
                                    name="password_confirmation" placeholder="{{ __('coreui::coreui.retype_password') }}"
                                    required>
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-map-marked-alt"></i></span>
                                </div>

                                <input id="address" type="text"
                                    class="form-control {{ $errors->has('address') ? ' is-invalid' : '' }}"
                                    name="address" placeholder="{{ __('coreui::coreui.address') }}" required>
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
                                    placeholder="{{ __('coreui::coreui.hp') }}" required>
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
                                    placeholder="{{ __('coreui::coreui.hp') }}" required>
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
                                    <option value="{{ $role_default_id->name }}" selected>default</option>
                                    @foreach ($roles as $role)
                                        <option value={{ $role->name }}>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-building"></i></span>
                                </div>

                                <select class="browser-default custom-select" name="position">
                                    <option value="{{ $position_default_id->id }}" selected>Staff</option>
                                    @foreach ($position as $jab)
                                        <option value={{ $jab->id }}>{{ $jab->position }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-block btn-success" type="submit">Create Account</button>
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

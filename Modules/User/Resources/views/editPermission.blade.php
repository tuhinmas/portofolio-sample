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
                    <div class="card-head">
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('user.update.permission',['id' => $user->id]) }}" method="post">
                            @csrf

                            <table>
                                <section class="p-3 mb-2 bg-secondary text-dark">
                                    <h3>Edit User Permission</h3>
                                </section>
                                <tr>
                                    <td>
                                        Nama :
                                    </td>
                                <tr>
                                    <td>
                                        <h1>{{ $user->name }}</h1>
                                    </td>
                                </tr>
                                </tr>
                                <tr>
                                    <td>
                                        Jabatan :
                                    </td>
                                <tr>
                                    <td>
                                        <h1>{{ $user->profile->position->position }}</h1>
                                    </td>
                                </tr>
                                </tr>
                                <tr>
                                    <td>
                                        Role :
                                    </td>
                                <tr>
                                    <td>
                                        @foreach ($user->roles as $role)
                                            <h1>{{ $role->name }}</h1>
                                        @endforeach
                                    </td>
                                </tr>
                                </tr>
                                <tr>
                                    <td>
                                        Permission :
                                    </td>
                                <tr>
                                    <td>
                                        <ul>
                                            @foreach ($roles as $role)
                                               <li>{{ $role->name }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>

                            </table>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-user-lock"></i></span>
                                </div>

                                <select class="browser-default custom-select" name="permission">
                                    @foreach ($permissions as $permission)
                                        <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-block btn-success" type="submit">Update Permission</button>
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

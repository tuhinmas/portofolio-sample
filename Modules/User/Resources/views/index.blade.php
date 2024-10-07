@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Users')

@section('breadcrumb')
    <li class="breadcrumb-item">a breadcrumb item</li>
@stop

@section('body')
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">
                Daftar User
            </h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Alamat</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $key => $user)
                        <tr>
                            <th scope="row">{{ $key + 1 }}</th>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->position->position }}</td>
                            <td>{{ $user->address }}</td>
                            <td>
                                @foreach ($user->users->roles as $role)
                                    {{ $role->name }}
                                @endforeach
                            </td>
                            <td>
                                @can('tambah akun baru')
                                    <a class="" href="{{ route('user.edit', ['id' => $user->id]) }}">
                                        <i class="fas fa-edit fa-2x" style="color: rgb(0, 0, 173)"></i>
                                    </a>
                                @endcan
                                @can('delete administrator permission')
                                    <a class="ml-3" href="{{ route('user.delete', ['id' => $user->id]) }}">
                                        <i class="fas fa-trash-alt fa-2x" style="color: black"></i>
                                    </a>
                                @endcan
                                @can('edit administrator permission')
                                    <a class="ml-3" href="{{ route('user.edit_permission', ['id' => $user->id]) }}">
                                        <i class="fas fa-key fa-2x" style="color: rgb(151, 51, 5)"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('footer')
    <p>Javamas 2021</p>
@endsection

@push('js')

@endpush

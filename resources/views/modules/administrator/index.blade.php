@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item">administrator/index</li>
@stop

@section('body')
    <h1>Admin Dashboard</h1>
    <div class="card">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Role Name</th>
                    <th>Permission</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $ki => $role)
                    @foreach ($role->permissions as $key => $permission)
                        <tr>
                            <th scope="row"></th></th>
                            <td>{{ $role->name }}</td>
                            <td>{{ $permission->name }}</td>
                            <td>
                                <div class ="d-inline-block">
                                    @can('edit administrator permission')
                                    <form action="{{ route('administrator.edit', ['administrator' => $permission->id]) }}" method="get">
                                        <button class="btn">
                                            <i class="fas fa-edit fa-1x" style="color:rgb(6, 177, 20)"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                               
                                <div class="d-inline-block">
                                    @can('delete administrator permission')
                                        <form action="{{ route('administrator.destroy', ['administrator' => $permission->id]) }}" method="post">
                                            @csrf
                                            @method("DELETE")
                                            <input type="hidden" value="{{ $role->name }}" name="role_name">
                                            <button class="btn"><i class="fa fa-trash-alt fa-1x" style="color: rgb(241, 6, 6)"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@section('footer')
    <p>Javamas 2021</p>
@endsection

@push('js')

@endpush

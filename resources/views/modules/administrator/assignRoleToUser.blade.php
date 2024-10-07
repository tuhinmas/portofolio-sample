@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Dashboard')

@section('breadcrumb')
@stop

@section('body')
    <div class="container">
        <div class="card card-primary">
            <h4 class="card-header">Assign Role to User</h4>
            <div class="card-body">
                <form method=POST action="{{ route('administrator.users.assignRole.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name">User List</label>
                        <div>
                            <select class="browser-default custom-select" name="user">
                                @foreach ($users as $user)
                                    <option value="{{ $user->name }}"">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-3"></div>
                        <label for="name">Assign Role</label>
                        <div>
                            <select class="browser-default custom-select" name="role">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <p>Javamas 2021</p>
@endsection

@push('js')

@endpush

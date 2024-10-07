@extends('coreui::master')

@push('css')

@endpush

@section('title', 'Dashboard')

@section('breadcrumb')
@stop

@section('body')
    <div class="container">
        <div class="card card-primary">
            <h4 class="card-header">Create New Permission</h4>
            <div class="card-body">
                <form method=POST action="{{ route('administrator.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                            required>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
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

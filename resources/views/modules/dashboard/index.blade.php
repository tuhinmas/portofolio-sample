@extends('coreui::master')

@push('css')
    <link rel="stylesheet" type="text/css" href="/url/to/stylesheet.css">
@endpush

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item">a breadcrumb item</li>
@stop

@section('body')
    <h1>Dashboard</h1>
    <p>Welcome to this awesome web app!</p>
@endsection

@section('footer')
    <p>My awesome footer!</p>
@endsection

@push('js')
    <script src="/url/to/script.js"></script>
@endpush
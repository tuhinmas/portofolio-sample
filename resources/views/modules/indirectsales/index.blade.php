@extends('indirectsales::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('indirectsales.name') !!}
    </p>
@endsection

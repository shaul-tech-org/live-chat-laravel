@extends('layouts.app')

@section('body')
<div class="flex items-center justify-center min-h-screen px-4">
    <div class="w-full max-w-md">
        @yield('content')
    </div>
</div>
@endsection

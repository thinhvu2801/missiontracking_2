@extends('layouts.app')

@section('title', 'Dashboard IOC')

@section('content')
<div id="dashboard-app" class="w-full"></div>
@endsection

@push('styles')
@endpush

@push('scripts')
@vite('resources/js/app.js')
@endpush
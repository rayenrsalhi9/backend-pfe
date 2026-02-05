@extends('update.update-layout')

@section('content')
<div class="sm:mx-auto sm:w-full sm:max-w-md">
    @if (file_exists(public_path('logo.png')))
    <div class="text-center">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="width: 50%;height: auto;border-radius: 10px;" class="mx-auto h-12 w-auto">
    </div>
    @endif
</div>

<div class="panel">
    <h3 class="install-completed-header">Update has been successfully completed!</h3>

    <p>You can close this page now and continue using the site.</p>

    <a href="/" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 hover:bg-primary-700">
        Close Update Page
    </a>
</div>
@endsection
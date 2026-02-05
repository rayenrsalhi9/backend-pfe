@extends('update.update-layout')

@section('content')

<div class="sm:mx-auto sm:w-full sm:max-w-md">
    @if (file_exists(public_path('logo.png')))
    <div class="text-center">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" style="width: 50%;height: auto;border-radius: 10px;" class="mx-auto h-12 w-auto">
    </div>
    @endif
</div>

@if(!$isUpdateAvailable)
<div class="panel">
    <h3 class="text-success-500">Great news! No update available at this time. Your system is up-to-date and running smoothly.</h3>

    <div class="-m-7 mt-6 rounded-b border-t border-neutral-200 bg-neutral-50 p-4 text-center">
        <a href="{{ url('login') }}" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 hover:bg-primary-700">
            Login to Continue
        </a>
    </div>
</div>
@endif

@if($isRequirementsErrors)
<div class="panel">
    <h3>Detected the following problems. Please correct them before proceeding with the update.</h3>
    <ul class="errors">
        @foreach($requirements as $req)
        @if(!$req['result'])
        <li>{{$req['errorMessage']}}</li>
        @endif
        @endforeach
    </ul>
</div>
@endif

@if($isUpdateAvailable)
<form class="panel" action="update/run" method="post">
    {{ csrf_field() }}
    <p class="text-success-500">Exciting news! A new update is available for your system. Stay ahead with the latest features, improvements, and enhancements by updating now.</p>
    <p>This might take several minutes, please don't close this browser tab while update is in progress.</p>
    @if($isRequirementsErrors)
    <button class="button bg-primary-600" type="submit" disabled>Update Now</button>
    @else
    <button class="button bg-primary-600" type="submit">Update Now</button>
    @endif
</form>
@endif
@endsection
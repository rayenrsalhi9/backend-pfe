@extends('layouts.installer')

@section('content')
<div class="p-3">
    <h4 class="mt-5 mb-8 text-center text-2xl font-semibold text-success-500">Installation Successfull</h4>

    <p class="text-neutral-700">
        <span class="font-semibold">{{ config('app.name') }} has been successfully installed</span>, now you need to
        set up a cron job:
    </p>

    <div class="mt-4 mb-3 block w-full rounded-md border border-neutral-300 bg-neutral-50 py-4 px-5 text-base shadow-sm">
        * * * * * <span class="select-all"> php {{ base_path() }}/artisan schedule:run
            >> /dev/null 2>&1</span>
    </div>

    <p class="mt-4 text-neutral-700">
        If you are not certain on how to configure the cron job with the minimum required PHP version
        ({{ config('installer.core.minPhpVersion') }}), the best is to consult with your hosting provider.
    </p>

    <p class="mt-4 text-neutral-700">
        On some <span class="font-medium">shared hostings you may need to specify full path</span> to the PHP executable
        (for example, <code class="select-all bg-danger-100 px-2">/usr/local/bin/php{{ str_replace('.', '', config('installer.core.minPhpVersion')) }}</code>
        or <code class="select-all bg-danger-100 px-2">/opt/alt/php{{ str_replace('.', '', config('installer.core.minPhpVersion')) }}/usr/bin/php</code>instead
        of <code class="bg-danger-100 px-2">php</code>), additionally, you can refer to our docs in order to read more about cron job configuration.
    </p>

    <h4 class="mt-5 mb-2 text-lg font-semibold text-neutral-800">Admin Credentials</h4>

    <p>
        <span class="font-semibold text-neutral-700">Email:</span> <span class="select-all">{{ $user->email }}</span><br />
        <span class="font-semibold text-neutral-700">Password:</span> <span>your chosen password</span>
    </p>
</div>

<div class="-m-7 mt-6 rounded-b border-t border-neutral-200 bg-neutral-50 p-4 text-right">
    <a href="{{ url('login') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 hover:bg-primary-700">
        Login
    </a>
</div>

@endsection
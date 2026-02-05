@extends('layouts.installer')

@section('content')
<form action="{{ url('install/setup') }}" id="setup-form" method="POST">
    @csrf
    <div class="p-3">
        <h5 class="text-lg my-5 text-neutral-800 font-semibold">General Config</h5>

        <div class="space-y-6 sm:space-y-5">

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="appUrlName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>App URL
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('app_url', $guessedUrl) }}" name="app_url" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="appUrlName">
                    <p class="mt-2 text-sm text-neutral-500">* This is the URL where you are installing the application,
                        for example, for subdomain in this field you need to enter "https://subdomain.example.com/",
                        make sure to check the documentation on how to create your subdomain.</p>
                    @error('app_url')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputAppName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Application Name
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('app_name', config('app.name')) }}" name="app_name" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputAppName">
                    @error('app_name')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
        </div>

        <h5 class="text-lg mb-5 mt-10 text-neutral-800 font-semibold">Database Configuration</h5>

        @error('privilege')
        <div class="text-danger-500 p-4 bg-danger-50 border border-danger-200 mb-5 rounded-md text-sm">
            <p class="mb-2">
                {{ $message }}
            </p>
            <p class="font-bold">Make sure to give <span class="font-bold">all privileges to the database
                    user</span>, check the installation video in the documentation.</p>
        </div>
        @enderror

        <div class="space-y-6 sm:space-y-5">
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputDatabaseHostname" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Hostname
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('database_hostname', 'localhost') }}" name="database_hostname" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputDatabaseHostname">
                    @error('database_hostname')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputDatabasePort" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Port
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('database_port', '3306') }}" name="database_port" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputDatabasePort">
                    <p class="mt-2 text-sm text-neutral-500">* The default MySQL ports is 3306, change the value only if
                        you are certain that you are using different port.</p>
                    @error('database_port')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputDatabaseName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Database Name
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('database_name') }}" name="database_name" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputDatabaseName">
                    <p class="mt-2 text-sm text-neutral-500">* Make sure that you have created the database before
                        configuring.</p>
                    @error('database_name')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputDatabaseUsername" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Database Username
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" value="{{ old('database_username') }}" name="database_username" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputDatabaseUsername">
                    <p class="mt-2 text-sm text-neutral-500">* Make sure you have set ALL privileges for the user.</p>
                    @error('database_username')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputDatabasePassword" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    Database Password
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="password" name="database_password" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputDatabasePassword">
                    <p class="mt-2 text-sm text-neutral-500">* Enter the database user password.</p>
                    @error('database_password')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    <div class="-m-7 -mb-11 p-4 mt-6 bg-neutral-50 border-t border-neutral-200 text-right rounded-b">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-60 disabled:pointer-events-none" id="btn-setup">Test Connection &amp; Configure</button>
    </div>
</form>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('setup-form').onsubmit = function(e) {
            document.getElementById('btn-setup').disabled = true;
            document.getElementById('btn-setup').innerText = 'Please wait...';
        }
    });
</script>
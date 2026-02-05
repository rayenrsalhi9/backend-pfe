@extends('layouts.installer')

@section('content')
<form action="{{ url('install/user') }}" id="user-form" method="POST">
    @csrf
    <div class="p-3">
        <h5 class="text-lg my-5 text-neutral-800 font-semibold">Configure Admin User</h5>
        <div class="space-y-6 sm:space-y-5">
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputUserFName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>First Name
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" name="firstName" placeholder="Enter your first name" value="{{ old('firstName') }}" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputUserFName">
                    @error('firstName')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputUserLName" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Last Name
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="text" name="lastName" placeholder="Enter your last name" value="{{ old('lastName') }}" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputUserLName">
                    @error('lastName')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputEmail" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>E-Mail Address
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="email" value="{{ old('email') }}" name="email" placeholder="Enter your email address that will be used for login" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputEmail">
                    @error('email')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputPassword" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Password
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="password" name="password" placeholder="Login password" autocomplete="new-password" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputPassword">
                    @error('password')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>

            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-neutral-200 sm:pt-5">
                <label for="inputPasswordConfirm" class="block text-sm font-medium text-neutral-700 sm:mt-px sm:pt-2">
                    <span class="text-danger-600 text-sm mr-1">*</span>Confirm Password
                </label>
                <div class="mt-1 sm:mt-0 sm:col-span-2">
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Confirm login password" class="block w-full shadow-sm focus:ring-primary-500 border focus:border-primary-500 border-neutral-300 sm:text-sm rounded-md" id="inputPasswordConfirm">
                    @error('password_confirmation')
                    <p class="mt-2 text-sm text-danger-600">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    <div class="-m-7 -mb-11 p-4 mt-6 bg-neutral-50 border-t border-neutral-200 text-right rounded-b">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-60 disabled:pointer-events-none" id="btn-install">Install</button>
    </div>
</form>
@endsection
<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        document.getElementById('user-form').onsubmit = function(e) {
            document.getElementById('btn-install').disabled = true;
            document.getElementById('btn-install').innerText = 'Please wait...';
        }
    });
</script>
<div class="p-3">
    <h4 class="text-lg my-5 text-neutral-800 font-semibold">Files and folders permissions</h4>
    <p class="text-neutral-700">
        These folders must be writable by web server user: <strong
            class="select-all">{{ get_current_user() }}</strong>
        <br />Recommended permissions: <strong class="select-all">0775</strong><br /><br />
    </p>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-neutral-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Path
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                    Permission
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-neutral-200">
                            @foreach ($permissions['results'] as $permission)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-neutral-900">
                                        {{ rtrim($permission['folder'], '/') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-neutral-900">
                                        <span
                                            class="inline-flex {{ $permission['isSet'] ? 'text-success-500' : 'text-warning-500' }}">
                                            @if ($permission['isSet'])
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $permission['permission'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

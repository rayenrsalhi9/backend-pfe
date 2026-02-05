<div class="p-3">
    @if ($memoryLimitMB !== -1 && $memoryLimitMB !== 0 && $memoryLimitMB <= 64)
        <div class="px-4 py-3 border border-warning-100 rounded bg-warning-50">
            <h3 class="text-warning-800 mb-3">Low PHP Memory Limit</h3>

            <p class="text-sm text-warning-800">
                The installer detected that the server <span class="font-medium">PHP memory limit</span> is set to
                <span class="font-medium">{{ $memoryLimitRaw }}</span>. It's
                <span class="font-medium">strongly recommended</span> to increase the memory limit to at least
                <span class="font-medium">128M</span> to avoid any failures during installation or while using the Document Management System.
            </p>

            <p class="mt-2 text-sm text-warning-800">
                When logged-in to the server control panel, perform a search for e.q. <span class="font-medium">"PHP
                    settings</span>", <span class="font-medium">"Select PHP
                    Version</span>", <span class="font-medium">"PHP INI Editor</span>", or <span
                    class="font-medium">"PHP Options</span>" or any other related options for your control panel in
                order to increase the memory limit, or consult with your hosting provider to do this for you.
            </p>
        </div>
    @endif


    <h4 class="text-lg my-5 text-neutral-800 font-semibold">PHP Version</h4>
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-neutral-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Required PHP Version
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Current
                            </th>
                        </thead>
                        <tbody class="bg-white divide-y divide-neutral-200">
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-neutral-900">
                                >= {{ $php['minimum'] }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-neutral-900">
                                <span
                                    class="inline-flex {{ $php['supported'] ? 'text-success-500' : 'text-warning-500' }}">
                                    @if ($php['supported'])
                                        @include('installer.passes-icon')
                                    @endif
                                    {{ $php['current'] }}
                                </span>
                            </td>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h4 class="text-lg mb-5 mt-10 text-neutral-800 font-semibold">Required PHP Extensions</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-neutral-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Extension
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Enabled
                            </th>

                        </thead>
                        <tbody>
                            @foreach ($requirements['results']['php'] as $requirement => $enabled)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-neutral-900">
                                        {{ $requirement }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-neutral-900">
                                        <span
                                            class="inline-flex {{ $enabled ? 'text-success-500' : 'text-warning-500' }}">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
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

    <h4 class="text-lg mb-5 mt-10 text-neutral-800 font-semibold">Recommended PHP Extensions</h4>

    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-neutral-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Extension
                            </th>
                            <th scope="col"
                                class="px-4 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">
                                Enabled
                            </th>

                        </thead>
                        <tbody>
                            @foreach ($requirements['recommended']['php'] as $requirement => $enabled)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-neutral-900">
                                        {{ $requirement }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-neutral-900">
                                        <span
                                            class="inline-flex {{ $enabled ? 'text-success-500' : 'text-warning-500' }}">
                                            @if ($enabled)
                                                @include('installer.passes-icon')
                                            @endif
                                            {{ $enabled ? 'Yes' : 'No' }}
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

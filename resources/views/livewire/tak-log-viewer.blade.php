<div class="px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold text-white">{{ __('TAK Forwarding Logs') }}</h1>
            <p class="mt-2 text-sm text-gray-300">A record of all Cursor on Target (CoT) XML events automatically forwarded to the TAK network.</p>
        </div>
    </div>
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="ring-1 ring-white/15 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-white/15">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">Time</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Device</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Target</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">CoT XML Preview</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/15">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-white sm:pl-6">
                                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">
                                        {{ $log->device_id }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">
                                        {{ $log->target }}
                                    </td>
                                    <td class="px-3 py-4 text-xs font-mono text-gray-400">
                                        {{ Str::limit($log->cot_xml, 100) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">
                                        No logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        {{ $logs->links(data: ['scrollTo' => false]) }}
    </div>
</div>
<div>
    <div class="sm:flex sm:items-center mb-4">
        <div class="sm:flex-auto">
            <p class="text-sm text-gray-600 dark:text-gray-300">A record of all Cursor on Target (CoT) XML events automatically forwarded to the TAK network.</p>
        </div>
    </div>
    <div class="flow-root">
        <div class="-mx-4 sm:mx-0 sm:rounded-lg ring-1 ring-gray-200 dark:ring-white/15">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/15">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">Time</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Device</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">Target</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white">CoT XML Preview</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/15">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 dark:text-white sm:pl-6">
                                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $log->device_id }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $log->target }}
                                    </td>
                                    <td class="px-3 py-4 text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($log->cot_xml, 100) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
        </div>
    </div>
    <div class="mt-4">
        {{ $logs->links(data: ['scrollTo' => false]) }}
    </div>
</div>
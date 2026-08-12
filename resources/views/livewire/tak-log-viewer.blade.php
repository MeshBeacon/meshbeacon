<div class="p-6">
    <flux:heading size="xl" class="mb-6">{{ __('TAK Forwarding Logs') }}</flux:heading>

    <flux:card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Device</th>
                        <th class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CoT XML Preview</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $log->device_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $log->target }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400 font-mono text-xs">
                                {{ Str::limit($log->cot_xml, 100) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-zinc-500">
                                No logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </flux:card>
</div>
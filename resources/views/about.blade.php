@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">{{ __('About MeshBeacon') }}</h2>
        <p class="text-gray-400">
            {{ __('MeshBeacon is an open-source, offline-first mesh networking platform built for disaster response, field operations, and incident management.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-gray-800/50 rounded-lg p-6 ring-1 ring-white/10">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-yellow-500"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                {{ __('Open Source') }}
            </h3>
            <p class="text-sm text-gray-400 mb-4">
                {{ __('MeshBeacon is actively developed in the open. Contributions, bug reports, and feature requests are always welcome.') }}
            </p>
            <a href="https://github.com/MeshBeacon/meshbeacon" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-yellow-500 hover:text-yellow-400">
                {{ __('View upstream repository on GitHub') }} &rarr;
            </a>
        </div>

        <div class="bg-gray-800/50 rounded-lg p-6 ring-1 ring-white/10">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-yellow-500"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                {{ __('Community & Resources') }}
            </h3>
            <p class="text-sm text-gray-400 mb-4">
                {{ __('Learn more about the project, discover hardware builds, and join our global community.') }}
            </p>
            <a href="https://meshbeacon.org" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-yellow-500 hover:text-yellow-400">
                {{ __('Visit meshbeacon.org') }} &rarr;
            </a>
        </div>
    </div>

    <div class="mt-8 bg-gray-800/50 rounded-lg p-6 ring-1 ring-white/10">
        <h3 class="text-lg font-semibold text-white mb-4">{{ __('Contributors') }}</h3>
        <p class="text-sm text-gray-400 mb-4">
            {{ __('This project is made possible by dedicated volunteers, hardware hackers, and developers globally who believe in resilient communications.') }}
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="https://github.com/muhammadn" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-1.5 text-sm font-medium text-gray-300 hover:text-yellow-400 hover:ring-yellow-500/50 ring-1 ring-inset ring-white/10 transition-colors">
                Muhammad Nuzaihan Bin Kamal Luddin
            </a>
            <a href="https://hamradio.my" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-gray-900 px-4 py-1.5 text-sm font-medium text-gray-300 hover:text-yellow-400 hover:ring-yellow-500/50 ring-1 ring-inset ring-white/10 transition-colors">
                9M2PJU
            </a>
        </div>
    </div>
</div>
@endsection

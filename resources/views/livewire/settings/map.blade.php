<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $mapFile;
    public $successMessage = '';
    public $errorMessage = '';
    public $useOfflineMap = true;

    public function mount()
    {
        $this->useOfflineMap = !File::exists(storage_path('app/use_osm_map.flag'));
    }

    public function updatedUseOfflineMap($value)
    {
        if ($value) {
            File::delete(storage_path('app/use_osm_map.flag'));
            $this->successMessage = __('Map preference updated to Offline Map (if installed).');
        } else {
            File::put(storage_path('app/use_osm_map.flag'), '1');
            $this->successMessage = __('Map preference updated to OpenStreetMap (Online).');
        }
        $this->errorMessage = '';
    }

    public function uploadMap()
    {
        $this->validate([
            'mapFile' => 'required|file|mimetypes:application/vnd.sqlite3,application/octet-stream,application/x-sqlite3|max:512000', // 500MB max
        ]);

        try {
            $destinationPath = config('services.map.mbtiles_path');
            
            // Ensure the directory exists
            $dir = dirname($destinationPath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            // Move the uploaded file to the configured destination
            $path = $this->mapFile->storeAs('/', 'map.mbtiles', 'local');

            if ($path) {
                // Get the absolute path from the Storage facade (this handles Laravel 11's 'private' default correctly)
                $storedFilePath = Storage::disk('local')->path($path);
                
                if ($storedFilePath !== $destinationPath) {
                    if (File::exists($destinationPath)) {
                        File::delete($destinationPath);
                    }
                    File::move($storedFilePath, $destinationPath);
                }
                
                $this->successMessage = __('Offline map successfully uploaded and installed!');
                $this->mapFile = null; // Reset
            } else {
                throw new \Exception("Failed to store uploaded file.");
            }
        } catch (\Exception $e) {
            Log::error("Failed to upload offline map: " . $e->getMessage());
            $this->errorMessage = __('An error occurred while uploading the map. Check logs for details.');
        }
    }
}; ?>

<x-settings.layout :heading="__('Offline Map')" :subheading="__('Configure map rendering options and upload local tile data.')">
    <div class="mt-6">
        <div class="mb-8">
            <flux:switch wire:model.live="useOfflineMap" :label="__('Prefer Offline Map')" :description="__('When enabled, the system will always use the installed offline map (if available). When disabled, it will default to OpenStreetMap online tiles.')" />
        </div>

        <div class="relative py-4">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200 dark:border-white/10"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white dark:bg-gray-900 px-3 text-sm font-medium text-gray-900 dark:text-white">{{ __('Upload Map Data') }}</span>
            </div>
        </div>

        <form wire:submit="uploadMap" class="space-y-6 max-w-xl mt-6">
            @if ($successMessage)
                <div class="rounded-md bg-green-500/10 p-4 ring-1 ring-inset ring-green-500/30">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-700 dark:text-green-400">{{ $successMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errorMessage)
                <div class="rounded-md bg-red-500/10 p-4 ring-1 ring-inset ring-red-500/30">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <flux:field>
                <flux:label>{{ __('Select Map File (.mbtiles)') }}</flux:label>
                
                <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-300 dark:border-white/25 px-6 py-10">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M11.47 2.47a.75.75 0 011.06 0l4.5 4.5a.75.75 0 01-1.06 1.06l-3.22-3.22V16.5a.75.75 0 01-1.5 0V4.81L8.03 8.03a.75.75 0 01-1.06-1.06l4.5-4.5zM3 15.75a.75.75 0 01.75.75v2.25a1.5 1.5 0 001.5 1.5h13.5a1.5 1.5 0 001.5-1.5V16.5a.75.75 0 011.5 0v2.25a3 3 0 01-3 3H5.25a3 3 0 01-3-3V16.5a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                        </svg>
                        <div class="mt-4 flex text-sm leading-6 text-gray-500 dark:text-gray-400 justify-center">
                            <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold text-orange-600 dark:text-yellow-500 hover:text-orange-500 dark:hover:text-yellow-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-orange-500 dark:focus-within:ring-yellow-500 focus-within:ring-offset-2 dark:focus-within:ring-offset-gray-900">
                                <span>{{ __('Upload a file') }}</span>
                                <input id="file-upload" wire:model="mapFile" type="file" accept=".mbtiles" class="sr-only">
                            </label>
                            <p class="pl-1">{{ __('or drag and drop') }}</p>
                        </div>
                        <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">{{ __('MBTiles up to 500MB') }}</p>
                    </div>
                </div>

                @if ($mapFile)
                    <div class="mt-2 text-sm text-green-600 dark:text-green-400 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ $mapFile->getClientOriginalName() }} ready to upload.
                    </div>
                @endif
                <flux:error name="mapFile" class="mt-2 text-red-700 dark:text-red-400" />
            </flux:field>

            <div wire:loading wire:target="mapFile">
                <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Processing file...') }}
                </span>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" :disabled="!$mapFile">
                    {{ __('Upload Map') }}
                </flux:button>
            </div>
            
            <div wire:loading wire:target="uploadMap" class="mt-2">
                <span class="text-sm text-gray-500 dark:text-gray-400 flex justify-end items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Uploading and installing map...') }}
                </span>
            </div>
            
            <div class="mt-6 rounded-md bg-blue-500/10 p-4 ring-1 ring-inset ring-blue-500/30">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-700 dark:text-blue-400">{{ __('Current Map Status') }}</p>
                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                            @if(file_exists(config('services.map.mbtiles_path')))
                                {{ __('An offline map is currently installed and ready to be used.') }}
                                <br>
                                <span class="text-xs opacity-75">{{ __('Size: ') . round(filesize(config('services.map.mbtiles_path')) / 1048576, 2) . ' MB' }}</span>
                            @else
                                {{ __('No offline map is currently installed. The system will fall back to online maps when connected to the internet.') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
        </form>
    </div>
</x-settings.layout>

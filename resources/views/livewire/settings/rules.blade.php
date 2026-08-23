<?php

use Livewire\Volt\Component;
use App\Models\Rule;
use Illuminate\Support\Collection;

new class extends Component {
    public Collection $rules;
    public string $name = '';
    public string $condition = 'battery_below';
    public float $threshold = 20;
    public string $action = 'telegram_alert';

    public function mount()
    {
        $this->loadRules();
    }

    public function loadRules()
    {
        $this->rules = Rule::all();
    }

    public function saveRule()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'condition' => 'required|in:battery_below,rssi_below',
            'threshold' => 'required|numeric',
            'action' => 'required|in:telegram_alert,create_incident',
        ]);

        Rule::create([
            'name' => $this->name,
            'condition' => $this->condition,
            'threshold' => $this->threshold,
            'action' => $this->action,
            'is_active' => true,
        ]);

        $this->loadRules();
        $this->reset(['name', 'threshold']);
    }

    public function deleteRule(Rule $rule)
    {
        $rule->delete();
        $this->loadRules();
    }
}; ?>
<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Automated Rules') }}</flux:heading>

    <x-settings.layout>
        <div class="divide-y divide-white/10">
            <div class="grid grid-cols-1 gap-x-8 gap-y-8 py-10 first:pt-0 lg:grid-cols-3">
                <div class="px-4 sm:px-0">
                    <h2 class="text-base/7 font-semibold text-white">{{ __('Automated Rules') }}</h2>
                    <p class="mt-1 text-sm/6 text-gray-400">{{ __('Configure rules to automatically trigger alerts based on telemetry conditions.') }}</p>
                </div>

                <div class="bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10 sm:rounded-xl lg:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <form wire:submit="saveRule" class="grid max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="name" class="block text-sm/6 font-medium text-white">{{ __('Rule Name') }}</label>
                                <div class="mt-2">
                                    <input type="text" wire:model="name" id="name" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" placeholder="e.g. Low Battery Alert">
                                </div>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label class="block text-sm/6 font-medium text-white">{{ __('Condition') }}</label>
                                <div class="mt-2">
                                    <select wire:model="condition" class="block w-full rounded-md bg-white/5 px-3 py-2 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6">
                                        <option value="battery_below">Battery Below</option>
                                        <option value="rssi_below">RSSI Below</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="sm:col-span-3">
                                <label class="block text-sm/6 font-medium text-white">{{ __('Threshold') }}</label>
                                <div class="mt-2">
                                    <input type="number" wire:model="threshold" step="any" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6" placeholder="e.g. 20">
                                </div>
                            </div>

                            <div class="sm:col-span-6">
                                <button type="submit" class="rounded-md bg-indigo-500 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">
                                    {{ __('Add Rule') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="border-t border-white/10 px-4 py-4 sm:px-8">
                        <ul role="list" class="divide-y divide-white/5">
                            @foreach($rules as $rule)
                            <li class="py-4 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-white">{{ $rule->name }}</p>
                                    <p class="text-sm text-gray-400">If {{ $rule->condition }} is {{ $rule->threshold }}, then {{ $rule->action }}</p>
                                </div>
                                <button wire:click="deleteRule({{ $rule->id }})" class="text-sm font-semibold text-red-600 dark:text-red-400 hover:text-red-500 dark:hover:text-red-300">{{ __('Delete') }}</button>
                            </li>
                            @endforeach
                            @if($rules->isEmpty())
                            <li class="py-4 text-sm text-gray-400">{{ __('No rules configured.') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </x-settings.layout>
</section>

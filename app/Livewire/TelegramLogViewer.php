<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TelegramLog;
use Livewire\WithPagination;

class TelegramLogViewer extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.telegram-log-viewer', [
            'logs' => TelegramLog::orderBy('created_at', 'desc')->paginate(20),
        ])->layout('layouts.app');
    }
}

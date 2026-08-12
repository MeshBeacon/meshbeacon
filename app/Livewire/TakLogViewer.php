<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TakLog;
use Livewire\WithPagination;

class TakLogViewer extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.tak-log-viewer', [
            'logs' => TakLog::orderBy('created_at', 'desc')->paginate(20),
        ])->layout('layouts.app');
    }
}

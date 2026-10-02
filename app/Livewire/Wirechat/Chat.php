<?php

namespace App\Livewire\Wirechat;

class Chat extends \Wirechat\Wirechat\Livewire\Chat\Chat
{
    public function getListeners(): array
    {
        $listeners = parent::getListeners();

        if (!$this->panel()?->hasBroadcasting()) {
            return array_filter($listeners, fn ($event) => !str_starts_with($event, 'echo'), ARRAY_FILTER_USE_KEY);
        }

        return $listeners;
    }
}
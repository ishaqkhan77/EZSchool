<?php

namespace App\Providers;

use App\Models\Branch;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use App\Livewire\Wirechat\Chat as AppChat;
use App\Livewire\Wirechat\Chats as AppChats;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */



    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->app->booted(function (): void {
            Livewire::component('wirechat.chats', AppChats::class);
            Livewire::component('wirechat.chat', AppChat::class);
        });
    }
}

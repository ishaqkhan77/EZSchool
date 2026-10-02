<?php

namespace App\Providers\Wirechat;

use App\Models\User;
use App\Services\ChatAccessService;
use Illuminate\Support\Facades\Auth;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;

class ChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
             ->id('chats')
             ->path('chats')
             ->default()
             ->chatsSearch(false)
             ->redirectToHomeAction()
             ->createChatAction(fn () => Auth::user() instanceof User
                 && app(ChatAccessService::class)->canAccessChat(Auth::user()))
             ->broadcasting(false)
             ->groups(false)
             ->unreadIndicator(true, UnreadIndicatorType::Count)
             ->searchUsersUsing(function (string $search) {
                 $user = Auth::user();

                 return $user instanceof User
                     ? app(ChatAccessService::class)->searchRecipients($user, $search)
                     : collect();
             })
             ->middleware(['web','auth']);
    }
}

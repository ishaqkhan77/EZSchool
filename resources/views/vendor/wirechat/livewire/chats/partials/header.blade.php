@php($homeUrl = $this->panel()->getHomeUrl())

<header class="sticky top-0 z-10 flex w-full items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
    <h2 class="truncate text-base font-semibold text-gray-900 dark:text-white">
        {{ filled($heading) ? $heading : 'Messages' }}
    </h2>

    <div class="flex shrink-0 items-center gap-2">
        @if ($redirectToHomeAction && $homeUrl)
            <a href="{{ $homeUrl }}" aria-label="Back" title="Back" class="inline-flex size-9 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                <x-wirechat::icon
                    :icon="$this->panel()->redirectToHomeActionIcon()"
                    default="wirechat::icons.logout"
                    class="size-5"
                    :icon-attributes="$this->panel()->redirectToHomeActionIconAttributes()"
                />
            </a>
        @endif

        @if ($createChatAction)
            <x-wirechat::actions.new-chat widget="{{ $this->isWidget() }}" panel="{{ $this->panel }}">
                <button id="open-new-chat-modal-button" type="button" class="inline-flex min-h-9 items-center gap-2 rounded-md bg-amber-500 px-3 text-sm font-semibold text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <x-wirechat::icon
                        :icon="$this->panel()->createChatActionIcon()"
                        default="wirechat::icons.messages-plus"
                        class="size-4"
                        :icon-attributes="$this->panel()->createChatActionIconAttributes()"
                    />
                    <span>New message</span>
                </button>
            </x-wirechat::actions.new-chat>
        @endif
    </div>
</header>
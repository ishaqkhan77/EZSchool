<div id="new-chat-modal" class="w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-gray-900 shadow-xl dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:max-w-lg">
    <header class="sticky top-0 z-10 border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between gap-4">
            <h2 class="text-base font-semibold">New message</h2>

            <x-wirechat::actions.close-modal>
                <button type="button" aria-label="Close" title="Close" class="inline-flex size-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </x-wirechat::actions.close-modal>
        </div>

        <label for="users-search-field" class="sr-only">Search people you can message</label>
        <input
            dusk="search_users_field"
            type="search"
            id="users-search-field"
            wire:model.live.debounce="search"
            autocomplete="off"
            placeholder="Search people you can message"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:placeholder:text-gray-400"
        >
    </header>

    <section class="max-h-96 min-h-32 overflow-y-auto p-2" aria-live="polite">
        @if (count($users) > 0)
            <ul class="space-y-1">
                @foreach ($users as $key => $user)
                    <li wire:key="chat-recipient-{{ $user['type'] }}-{{ $user['id'] }}">
                        <button
                            type="button"
                            wire:click="createConversation('{{ $user['id'] }}', {{ json_encode($user['type']) }})"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-gray-800"
                        >
                            <x-wirechat::avatar :src="$user['wirechat_avatar_url']" class="size-10 shrink-0" />
                            <span class="truncate text-sm font-medium">{{ $user['wirechat_name'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @elseif (filled($search))
            <p class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No eligible people match that search.</p>
        @else
            <p class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Search by name to find someone you can message.</p>
        @endif
    </section>
</div>

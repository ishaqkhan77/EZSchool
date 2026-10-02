<div class="-mx-4 -mt-4 flex h-[calc(100dvh-8.5rem)] min-h-[32rem] overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-950 md:-mx-6 md:-mt-6">
    <aside @class([
        'flex min-h-0 w-full flex-col md:w-[22rem] md:shrink-0 md:border-r md:border-gray-200 dark:md:border-gray-700',
        'hidden md:flex' => filled($activeConversationId),
    ])>
        <header class="space-y-3 border-b border-gray-200 px-4 py-4 dark:border-gray-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Messages</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Conversations with your students' families</p>
                </div>
                <button
                    type="button"
                    wire:click="showNewMessageFlow"
                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-md bg-amber-500 px-3 text-xs font-semibold text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950"
                >
                    <x-filament::icon icon="heroicon-m-plus" class="size-4" />
                    <span>New Message</span>
                </button>
            </div>

            <label class="sr-only" for="teacher-inbox-search">Search conversations by student</label>
            <input
                id="teacher-inbox-search"
                type="search"
                wire:model.live.debounce.250ms="studentSearch"
                placeholder="Search student..."
                autocomplete="off"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400"
            >
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto p-2">
            @forelse ($this->getTeacherInboxConversations() as $item)
                @php
                    $student = $item['student'];
                    $parent = $item['parent'];
                    $conversationId = $item['conversation_id'];
                    $isActive = (string) $activeConversationId === (string) $conversationId;
                @endphp
                <button
                    type="button"
                    wire:key="teacher-inbox-{{ $conversationId }}"
                    wire:click="openInboxConversation('{{ $student->id }}', '{{ $parent->id }}', '{{ $conversationId }}')"
                    @class([
                        'mb-1 flex w-full items-start gap-3 rounded-md px-3 py-3 text-left transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-gray-900',
                        'bg-amber-50 dark:bg-gray-900' => $isActive,
                    ])
                >
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200" aria-hidden="true">
                        {{ str($student->full_name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-baseline justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $student->full_name }}</span>
                            <span class="shrink-0 text-[11px] text-gray-500 dark:text-gray-400">
                                {{ $item['last_message_at']?->diffForHumans(short: true) ?? '' }}
                            </span>
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ implode(', ', $student->teacher_class_labels ?? []) }}
                        </span>
                        <span class="mt-1.5 block truncate text-xs text-gray-600 dark:text-gray-300">
                            <span class="font-medium">{{ $item['relationship'] }} · {{ $parent->full_name }}</span>
                            <span class="text-gray-400 dark:text-gray-500"> · </span>
                            <span>{{ $item['last_message'] }}</span>
                        </span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ filled($studentSearch) ? 'No conversations match that student.' : 'No conversations yet.' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Start a message from one of your assigned students.</p>
                    @if (!filled($studentSearch))
                        <button type="button" wire:click="showNewMessageFlow" class="mt-4 text-sm font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400">
                            Start a new message
                        </button>
                    @endif
                </div>
            @endforelse
        </div>
    </aside>

    <main @class([
        'min-w-0 flex-1 bg-gray-50 dark:bg-gray-900/50',
        'hidden md:flex md:flex-col' => blank($activeConversationId),
        'fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-950 md:static md:z-auto' => filled($activeConversationId),
    ])>
        @if ($activeConversationId)
            <div class="flex h-12 shrink-0 items-center gap-3 border-b border-gray-200 px-3 dark:border-gray-700 md:hidden">
                <button type="button" wire:click="backToInbox" aria-label="Back to conversations" class="inline-flex size-9 items-center justify-center rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
                    <x-filament::icon icon="heroicon-m-arrow-left" class="size-5" />
                </button>
                <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $this->getSelectedStudent()?->full_name ?? 'Conversation' }}
                </span>
            </div>
            <div class="min-h-0 flex-1 overflow-hidden bg-white dark:bg-gray-950">
                @livewire('wirechat.chat', ['panel' => 'chats', 'conversation' => $activeConversationId], key('teacher-parent-chat-' . $activeConversationId))
            </div>
        @else
            <div class="hidden h-full items-center justify-center px-8 text-center md:flex">
                <div class="max-w-sm">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Your student conversations</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Choose a conversation from the inbox, or start a new message with a student's parent or guardian.</p>
                </div>
            </div>
        @endif
    </main>

    @if ($showNewMessage)
        <div class="fixed inset-0 z-[70] flex items-end justify-center bg-gray-950/50 sm:items-center sm:p-6" wire:key="new-student-message-modal">
            <section role="dialog" aria-modal="true" aria-labelledby="new-message-heading" class="flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-950 sm:max-w-lg sm:rounded-lg">
                <header class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 id="new-message-heading" class="text-base font-semibold text-gray-900 dark:text-white">New Message</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if ($this->getNewMessageStudent())
                                Select a parent or guardian for {{ $this->getNewMessageStudent()->full_name }}.
                            @else
                                Select a student to find their parents or guardians.
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="hideNewMessageFlow" aria-label="Close" class="inline-flex size-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <x-filament::icon icon="heroicon-m-x-mark" class="size-5" />
                    </button>
                </header>

                @if ($student = $this->getNewMessageStudent())
                    <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                        <button type="button" wire:click="$set('newMessageStudentId', null)" class="text-xs font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400">Change student</button>
                        <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $student->full_name }}</span>
                        <span class="truncate text-xs text-gray-500 dark:text-gray-400">{{ implode(', ', $student->teacher_class_labels ?? []) }}</span>
                    </div>
                    <div class="min-h-32 flex-1 overflow-y-auto p-2">
                        @forelse ($student->parents as $parent)
                            <button
                                type="button"
                                wire:key="new-message-parent-{{ $parent->id }}"
                                wire:click="startNewMessage('{{ $parent->id }}')"
                                @disabled(!$parent->user)
                                class="flex w-full items-center gap-3 rounded-md px-3 py-3 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-gray-900"
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200" aria-hidden="true">
                                    {{ str($parent->full_name)->substr(0, 1)->upper() }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-white">{{ $parent->full_name }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($parent->pivot->relationship_type ?: 'Guardian') }}</span>
                                </span>
                                @if (!$parent->user)
                                    <span class="text-xs text-gray-400">No account</span>
                                @else
                                    <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="size-4 text-amber-600 dark:text-amber-400" />
                                @endif
                            </button>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No parents or guardians are linked to this student.</p>
                        @endforelse
                    </div>
                @else
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <label class="sr-only" for="new-message-student-search">Search students</label>
                        <input
                            id="new-message-student-search"
                            type="search"
                            wire:model.live.debounce.250ms="newMessageStudentSearch"
                            placeholder="Search student..."
                            autocomplete="off"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-400"
                        >
                    </div>
                    <div class="min-h-32 flex-1 overflow-y-auto p-2">
                        @forelse ($this->getNewMessageStudents() as $student)
                            <button type="button" wire:key="new-message-student-{{ $student->id }}" wire:click="chooseNewMessageStudent('{{ $student->id }}')" class="flex w-full items-center gap-3 rounded-md px-3 py-3 text-left hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-gray-900">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200" aria-hidden="true">
                                    {{ str($student->full_name)->substr(0, 1)->upper() }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-white">{{ $student->full_name }}</span>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ implode(', ', $student->teacher_class_labels ?? []) }}</span>
                                </span>
                                <x-filament::icon icon="heroicon-m-chevron-right" class="size-4 text-gray-400" />
                            </button>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No assigned students match that search.</p>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>

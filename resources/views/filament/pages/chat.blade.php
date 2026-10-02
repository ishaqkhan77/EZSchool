<x-filament-panels::page>
    @if ($this->isTeacherInbox())
        @include('filament.pages.teacher-chat-inbox')
    @else
        <div class="-mx-4 -mt-4 h-[calc(100dvh-9rem)] min-h-[32rem] overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 md:-mx-6 md:-mt-6">
            @livewire('wirechat', ['panel' => 'chats'])
        </div>
    @endif
</x-filament-panels::page>

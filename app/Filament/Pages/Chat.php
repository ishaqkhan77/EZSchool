<?php

namespace App\Filament\Pages;

use App\Models\Student;
use App\Models\User;
use App\Services\ChatAccessService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Wirechat\Wirechat\Facades\Wirechat;

class Chat extends Page
{
    protected string $view = 'filament.pages.chat';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    // Label on the sidebar
    protected static ?string $navigationLabel = 'Messages';

    // Page title
    protected static ?string $title = 'Messages';

    // Navigation position
    protected static ?int $navigationSort = 2;

    public ?string $studentSearch = null;

    public ?string $selectedStudentId = null;

    public ?string $newMessageStudentSearch = null;

    public ?string $newMessageStudentId = null;

    public ?string $activeConversationId = null;

    public bool $showNewMessage = false;

    public function mount(): void
    {
        if (!$this->isTeacherInbox() || !request()->filled('conversation_id')) {
            return;
        }

        $studentId = request()->query('student_id');
        $user = Auth::user();
        $conversation = Wirechat::conversationModelClass()::find(request()->query('conversation_id'));
        $student = $studentId ? $this->getTeacherStudents()->firstWhere('id', $studentId) : null;

        abort_unless(
            $student instanceof Student
                && $user instanceof User
                && $conversation
                && app(ChatAccessService::class)->canOpenStudentParentConversation($user, $student, $conversation),
            403
        );

        $this->activeConversationId = (string) $conversation->id;
        $this->selectedStudentId = (string) $student->id;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function isTeacherInbox(): bool
    {
        $user = Auth::user();
        $branchId = Filament::getTenant()?->getKey();

        if (!$user instanceof User || !$branchId) {
            return false;
        }

        $access = app(ChatAccessService::class);

        return !$access->hasRoleInBranch($user, 'Super Admin', (string) $branchId)
            && !$access->hasRoleInBranch($user, 'Admin', (string) $branchId)
            && $access->hasRoleInBranch($user, 'Teacher', (string) $branchId);
    }

    public function getTeacherStudents()
    {
        $user = Auth::user();

        if (!$this->isTeacherInbox() || !$user instanceof User) {
            return collect();
        }

        return app(ChatAccessService::class)->teacherStudents($user, $this->studentSearch ?? '');
    }

    public function getTeacherInboxConversations()
    {
        $user = Auth::user();

        if (!$this->isTeacherInbox() || !$user instanceof User) {
            return collect();
        }

        return app(ChatAccessService::class)->teacherInboxConversations($user, $this->studentSearch ?? '');
    }

    public function openInboxConversation(string $studentId, string $parentId, string $conversationId): void
    {
        abort_unless($this->isTeacherInbox(), 403);

        $user = Auth::user();
        if (!$user instanceof User) {
            abort(403);
        }

        $student = $this->getTeacherStudents()->firstWhere('id', $studentId);
        abort_unless($student instanceof Student, 404);

        $access = app(ChatAccessService::class);
        $conversation = Wirechat::conversationModelClass()::findOrFail($conversationId);
        abort_unless($access->canOpenStudentParentConversation($user, $student, $conversation), 403);

        $peerId = $conversation->participants()
            ->where('participantable_type', $user->getMorphClass())
            ->where('participantable_id', '!=', $user->getKey())
            ->value('participantable_id');
        abort_unless((string) $peerId === (string) $student->parents()->whereKey($parentId)->value('user_id'), 403);

        $this->selectedStudentId = (string) $student->id;
        $this->activeConversationId = (string) $conversation->id;
        $this->showNewMessage = false;
        $this->dispatch('refresh-chats');
    }

    public function showNewMessageFlow(): void
    {
        abort_unless($this->isTeacherInbox(), 403);

        $this->showNewMessage = true;
        $this->newMessageStudentId = null;
        $this->newMessageStudentSearch = null;
    }

    public function hideNewMessageFlow(): void
    {
        $this->showNewMessage = false;
        $this->newMessageStudentId = null;
        $this->newMessageStudentSearch = null;
    }

    public function chooseNewMessageStudent(string $studentId): void
    {
        abort_unless($this->isTeacherInbox(), 403);

        $student = app(ChatAccessService::class)
            ->teacherStudents(Auth::user(), $this->newMessageStudentSearch ?? '')
            ->firstWhere('id', $studentId);
        abort_unless($student instanceof Student, 404);

        $this->newMessageStudentId = (string) $student->id;
    }

    public function getNewMessageStudents()
    {
        $user = Auth::user();

        if (!$this->isTeacherInbox() || !$user instanceof User) {
            return collect();
        }

        return app(ChatAccessService::class)->teacherStudents($user, $this->newMessageStudentSearch ?? '');
    }

    public function getNewMessageStudent(): ?Student
    {
        if (!$this->newMessageStudentId) {
            return null;
        }

        return $this->getNewMessageStudents()->firstWhere('id', $this->newMessageStudentId);
    }

    public function startNewMessage(string $parentId): void
    {
        abort_unless($this->isTeacherInbox(), 403);

        $user = Auth::user();
        $student = $this->getNewMessageStudent();
        abort_unless($user instanceof User && $student instanceof Student, 404);

        $conversation = app(ChatAccessService::class)
            ->startStudentParentConversation($user, $student, $parentId);
        abort_unless($conversation, 500);

        $this->selectedStudentId = (string) $student->id;
        $this->activeConversationId = (string) $conversation->id;
        $this->hideNewMessageFlow();
        $this->dispatch('refresh-chats');
    }

    public function backToInbox(): void
    {
        $this->activeConversationId = null;
    }

    public function getSelectedStudent(): ?Student
    {
        if (!$this->selectedStudentId) {
            return null;
        }

        return $this->getTeacherStudents()->firstWhere('id', $this->selectedStudentId);
    }

    public function getSelectedStudentParentSummaries()
    {
        $student = $this->getSelectedStudent();
        $user = Auth::user();

        if (!$student || !$user instanceof User) {
            return collect();
        }

        return app(ChatAccessService::class)->studentParentConversationSummaries($user, $student);
    }
}

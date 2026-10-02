<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Facades\Wirechat;

class ChatAccessService
{
    private const ADMIN_ROLES = ['Super Admin', 'Admin'];

    private const CHAT_ROLES = ['Super Admin', 'Admin', 'Teacher', 'Parent'];

    public function canAccessChat(User $user): bool
    {
        return $this->roleAssignments($user)
            ->contains(fn ($assignment) => in_array($assignment->name, self::CHAT_ROLES, true));
    }

    public function hasRoleInAnyBranch(User $user, string $role): bool
    {
        return $this->roleAssignments($user)->contains('name', $role);
    }

    public function hasRoleInBranch(User $user, string $role, string $branchId): bool
    {
        return $this->roleAssignments($user)
            ->contains(fn ($assignment) => $assignment->name === $role && (string) $assignment->branch_id === $branchId);
    }

    public function canAccessAdminPanel(User $user): bool
    {
        return DB::table('model_has_roles as mhr')
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->join('branch_user as bu', function ($join) {
                $join->on('bu.user_id', '=', 'mhr.model_id')
                    ->on('bu.branch_id', '=', 'mhr.branch_id');
            })
            ->where('mhr.model_id', $user->getKey())
            ->where('mhr.model_type', $user->getMorphClass())
            ->whereNotNull('mhr.branch_id')
            ->where('roles.guard_name', 'web')
            ->where(function ($query) {
                $query->whereIn('roles.name', ['Super Admin', 'Admin', 'Teacher'])
                    ->orWhereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('role_has_permissions')
                            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                            ->whereColumn('role_has_permissions.role_id', 'roles.id')
                            ->where('permissions.name', 'Access:AdminPanel')
                            ->where('permissions.guard_name', 'web');
                    });
            })
            ->exists();
    }

    public function canStartConversation(User $sender, mixed $recipient): bool
    {
        return $recipient instanceof User
            && $recipient->isNot($sender)
            && $this->recipientIds($sender)->contains((string) $recipient->getKey());
    }

    public function startConversation(User $sender, User $recipient): ?Conversation
    {
        abort_unless($this->canStartConversation($sender, $recipient), 403);

        return $sender->createConversationWith($recipient);
    }

    public function searchRecipients(User $sender, string $search): Collection
    {
        $recipientIds = $this->recipientIds($sender);

        if ($recipientIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $recipientIds)
            ->where('id', '!=', $sender->getKey())
            ->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function teacherStudents(User $teacher, string $search = ''): Collection
    {
        $employee = $this->teacherEmployee($teacher);

        if (!$employee) {
            return collect();
        }

        $assignments = DB::table('class_teacher as ct')
            ->join('teachers as t', 't.id', '=', 'ct.teacher_id')
            ->join('class_student as cs', function ($join) {
                $join->on('cs.class_id', '=', 'ct.class_id')
                    ->on('cs.academic_year', '=', 'ct.academic_year');
            })
            ->join('students as s', 's.id', '=', 'cs.student_id')
            ->join('classes as c', function ($join) {
                $join->on('c.id', '=', 'ct.class_id')
                    ->on('c.branch_id', '=', 's.branch_id');
            })
            ->where('t.employee_id', $employee->id)
            ->where('c.branch_id', $employee->branch_id)
            ->when(trim($search) !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $needle = '%' . addcslashes($term, '%_\\') . '%';
                    $query->where(function ($query) use ($needle) {
                        $query->where('s.first_name', 'like', $needle)
                            ->orWhere('s.last_name', 'like', $needle)
                            ->orWhere('s.roll_number', 'like', $needle);
                    });
                }
            })
            ->orderBy('s.first_name')
            ->orderBy('s.last_name')
            ->get([
                's.id as student_id',
                'c.id as class_id',
                'c.name as class_name',
                'c.section as class_section',
                'cs.academic_year',
            ]);

        if ($assignments->isEmpty()) {
            return collect();
        }

        $classLabels = $assignments
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $rows
                ->map(fn ($row) => trim($row->class_name . ($row->class_section ? '-' . $row->class_section : '')))
                ->unique()
                ->values());

        return Student::query()
            ->with(['parents.user'])
            ->whereIn('id', $classLabels->keys())
            ->get()
            ->each(function (Student $student) use ($classLabels, $employee): void {
                $student->setAttribute('teacher_class_labels', $classLabels->get($student->id, collect())->all());
                $student->setRelation('parents', $student->parents
                    ->filter(fn ($parent) => $parent->branch_id === $employee->branch_id)
                    ->values());
            });
    }

    public function teacherInboxConversations(User $teacher, string $search = ''): Collection
    {
        $students = $this->teacherStudents($teacher, $search);

        if ($students->isEmpty()) {
            return collect();
        }

        $parentUsers = $students
            ->flatMap(fn (Student $student) => $student->parents
                ->filter(fn ($parent) => $parent->user instanceof User)
                ->map(fn ($parent) => [
                    'student' => $student,
                    'parent' => $parent,
                    'user' => $parent->user,
                ]))
            ->unique(fn ($entry) => $entry['student']->id . ':' . $entry['user']->id)
            ->values();

        if ($parentUsers->isEmpty()) {
            return collect();
        }

        $parentUserIds = $parentUsers->pluck('user')->map(fn (User $user) => (string) $user->id)->unique();
        $conversations = Wirechat::conversationModelClass()::query()
            ->where('type', ConversationType::PRIVATE)
            ->whereHas('participants', fn ($query) => $query
                ->where('participantable_id', $teacher->getKey())
                ->where('participantable_type', $teacher->getMorphClass()))
            ->whereHas('participants', fn ($query) => $query
                ->whereIn('participantable_id', $parentUserIds)
                ->where('participantable_type', $teacher->getMorphClass()))
            ->with(['lastMessage', 'participants'])
            ->get();

        $conversationByParentId = $conversations->mapWithKeys(function ($conversation) use ($teacher): array {
            $peer = $conversation->participants->first(fn ($participant) =>
                $participant->participantable_type === $teacher->getMorphClass()
                && (string) $participant->participantable_id !== (string) $teacher->getKey()
            );

            return $peer ? [(string) $peer->participantable_id => $conversation] : [];
        });

        return $parentUsers
            ->map(function (array $entry) use ($conversationByParentId): ?array {
                $conversation = $conversationByParentId->get((string) $entry['user']->id);

                if (!$conversation) {
                    return null;
                }

                $message = $conversation->lastMessage;

                return [
                    'student' => $entry['student'],
                    'parent' => $entry['parent'],
                    'relationship' => ucfirst($entry['parent']->pivot->relationship_type ?: 'Guardian'),
                    'conversation_id' => (string) $conversation->id,
                    'last_message' => $message?->body ?: ($message ? 'Attachment' : 'No messages yet'),
                    'last_message_at' => $message?->created_at,
                    'sort_at' => $message?->created_at ?? $conversation->updated_at,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $entry) => $entry['sort_at'])
            ->values();
    }

    public function startStudentParentConversation(User $teacher, Student $student, string|int $parentId): ?Conversation
    {
        abort_unless($this->teacherCanMessageStudentParent($teacher, $student, $parentId), 403);

        $parent = $student->parents()
            ->whereKey($parentId)
            ->where('parents.branch_id', $student->branch_id)
            ->firstOrFail();

        abort_unless($parent->user instanceof User, 404);

        return $this->startConversation($teacher, $parent->user);
    }

    public function teacherCanMessageStudentParent(User $teacher, Student $student, string|int $parentId): bool
    {
        if (!$this->studentIsAssignedToTeacher($teacher, $student)) {
            return false;
        }

        $parent = $student->parents()->whereKey($parentId)->where('parents.branch_id', $student->branch_id)->first();

        return $parent?->user instanceof User
            && $this->canStartConversation($teacher, $parent->user);
    }

    public function studentParentConversationSummaries(User $teacher, Student $student): Collection
    {
        if (!$this->studentIsAssignedToTeacher($teacher, $student)) {
            return collect();
        }

        $parentUsers = $student->parents
            ->where('branch_id', $student->branch_id)
            ->pluck('user')
            ->filter(fn ($user) => $user instanceof User)
            ->keyBy(fn (User $user) => (string) $user->getKey());

        if ($parentUsers->isEmpty()) {
            return collect();
        }

        $conversations = Wirechat::conversationModelClass()::query()
            ->where('type', ConversationType::PRIVATE)
            ->whereHas('participants', fn ($query) => $query
                ->where('participantable_id', $teacher->getKey())
                ->where('participantable_type', $teacher->getMorphClass()))
            ->whereHas('participants', fn ($query) => $query
                ->whereIn('participantable_id', $parentUsers->keys())
                ->where('participantable_type', $teacher->getMorphClass()))
            ->with(['lastMessage', 'participants'])
            ->get();

        return $conversations->mapWithKeys(function ($conversation) use ($teacher): array {
            $parentParticipant = $conversation->participants->first(fn ($participant) =>
                $participant->participantable_type === $teacher->getMorphClass()
                && (string) $participant->participantable_id !== (string) $teacher->getKey()
            );

            if (!$parentParticipant) {
                return [];
            }

            $message = $conversation->lastMessage;

            return [(string) $parentParticipant->participantable_id => [
                'body' => $message?->body,
                'created_at' => $message?->created_at,
            ]];
        });
    }

    public function canOpenStudentParentConversation(User $teacher, Student $student, Conversation $conversation): bool
    {
        if (!$this->studentIsAssignedToTeacher($teacher, $student)
            || !$teacher->belongsToConversation($conversation)
            || !$conversation->isPrivate()) {
            return false;
        }

        $peerParticipant = $conversation->participants()
            ->where('participantable_type', $teacher->getMorphClass())
            ->where('participantable_id', '!=', $teacher->getKey())
            ->first();

        if (!$peerParticipant) {
            return false;
        }

        return $student->parents()
            ->where('parents.branch_id', $student->branch_id)
            ->where('parents.user_id', $peerParticipant->participantable_id)
            ->exists();
    }

    private function studentIsAssignedToTeacher(User $teacher, Student $student): bool
    {
        $employee = $this->teacherEmployee($teacher);

        if (!$employee || $student->branch_id !== $employee->branch_id) {
            return false;
        }

        return DB::table('class_teacher as ct')
            ->join('class_student as cs', function ($join) {
                $join->on('cs.class_id', '=', 'ct.class_id')
                    ->on('cs.academic_year', '=', 'ct.academic_year');
            })
            ->join('teachers as t', 't.id', '=', 'ct.teacher_id')
            ->where('t.employee_id', $employee->id)
            ->where('cs.student_id', $student->id)
            ->exists();
    }

    private function teacherEmployee(User $teacher): ?\App\Models\Employee
    {
        $branchIds = $this->roleAssignments($teacher)
            ->where('name', 'Teacher')
            ->pluck('branch_id')
            ->unique();

        return $teacher->employee()
            ->where('type', 'teacher')
            ->whereIn('branch_id', $branchIds)
            ->first();
    }

    public function recipientIds(User $user): Collection
    {
        $assignments = $this->roleAssignments($user);
        $adminBranchIds = $assignments
            ->whereIn('name', self::ADMIN_ROLES)
            ->pluck('branch_id')
            ->unique()
            ->values();

        if ($adminBranchIds->isNotEmpty()) {
            return User::query()
                ->where(function ($query) use ($adminBranchIds) {
                    $query->whereHas('branches', fn ($branches) => $branches->whereIn('branches.id', $adminBranchIds))
                        ->orWhereHas('employee', fn ($employee) => $employee->whereIn('branch_id', $adminBranchIds))
                        ->orWhereHas('parentProfile', fn ($parent) => $parent->whereIn('branch_id', $adminBranchIds));
                })
                ->pluck('id')
                ->map(fn ($id) => (string) $id);
        }

        $parentBranchIds = $assignments->where('name', 'Parent')->pluck('branch_id')->unique();
        $parentIds = DB::table('parents')
            ->where('user_id', $user->getKey())
            ->whereIn('branch_id', $parentBranchIds)
            ->pluck('id');

        if ($parentIds->isNotEmpty()) {
            return DB::table('parent_student as ps')
                ->join('students as s', 's.id', '=', 'ps.student_id')
                ->join('class_student as cs', 'cs.student_id', '=', 's.id')
                ->join('classes as c', function ($join) {
                    $join->on('c.id', '=', 'cs.class_id')
                        ->on('c.branch_id', '=', 's.branch_id');
                })
                ->join('class_teacher as ct', function ($join) {
                    $join->on('ct.class_id', '=', 'cs.class_id')
                        ->on('ct.academic_year', '=', 'cs.academic_year');
                })
                ->join('teachers as t', 't.id', '=', 'ct.teacher_id')
                ->join('employees as e', function ($join) {
                    $join->on('e.id', '=', 't.employee_id')
                        ->on('e.branch_id', '=', 'c.branch_id');
                })
                ->whereIn('ps.parent_id', $parentIds)
                ->whereIn('c.branch_id', $parentBranchIds)
                ->distinct()
                ->pluck('e.user_id')
                ->filter()
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();
        }

        $teacherBranchIds = $assignments->where('name', 'Teacher')->pluck('branch_id')->unique();
        $employee = $user->employee()
            ->whereIn('branch_id', $teacherBranchIds)
            ->where('type', 'teacher')
            ->first();

        if (!$employee) {
            return collect();
        }

        return DB::table('class_teacher as ct')
            ->join('teachers as t', 't.id', '=', 'ct.teacher_id')
            ->join('class_student as cs', function ($join) {
                $join->on('cs.class_id', '=', 'ct.class_id')
                    ->on('cs.academic_year', '=', 'ct.academic_year');
            })
            ->join('students as s', 's.id', '=', 'cs.student_id')
            ->join('classes as c', function ($join) {
                $join->on('c.id', '=', 'ct.class_id')
                    ->on('c.branch_id', '=', 's.branch_id');
            })
            ->join('parent_student as ps', 'ps.student_id', '=', 'cs.student_id')
            ->join('parents as p', function ($join) {
                $join->on('p.id', '=', 'ps.parent_id')
                    ->on('p.branch_id', '=', 'c.branch_id');
            })
            ->where('t.employee_id', $employee->id)
            ->where('c.branch_id', $employee->branch_id)
            ->distinct()
            ->pluck('p.user_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
    }

    private function roleAssignments(User $user): Collection
    {
        return DB::table('model_has_roles as mhr')
            ->join('roles', 'roles.id', '=', 'mhr.role_id')
            ->where('mhr.model_id', $user->getKey())
            ->where('mhr.model_type', $user->getMorphClass())
            ->whereNotNull('mhr.branch_id')
            ->get(['roles.name', 'mhr.branch_id']);
    }
}
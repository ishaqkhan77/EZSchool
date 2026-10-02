<?php

use App\Models\Branch;
use App\Models\Classes;
use App\Models\Employee;
use App\Models\ParentModel;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Filament\Resources\Roles\RoleResource;
use App\Services\TeacherRolePermissions;
use App\Livewire\Wirechat\Chat as AppWirechatChat;
use App\Livewire\Wirechat\Chats as AppWirechatChats;
use App\Providers\Wirechat\ChatsPanelProvider;
use App\Services\ChatAccessService;
use Filament\Panel as FilamentPanel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Wirechat\Wirechat\Panel as WirechatPanel;
use Wirechat\Wirechat\PanelRegistry;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;

function createChatTestSchema(): void
{
    foreach ([
        'wirechat_actions', 'wirechat_participants', 'wirechat_conversations', 'class_teacher', 'employees', 'class_student',
        'classes', 'parent_student', 'students', 'parents', 'model_has_roles', 'roles', 'branch_user',
        'branches', 'schools', 'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('schools', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('slug');
        $table->timestamps();
    });

    Schema::create('branches', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('school_id');
        $table->string('name');
        $table->string('location')->nullable();
        $table->string('phone')->nullable();
        $table->timestamps();
    });

    Schema::create('branch_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->uuid('branch_id');
        $table->string('role')->default('staff');
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->uuid('branch_id')->nullable();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });

    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('role_has_permissions', function (Blueprint $table) {
        $table->unsignedBigInteger('permission_id');
        $table->unsignedBigInteger('role_id');
        $table->primary(['permission_id', 'role_id']);
    });

    Schema::create('model_has_roles', function (Blueprint $table) {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->uuid('branch_id')->nullable();
    });

    Schema::create('parents', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('branch_id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('full_name');
        $table->string('phone');
        $table->string('address')->nullable();
        $table->timestamps();
    });

    Schema::create('students', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('branch_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('roll_number');
        $table->date('date_of_birth')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('parent_student', function (Blueprint $table) {
        $table->id();
        $table->uuid('parent_id');
        $table->uuid('student_id');
        $table->string('relationship_type')->nullable();
        $table->timestamps();
    });

    Schema::create('classes', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('branch_id');
        $table->string('name');
        $table->string('section')->nullable();
        $table->string('room_number')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('class_student', function (Blueprint $table) {
        $table->id();
        $table->uuid('class_id');
        $table->uuid('student_id');
        $table->string('academic_year');
        $table->timestamps();
    });

    Schema::create('employees', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->unsignedBigInteger('user_id');
        $table->uuid('branch_id');
        $table->string('employee_id');
        $table->decimal('salary', 10, 2);
        $table->date('joining_date');
        $table->string('designation');
        $table->string('type');
        $table->timestamps();
    });

    Schema::create('teachers', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('employee_id');
        $table->string('specialization')->nullable();
        $table->timestamps();
    });

    Schema::create('class_teacher', function (Blueprint $table) {
        $table->id();
        $table->uuid('class_id');
        $table->uuid('teacher_id');
        $table->string('academic_year');
        $table->boolean('is_main_teacher')->default(false);
        $table->timestamps();
    });

    Schema::create('wirechat_conversations', function (Blueprint $table) {
        $table->id();
        $table->string('type');
        $table->timestamp('disappearing_started_at')->nullable();
        $table->integer('disappearing_duration')->nullable();
        $table->timestamps();
    });

    Schema::create('wirechat_messages', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('conversation_id');
        $table->unsignedBigInteger('participant_id');
        $table->unsignedBigInteger('reply_id')->nullable();
        $table->text('body')->nullable();
        $table->string('type')->default('text');
        $table->timestamp('kept_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('wirechat_participants', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('conversation_id');
        $table->string('role');
        $table->unsignedBigInteger('participantable_id');
        $table->string('participantable_type');
        $table->timestamp('exited_at')->nullable();
        $table->timestamp('last_active_at')->nullable();
        $table->timestamp('conversation_cleared_at')->nullable();
        $table->timestamp('conversation_deleted_at')->nullable();
        $table->timestamp('conversation_read_at')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('wirechat_actions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('actionable_id');
        $table->string('actionable_type');
        $table->unsignedBigInteger('actor_id');
        $table->string('actor_type');
        $table->string('type');
        $table->string('data')->nullable();
        $table->timestamps();
    });
}

function createChatFixture(): array
{
    createChatTestSchema();

    $school = School::create([
        'name' => 'Chat Test School',
        'slug' => 'chat-test-school',
    ]);

    $branch = Branch::create([
        'school_id' => $school->id,
        'name' => 'North Branch',
        'location' => 'North',
    ]);

    $otherBranch = Branch::create([
        'school_id' => $school->id,
        'name' => 'South Branch',
        'location' => 'South',
    ]);

    $createUser = fn (string $name, string $email) => User::create([
        'name' => $name,
        'email' => $email,
        'password' => 'password',
    ]);

    $parentUser = $createUser('Parent One', 'parent@test.local');
    $parent = ParentModel::create([
        'branch_id' => $branch->id,
        'user_id' => $parentUser->id,
        'full_name' => 'Parent One',
        'phone' => '10000001',
    ]);
    assignChatRole($parentUser, 'Parent', $branch);

    $student = Student::create([
        'branch_id' => $branch->id,
        'first_name' => 'Student',
        'last_name' => 'One',
        'roll_number' => 'S-001',
        'status' => 'active',
    ]);
    $parent->students()->attach($student->id, ['relationship_type' => 'guardian']);

    $class = Classes::create([
        'branch_id' => $branch->id,
        'name' => 'Grade 1',
        'section' => 'A',
    ]);
    $student->classes()->attach($class->id, ['academic_year' => '2026-2027']);

    $unassignedClass = Classes::create([
        'branch_id' => $branch->id,
        'name' => 'Grade 2',
        'section' => 'B',
    ]);
    $unassignedStudent = Student::create([
        'branch_id' => $branch->id,
        'first_name' => 'Unassigned',
        'last_name' => 'Student',
        'roll_number' => 'S-002',
        'status' => 'active',
    ]);
    $unassignedStudent->classes()->attach($unassignedClass->id, ['academic_year' => '2026-2027']);

    $teacherUser = $createUser('Teacher One', 'teacher@test.local');
    $employee = Employee::create([
        'user_id' => $teacherUser->id,
        'branch_id' => $branch->id,
        'employee_id' => 'T-001',
        'salary' => 1000,
        'joining_date' => '2026-01-01',
        'designation' => 'Teacher',
        'type' => 'teacher',
    ]);
    $employee->teacher->classes()->attach($class->id, [
        'academic_year' => '2026-2027',
        'is_main_teacher' => true,
    ]);
    assignChatRole($teacherUser, 'Teacher', $branch);

    $unrelatedTeacher = $createUser('Other Teacher', 'other-teacher@test.local');
    $unrelatedEmployee = Employee::create([
        'user_id' => $unrelatedTeacher->id,
        'branch_id' => $otherBranch->id,
        'employee_id' => 'T-002',
        'salary' => 1000,
        'joining_date' => '2026-01-01',
        'designation' => 'Teacher',
        'type' => 'teacher',
    ]);
    assignChatRole($unrelatedTeacher, 'Teacher', $otherBranch);

    $adminUser = $createUser('Branch Admin', 'admin@test.local');
    $adminUser->branches()->attach($branch->id);
    assignChatRole($adminUser, 'Admin', $branch);

    $otherBranchParentUser = $createUser('Other Parent', 'other-parent@test.local');
    ParentModel::create([
        'branch_id' => $otherBranch->id,
        'user_id' => $otherBranchParentUser->id,
        'full_name' => 'Other Parent',
        'phone' => '10000002',
    ]);
    $otherBranchParentUser->branches()->attach($otherBranch->id);
    assignChatRole($otherBranchParentUser, 'Parent', $otherBranch);

    return compact(
        'branch',
        'otherBranch',
        'parent',
        'parentUser',
        'teacherUser',
        'class',
        'student',
        'unassignedClass',
        'unassignedStudent',
        'unrelatedTeacher',
        'adminUser',
        'otherBranchParentUser'
    );
}

function assignChatRole(User $user, string $name, Branch $branch): void
{
    $roleId = DB::table('roles')
        ->where('name', $name)
        ->where('branch_id', $branch->id)
        ->value('id');

    $user->branches()->syncWithoutDetaching([$branch->id]);

    if ($name === 'Teacher') {
        $role = App\Models\Role::findOrFail($roleId);
        TeacherRolePermissions::sync($role);
    }

    DB::table('model_has_roles')->insert([
        'role_id' => $roleId,
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'branch_id' => $branch->id,
    ]);
}

test('parents and teachers can only start chats through assigned students', function () {
    $fixture = createChatFixture();
    $access = app(ChatAccessService::class);

    expect($access->canAccessChat($fixture['parentUser']))->toBeTrue();
    expect($fixture['teacherUser']->canAccessPanel(FilamentPanel::make()->id('admin')))->toBeTrue()
        ->and($fixture['parentUser']->canAccessPanel(FilamentPanel::make()->id('admin')))->toBeFalse();
    expect($access->recipientIds($fixture['parentUser'])->all())
        ->toContain((string) $fixture['teacherUser']->id);
    expect($access->recipientIds($fixture['teacherUser'])->all())
        ->toContain((string) $fixture['parentUser']->id);
    expect($access->searchRecipients($fixture['parentUser'], 'Teacher One')->modelKeys())
        ->toContain($fixture['teacherUser']->id);

    expect($access->canStartConversation($fixture['parentUser'], $fixture['teacherUser']))->toBeTrue()
        ->and($access->canStartConversation($fixture['parentUser'], $fixture['unrelatedTeacher']))->toBeFalse()
        ->and($access->canStartConversation($fixture['teacherUser'], $fixture['parentUser']))->toBeTrue()
        ->and($access->canStartConversation($fixture['teacherUser'], $fixture['otherBranchParentUser']))->toBeFalse();
});

test('custom admin panel access requires its permission and branch membership', function () {
    $fixture = createChatFixture();
    $user = User::create([
        'name' => 'Office Manager',
        'email' => 'office-manager@test.local',
        'password' => 'password',
    ]);
    $role = App\Models\Role::create([
        'name' => 'Office Manager',
        'guard_name' => 'web',
        'branch_id' => $fixture['branch']->id,
    ]);
    $permission = Spatie\Permission\Models\Permission::create([
        'name' => 'Access:AdminPanel',
        'guard_name' => 'web',
    ]);
    $role->syncPermissions([$permission]);

    DB::table('model_has_roles')->insert([
        'role_id' => $role->id,
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'branch_id' => $fixture['branch']->id,
    ]);

    $adminPanel = FilamentPanel::make()->id('admin');

    expect($user->canAccessPanel($adminPanel))->toBeFalse();

    $user->branches()->attach($fixture['branch']->id);

    expect($user->canAccessPanel($adminPanel))->toBeTrue();
});

test('branch admins can only start chats with accounts in their branch', function () {
    $fixture = createChatFixture();
    $access = app(ChatAccessService::class);

    expect($access->canStartConversation($fixture['adminUser'], $fixture['parentUser']))->toBeTrue()
        ->and($access->canStartConversation($fixture['adminUser'], $fixture['otherBranchParentUser']))->toBeFalse();
});

test('teacher roles include teaching workflows without administrative permissions', function () {
    $fixture = createChatFixture();
    $teacherRole = App\Models\Role::where('name', 'Teacher')
        ->where('branch_id', $fixture['branch']->id)
        ->firstOrFail();
    $permissionNames = $teacherRole->permissions()->pluck('name');

    expect($permissionNames)
        ->toContain('ViewAny:Attendance', 'Create:Attendance', 'Update:Attendance')
        ->toContain('ViewAny:Classes', 'ViewAny:Student', 'View:EnterMarks')
        ->not->toContain('Delete:Attendance', 'Delete:Classes', 'Delete:Student', 'Create:Student');

    Illuminate\Support\Facades\Auth::login($fixture['teacherUser']);
    expect(RoleResource::canViewAny())->toBeFalse();
});

test('teacher class and student resources are limited to assigned teaching records', function () {
    $fixture = createChatFixture();
    Illuminate\Support\Facades\Auth::login($fixture['teacherUser']);

    expect(App\Filament\Resources\Classes\ClassResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([(string) $fixture['class']->id])
        ->and(App\Filament\Resources\Students\StudentResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([(string) $fixture['student']->id]);
});

test('teacher messages search students and only expose guardians for that assigned student', function () {
    $fixture = createChatFixture();
    $access = app(ChatAccessService::class);

    expect($access->teacherStudents($fixture['teacherUser'])->pluck('id')->all())
        ->toContain($fixture['student']->id)
        ->not->toContain($fixture['unassignedStudent']->id)
        ->and($access->teacherStudents($fixture['teacherUser'], 'Student One')->pluck('id')->all())
        ->toContain($fixture['student']->id)
        ->and($access->teacherStudents($fixture['teacherUser'], 'Unassigned'))->toBeEmpty()
        ->and($access->teacherCanMessageStudentParent($fixture['teacherUser'], $fixture['student'], $fixture['parent']->id))->toBeTrue()
        ->and($access->teacherCanMessageStudentParent($fixture['teacherUser'], $fixture['unassignedStudent'], $fixture['parent']->id))->toBeFalse();
});

test('teacher student row can resolve an existing parent chat summary', function () {
    $fixture = createChatFixture();
    $fixture['parentUser']->createConversationWith($fixture['teacherUser']);

    $summaries = app(ChatAccessService::class)
        ->studentParentConversationSummaries($fixture['teacherUser'], $fixture['student']);

    expect($summaries)->toHaveKey((string) $fixture['parentUser']->id)
        ->and($summaries->get((string) $fixture['parentUser']->id)['created_at'])->toBeNull();
});

test('teacher inbox rows identify existing conversations by student and guardian', function () {
    $fixture = createChatFixture();
    $conversation = $fixture['parentUser']->createConversationWith($fixture['teacherUser']);
    Wirechat\Wirechat\Models\Message::create([
        'conversation_id' => $conversation->id,
        'participant_id' => $conversation->participant($fixture['parentUser'])->id,
        'body' => 'I will check with him',
        'type' => 'text',
    ]);

    $rows = app(ChatAccessService::class)->teacherInboxConversations($fixture['teacherUser']);
    $row = $rows->first();

    expect($rows)->toHaveCount(1)
        ->and($row['student']->id)->toBe($fixture['student']->id)
        ->and($row['parent']->id)->toBe($fixture['parent']->id)
        ->and($row['relationship'])->toBe('Guardian')
        ->and($row['last_message'])->toBe('I will check with him')
        ->and($row['conversation_id'])->toBe((string) $conversation->id);
});

test('teacher can open a guardian conversation only from a student they teach', function () {
    $fixture = createChatFixture();
    $conversation = $fixture['teacherUser']->createConversationWith($fixture['parentUser']);
    $access = app(ChatAccessService::class);

    expect($access->canOpenStudentParentConversation($fixture['teacherUser'], $fixture['student'], $conversation))->toBeTrue()
        ->and($access->canOpenStudentParentConversation($fixture['teacherUser'], $fixture['unassignedStudent'], $conversation))->toBeFalse();
});

test('chat creation enforces the same recipient policy and groups are disabled', function () {
    $fixture = createChatFixture();

    expect($fixture['parentUser']->canCreateGroups())->toBeFalse();
    $conversation = $fixture['parentUser']->createConversationWith($fixture['teacherUser']);

    expect($conversation)->not->toBeNull()
        ->and($fixture['parentUser']->belongsToConversation($conversation))->toBeTrue()
        ->and($fixture['unrelatedTeacher']->belongsToConversation($conversation))->toBeFalse();

    expect(fn () => $fixture['parentUser']->createConversationWith($fixture['unrelatedTeacher']))
        ->toThrow(HttpException::class);
});

test('chat panel uses unread counts and disables group conversations', function () {
    $provider = new ChatsPanelProvider(app());
    $panel = $provider->panel(WirechatPanel::make());
    $fixture = createChatFixture();
    Illuminate\Support\Facades\Auth::login($fixture['parentUser']);

    expect($panel->hasUnreadIndicator())->toBeTrue()
        ->and($panel->getUnreadIndicatorType())->toBe(UnreadIndicatorType::Count)
        ->and($panel->hasGroups())->toBeFalse()
        ->and($panel->hasChatsSearch())->toBeFalse()
        ->and($panel->hasBroadcasting())->toBeFalse()
        ->and($panel->hasCreateChatAction())->toBeTrue();
});

    test('chat components do not register Echo listeners while realtime is disabled', function () {
        $fixture = createChatFixture();
        Illuminate\Support\Facades\Auth::login($fixture['parentUser']);

        $provider = new ChatsPanelProvider(app());
        app(PanelRegistry::class)->register($provider->panel(WirechatPanel::make()));

        expect(app('livewire.factory')->resolveComponentClass('wirechat.chats'))->toBe(AppWirechatChats::class)
            ->and(app('livewire.factory')->resolveComponentClass('wirechat.chat'))->toBe(AppWirechatChat::class);

        $chats = new AppWirechatChats();
        $chats->panel = 'chats';

        $chat = new AppWirechatChat();
        $chat->panel = 'chats';
        $chat->conversation = (object) ['id' => 123];

        expect(collect($chats->getListeners())->keys()->filter(fn ($key) => str_starts_with($key, 'echo')))->toBeEmpty()
        ->and(collect($chat->getListeners())->keys()->filter(fn ($key) => str_starts_with($key, 'echo')))->toBeEmpty();
    });

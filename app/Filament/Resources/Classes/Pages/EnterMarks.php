<?php

namespace App\Filament\Resources\Classes\Pages;

use App\Filament\Resources\Classes\ClassResource;
use App\Filament\Resources\Classes\Schemas\ClassForm;
use App\Models\Exam;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EnterMarks extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, InteractsWithRecord;

    protected static string $resource = ClassResource::class;
    protected string $view = 'filament.resources.classes.pages.enter-marks';

    // CRITICAL: statePath('data') requires this property to exist
    public ?string $exam_id = null;
    public ?string $subject_id = null;
    public ?array $data = [];

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user && Gate::forUser($user)->allows('View:EnterMarks');
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $user = Auth::user();
        if ($user?->roles->contains('name', 'Teacher')) {
            abort_unless($user->employee?->teacher?->classes()->whereKey($this->record->id)->exists(), 403);
        }

        $this->form->fill();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Student::whereHas('classes', fn($q) => $q->where('classes.id', $this->record->id))
            )
            ->heading('Student Marks List')
            ->description('Sorting and filtering based on selected exam and subject.')
            ->columns([
                TextColumn::make('roll_number')->label('Roll No.'),
                TextColumn::make('full_name')->searchable()->label('Student Name'),

                TextColumn::make('marks_info')
                    ->label('Obtained / Max')
                    ->state(function (Student $record) {
                        if (!$this->exam_id || !$this->subject_id) return 'Select Filters...';

                        $mark = Mark::where([
                            'student_id' => $record->id,
                            'exam_id' => $this->exam_id,
                            'subject_id' => $this->subject_id,
                        ])->first();

                        return $mark ? "{$mark->score} / {$mark->max_score}" : 'Pending';
                    })
                    ->badge()
                    ->color(fn ($state) => str_contains($state, '/') ? 'success' : 'gray')
                    ->sortable(query: function ($query, $direction) {
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('edit_mark')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->hidden(fn () => !$this->subject_id || !$this->exam_id)
                    ->form([
                        TextInput::make('score')->numeric()->required()->label('Obtained Marks'),
                        TextInput::make('total_marks')->numeric()->default(100)->required()->label('Max Marks'),
                    ])
                    ->fillForm(function (Student $record) {
                        $mark = Mark::where([
                            'student_id' => $record->id,
                            'exam_id' => $this->exam_id,
                            'subject_id' => $this->subject_id,
                        ])->first();

                        return [
                            'score' => $mark?->score,
                            'total_marks' => $mark?->max_score ?? 100,
                        ];
                    })
                    ->action(function (array $data, Student $record) {
                        Mark::updateOrCreate(
                            [
                                'student_id' => $record->id,
                                'exam_id' => $this->exam_id,
                                'subject_id' => $this->subject_id,
                            ],
                            [
                                'score' => $data['score'],
                                'max_score' => $data['total_marks'],
                                'class_id' => $this->record->id,
                            ]
                        );
                    })
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return ClassForm::getMarksFormSchema($schema,$this);
    }

    public function loadStudents()
    {
        $subjectId = $this->data['subject_id'] ?? null;
        $examId = $this->data['exam_id'] ?? null;

        if (!$subjectId || !$examId) return;

        // Get students in this class
        $students = Student::whereHas('classes', fn ($query) => $query->where('classes.id', $this->record->id))->get();

        $gridData = $students->map(function ($student) use ($subjectId, $examId) {
            // Look for an existing mark in the DB
            $existingMark = \App\Models\Mark::where([
                'student_id' => $student->id,
                'subject_id' => $subjectId,
                'exam_id'    => $examId,
            ])->first();

            return [
                'student_id'   => (string) $student->id,
                'student_name' => $student->full_name,
                'score'        => $existingMark?->score ?? 0,
                'max_score'    => $existingMark?->max_score ?? 100,
            ];
        })->toArray();

        $this->data['marks_grid'] = $gridData;
    }

    public function save()
    {
        $formData = $this->form->getState();

        foreach ($formData['marks_grid'] as $entry) {
            Mark::updateOrCreate(
                [
                    'student_id' => $entry['student_id'],
                    'subject_id' => $formData['subject_id'],
                    'exam_id' => $formData['exam_id'],
                ],
                [
                    'score' => $entry['score'],
                    'max_score' => $entry['max_score'],
                    'class_id' => $this->record->id,
                ]
            );
        }

        Notification::make()->success()->title('Marks Saved Successfully')->send();
    }
}

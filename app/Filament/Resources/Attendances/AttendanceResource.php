<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\ManageAttendances;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Student;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'Attendance';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Session')
                    ->schema([
                        DatePicker::make('date')
                            ->default(now())
                            ->required()
                            ->native(false)
                            ->maxDate(now())
                            ->live(),

                        Select::make('class_id')
                            ->label('Select Class')
                            ->options(function (Get $get, $record) {
                                $date = $get('date');
                                $branchId = Filament::getTenant()?->getKey();
                                if (!$date || !$branchId) return [];

                                $classes = Classes::where('branch_id', $branchId);
                                $user = Auth::user();

                                if ($user instanceof User && $user->roles->contains('name', 'Teacher')) {
                                    $teacherId = $user->employee?->teacher?->id;
                                    if (!$teacherId) return [];
                                    $classes->whereHas('teachers', fn ($query) => $query->whereKey($teacherId));
                                }

                                // Optimized: Find classes that DON'T have attendance on this date
                                return $classes->whereDoesntHave('attendances', function ($q) use ($date, $record) {
                                    $q->where('date', $date);
                                    if ($record) $q->where('id', '!=', $record->id);
                                    })->pluck('name', 'id');
                            })
                            ->disabled(fn($record) => $record !== null)
                            ->dehydrated()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) {
                                    $set('items', []);
                                    return;
                                }

                                // Fetch only necessary columns for performance
                                $students = Student::where('branch_id', Filament::getTenant()?->getKey())
                                    ->whereHas('classes', fn($q) => $q->where('classes.id', $state))
                                    ->select(['students.id', 'students.first_name', 'students.last_name'])
                                    ->get();

                                $set('items', $students->map(fn($s) => [
                                    'student_id' => $s->id,
                                    'student_name' => "{$s->first_name} {$s->last_name}",
                                    'status' => 'p', // Use lowercase to match ToggleButtons
                                ])->toArray());
                            })
                            ->afterStateHydrated(function (Set $set, $state, $record) {
                                if (!$record) return;
                                $items = $record->items()->with('student')->get();
                                if ($items->isNotEmpty()) {
                                    $set('items', $items->map(fn($item) => [
                                        'student_id' => $item->student_id,
                                        'student_name' => $item->student->first_name . ' ' . $item->student->last_name,
                                        'status' => $item->status,
                                    ])->toArray());
                                    return;

                                }
                                if (!$state) return;
                                $students = Student::where('branch_id', Filament::getTenant()?->getKey())
                                    ->whereHas('classes', fn($q) => $q->where('classes.id', $state))
                                    ->select('students.id', 'students.first_name', 'students.last_name')
                                    ->get();
                                $set('items', $students->map(fn($s) => [
                                    'student_id' => $s->id,
                                    'student_name' => "{$s->first_name} {$s->last_name}",
                                    'status' => 'p',
                                ])->toArray());
                            })
                    ])->columns(2),

                Section::make('Attendance Sheet')

                    ->schema([
                        Repeater::make('items')
                            ->label('Student List')
                            ->schema([
                                Hidden::make('student_id'),
                                Hidden::make('student_name'), // Keeps name in state for placeholders

                                Placeholder::make('student_name_label')
                                    ->hiddenLabel()
                                    ->content(fn(Get $get) => $get('student_name'))
                                    ->extraAttributes(['class' => 'flex items-center pt-2 font-bold'])
                                    ->columnSpan(5),

                                ToggleButtons::make('status')
                                    ->hiddenLabel()
                                    ->options([
                                        'p' => 'Present',
                                        'a' => 'Absent',
                                        'l' => 'Late',
                                    ])
                                    ->colors(['p' => 'success', 'a' => 'danger', 'l' => 'warning'])
                                    ->default('p')
                                    ->inline()
                                    ->required()
                                    ->columnSpan(7),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->itemLabel(null)
                            ->columnSpanFull()
                    ])
                    ->visible(fn (Get $get, $record) => $record !== null || filled($get('class_id'))),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Session Overview')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('class.name')
                                    ->label('Class')
                                    ->weight('bold'),

                                TextEntry::make('class.section')
                                    ->label('Section')
                                    ->placeholder('N/A'),

                                TextEntry::make('date')
                                    ->date()
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('presents_count')
                                    ->label('Presents')
                                    ->color('success'),

                                TextEntry::make('absents_count')
                                    ->label('Absents')
                                    ->color('danger'),

                                TextEntry::make('lates_count')
                                    ->label('Lates')
                                    ->color('warning'),

                                TextEntry::make('recorder.name')
                                    ->label('Recorded By')
                                    ->icon('heroicon-m-user'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Attendance Details')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Student Attendance')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('student.first_name')
                                            ->label('Student')
                                            ->weight('medium'),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn($state) => match ($state) {
                                                'p' => 'success',
                                                'a' => 'danger',
                                                'l' => 'warning',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn($state) => match ($state) {
                                                'p' => 'Present',
                                                'a' => 'Absent',
                                                'l' => 'Late',
                                                default => $state,
                                            }),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->grid(2)
                    ])
                    ->collapsible()
                    ->collapsed(false)->columnSpanFull(),
            ]);

    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount([
                'items as absents_count' => fn($query) => $query->where('status', 'a'),
                'items as lates_count' => fn($query) => $query->where('status', 'l'),
                'items as presents_count' => fn($query) => $query->where('status', 'p'),
            ]);

        $user = Auth::user();

        if (!$user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $roles = $user->roles->pluck('name');

        if ($roles->contains('Super Admin')) {
            return $query;
        }

        if ($roles->contains('Teacher')) {
            return $query->whereHas('class', function ($q) use ($user) {
                $q->whereHas('teachers', function ($q2) use ($user) {
                    $q2->where('teacher_id', $user->employee->teacher->id);
                });
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Attendance')
            ->columns([
                TextColumn::make('class.name')
                    ->searchable(),
                TextColumn::make('class.section')
                    ->label('Section')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('presents_count')
                    ->label('Presents')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('absents_count')
                    ->label('Absents')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('lates_count')
                    ->label('Lates')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('recorder.name')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->using(function (\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model {
                        return DB::transaction(function () use ($record, $data) {
                            // Update Header
                            $record->update(['date' => $data['date']]);

                            if (isset($data['items'])) {
                                $record->items()->delete();

                                $items = collect($data['items'])->map(fn($item) => [
                                    'id' => (string) \Illuminate\Support\Str::uuid(),
                                    'attendance_id' => $record->id,
                                    'student_id' => $item['student_id'],
                                    'status' => $item['status'],
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ])->toArray();

                                \App\Models\AttendanceItem::insert($items);
                            }

                            return $record;
                        });
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAttendances::route('/'),
        ];
    }
}


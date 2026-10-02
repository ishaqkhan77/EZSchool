<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceItem;
use App\Models\Classes;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ManageAttendances extends ManageRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Take Attendance')
                ->modalWidth('4xl')
                ->using(function (array $data): Attendance {
                    $user = Auth::user();
                    $class = Classes::findOrFail($data['class_id']);

                    if ($user instanceof User && $user->roles->contains('name', 'Teacher')) {
                        abort_unless(
                            $user->employee?->teacher?->classes()->whereKey($class->id)->exists()
                                && $class->branch_id === Filament::getTenant()?->id,
                            403
                        );
                    }

                    return DB::transaction(function () use ($data) {
                        $attendance = \App\Models\Attendance::create([
                            'date' => $data['date'],
                            'class_id' => $data['class_id'],
                            'recorded_by' => Auth::id(),
                            'branch_id' => Filament::getTenant()->id,
                        ]);

                        $items = collect($data['items'] ?? [])->map(fn ($item) => [
                            'id' => Str::uuid(),
                            'attendance_id' => $attendance->id,
                            'student_id' => $item['student_id'],
                            'status' => $item['status'] ?? 'P',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])->toArray();

                        if (!empty($items)) {
                            AttendanceItem::insert($items);
                        }
                        return $attendance;
                    });
                }),
        ];
    }
}

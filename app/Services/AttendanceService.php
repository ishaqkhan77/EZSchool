<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceItem;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceService
{
    public static function bulkSave(string $classId, string $date, array $studentData, string $recordedBy)
    {
        return DB::transaction(function () use ($classId, $date, $studentData, $recordedBy) {
            $class = Classes::query()->findOrFail($classId);

            // 1. Create or Get the Header
            $attendance = Attendance::updateOrCreate(
                ['class_id' => $classId, 'date' => $date],
                [
                    'branch_id' => $class->branch_id,
                    'recorded_by' => $recordedBy
                ]
            );

            // 2. Prepare Bulk Insert Data (The Optimization)
            $items = collect($studentData)->map(function ($status, $studentId) use ($attendance) {
                return [
                    'id' => Str::uuid(),
                    'attendance_id' => $attendance->id,
                    'student_id' => $studentId,
                    'status' => $status, // 'p', 'a', etc.
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // 3. Delete existing items for this day (if re-taking attendance) and Bulk Insert
            AttendanceItem::where('attendance_id', $attendance->id)->delete();
            AttendanceItem::insert($items);

            return $attendance;
        });
    }
}

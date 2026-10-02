<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->select(['user_id', 'branch_id'])
            ->distinct()
            ->orderBy('user_id')
            ->orderBy('branch_id')
            ->chunk(500, function ($employees): void {
                foreach ($employees as $employee) {
                    $membershipExists = DB::table('branch_user')
                        ->where('user_id', $employee->user_id)
                        ->where('branch_id', $employee->branch_id)
                        ->exists();

                    if (! $membershipExists) {
                        DB::table('branch_user')->insert([
                            'user_id' => $employee->user_id,
                            'branch_id' => $employee->branch_id,
                            'role' => 'staff',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
    }
};
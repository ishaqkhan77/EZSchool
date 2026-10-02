<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->constrained()->onDelete('cascade');

            $indexes = Schema::getIndexes('roles');
            $indexNames = array_column($indexes, 'name');

            if (in_array('roles_name_guard_name_unique', $indexNames)) {
                $table->dropUnique('roles_name_guard_name_unique');
            }

            $table->unique(['name', 'guard_name', 'branch_id'], 'roles_branch_unique');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->renameColumn('team_id', 'branch_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->renameColumn('team_id', 'branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            //
        });
    }
};

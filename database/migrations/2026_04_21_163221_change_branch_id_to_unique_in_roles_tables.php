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
        $indexNames = array_column(Schema::getIndexes('roles'), 'name');

        if (in_array('roles_team_id_name_guard_name_unique', $indexNames, true)) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_team_id_name_guard_name_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles_tables', function (Blueprint $table) {
            //
        });
    }
};

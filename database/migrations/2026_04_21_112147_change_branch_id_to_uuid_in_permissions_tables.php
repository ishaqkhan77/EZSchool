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
        $this->replaceBranchIdColumn('model_has_roles', 'role_id', 'model_has_roles_role_model_type_primary');
        $this->replaceBranchIdColumn('model_has_permissions', 'permission_id', 'model_has_permissions_permission_model_type_primary');
    }

    private function replaceBranchIdColumn(string $tableName, string $relatedId, string $primaryIndex): void
    {
        if (! Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        $indexes = array_values(array_filter(
            Schema::getIndexes($tableName),
            fn (array $index): bool => in_array('branch_id', $index['columns'], true),
        ));

        foreach ($indexes as $index) {
            Schema::table($tableName, function (Blueprint $table) use ($index) {
                if ($index['primary']) {
                    $table->dropPrimary($index['name']);
                } elseif ($index['unique']) {
                    $table->dropUnique($index['name']);
                } else {
                    $table->dropIndex($index['name']);
                }
            });
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });

        Schema::table($tableName, function (Blueprint $table) use ($relatedId) {
            $table->uuid('branch_id')->nullable()->after($relatedId);
        });

        $primaryIndexColumns = $tableName === 'model_has_roles'
            ? ['branch_id', 'role_id', 'model_id', 'model_type']
            : ['branch_id', 'permission_id', 'model_id', 'model_type'];

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $primaryIndexColumns, $primaryIndex) {
            $table->index('branch_id', $tableName.'_team_foreign_key_index');
            $table->primary($primaryIndexColumns, $primaryIndex);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions_tables', function (Blueprint $table) {
            //
        });
    }
};

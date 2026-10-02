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
        foreach (Schema::getForeignKeys('roles') as $foreignKey) {
            if (in_array('branch_id', $foreignKey['columns'], true)) {
                Schema::table('roles', function (Blueprint $table) use ($foreignKey) {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }

        if (Schema::hasColumn('roles', 'branch_id')) {
            foreach (Schema::getIndexes('roles') as $index) {
                if (! in_array('branch_id', $index['columns'], true)) {
                    continue;
                }

                Schema::table('roles', function (Blueprint $table) use ($index) {
                    if ($index['primary']) {
                        $table->dropPrimary($index['name']);
                    } elseif ($index['unique']) {
                        $table->dropUnique($index['name']);
                    } else {
                        $table->dropIndex($index['name']);
                    }
                });
            }

            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }

        $indexes = Schema::getIndexes('roles');
        foreach ($indexes as $index) {
            if ($index['unique'] && $index['columns'] === ['name', 'guard_name']) {
                Schema::table('roles', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->unique(['name', 'guard_name', 'branch_id'], 'roles_branch_unique');
        });

        foreach (['model_has_roles', 'model_has_permissions'] as $tableName) {
            if (Schema::hasColumn($tableName, 'team_id') && ! Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->renameColumn('team_id', 'branch_id');
                });
            }
        }
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

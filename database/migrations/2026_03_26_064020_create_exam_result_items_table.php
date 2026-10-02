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
        Schema::create('exam_result_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_result_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained();
            $table->decimal('marks', 5, 2);
            $table->decimal('total_marks', 5, 2)->default(100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_result_items');
    }
};

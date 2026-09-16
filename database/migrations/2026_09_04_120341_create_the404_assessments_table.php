<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One Boss Challenge (assessment) per course. The unique course_id
     * constraint enforces the §3.2 "one assessment per course" rule, and the
     * restrict-on-delete FK keeps a course deletion from silently destroying
     * the scored attempt history that references it.
     */
    public function up(): void
    {
        Schema::create('the404_assessments', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('course_id');
            $table->string('title', 128);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->mediumText('grading_rule')->nullable();
            $table->unsignedInteger('passing_score')->default(0);
            $table->enum('status', ['active', 'locked', 'draft'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique('course_id');
            $table->index('status');
            $table->foreign('course_id')->references('id')->on('the404_courses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_assessments');
    }
};

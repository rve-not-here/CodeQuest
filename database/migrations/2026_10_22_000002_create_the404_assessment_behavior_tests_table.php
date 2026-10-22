<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hidden behavioural tests for a Boss Challenge. Same shape and trust
     * model as the404_mission_behavior_tests: system-owned grading
     * configuration, read server-side only, never serialized to students.
     *
     * Additive only. An assessment with no rows keeps its historical
     * pattern-only evaluation; once tests exist, evaluation requires every
     * test to pass.
     */
    public function up(): void
    {
        Schema::create('the404_assessment_behavior_tests', function (Blueprint $table): void {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('assessment_id');
            $table->string('name', 120);
            $table->enum('test_type', ['function', 'console']);
            $table->text('configuration');
            $table->unsignedInteger('order_num')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['assessment_id', 'order_num']);
            $table->foreign('assessment_id')->references('id')->on('the404_assessments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_assessment_behavior_tests');
    }
};

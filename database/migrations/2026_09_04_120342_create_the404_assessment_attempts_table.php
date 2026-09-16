<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per student attempt at a Boss Challenge. Attempts are
     * audit-worthy scored records (score, pass/fail, timestamps), so both FKs
     * restrict on delete: destroying a user or an assessment must not silently
     * carry away graded attempt history.
     */
    public function up(): void
    {
        Schema::create('the404_assessment_attempts', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('assessment_id');
            $table->unsignedInteger('user_id');
            $table->mediumText('code')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->enum('status', ['available', 'started', 'submitted', 'evaluated', 'passed', 'failed'])
                ->default('available');
            $table->timestamp('passed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('user_id');
            $table->index('assessment_id');
            $table->index(['assessment_id', 'user_id']);
            $table->foreign('assessment_id')->references('id')->on('the404_assessments')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('the404_users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_assessment_attempts');
    }
};

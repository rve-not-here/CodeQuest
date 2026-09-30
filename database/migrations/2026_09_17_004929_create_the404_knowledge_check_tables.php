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
        Schema::create('the404_knowledge_checks', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('mission_id');
            $table->unsignedInteger('order_num')->default(1);
            $table->string('title', 128);
            $table->text('instructions')->nullable();
            $table->boolean('is_required')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['mission_id', 'order_num']);
            $table->index(['mission_id', 'status', 'order_num']);
            $table->foreign('mission_id')->references('id')->on('the404_missions')->restrictOnDelete();
        });

        Schema::create('the404_knowledge_check_questions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('knowledge_check_id');
            $table->unsignedInteger('order_num')->default(1);
            $table->enum('type', ['multiple_choice', 'code_reading', 'concept_identification']);
            $table->text('prompt');
            $table->mediumText('code_snippet')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['knowledge_check_id', 'order_num'], 'kcq_check_order_unique');
            $table->foreign('knowledge_check_id')->references('id')->on('the404_knowledge_checks')->restrictOnDelete();
        });

        Schema::create('the404_knowledge_check_options', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('knowledge_check_question_id');
            $table->unsignedInteger('order_num')->default(1);
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['knowledge_check_question_id', 'order_num'], 'kco_check_question_order_unique');
            $table->index(['knowledge_check_question_id', 'is_correct'], 'kco_check_question_correct_index');
            $table->foreign('knowledge_check_question_id', 'kco_check_question_foreign')->references('id')->on('the404_knowledge_check_questions')->restrictOnDelete();
        });

        Schema::create('the404_knowledge_check_attempts', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('knowledge_check_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('attempt_number');
            $table->enum('status', ['started', 'submitted'])->default('started');
            $table->unsignedInteger('score')->nullable();
            $table->unsignedInteger('total_questions')->nullable();
            $table->unsignedInteger('percentage')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['knowledge_check_id', 'user_id', 'attempt_number'], 'kca_check_user_attempt_unique');
            $table->index(['user_id', 'submitted_at']);
            $table->index(['knowledge_check_id', 'status']);
            $table->foreign('knowledge_check_id')->references('id')->on('the404_knowledge_checks')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('the404_users')->restrictOnDelete();
        });

        Schema::create('the404_knowledge_check_responses', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('knowledge_check_attempt_id');
            $table->unsignedInteger('knowledge_check_question_id');
            $table->unsignedInteger('selected_option_id');
            $table->unsignedInteger('correct_option_id');
            $table->boolean('is_correct');
            $table->text('prompt_snapshot');
            $table->text('selected_option_snapshot');
            $table->text('correct_option_snapshot');
            $table->text('explanation_snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['knowledge_check_attempt_id', 'knowledge_check_question_id'], 'kcr_attempt_question_unique');
            $table->index(['knowledge_check_question_id', 'is_correct'], 'kcr_question_correct_index');
            $table->foreign('knowledge_check_attempt_id', 'kcr_attempt_foreign')->references('id')->on('the404_knowledge_check_attempts')->restrictOnDelete();
            $table->foreign('knowledge_check_question_id', 'kcr_question_foreign')->references('id')->on('the404_knowledge_check_questions')->restrictOnDelete();
            $table->foreign('selected_option_id')->references('id')->on('the404_knowledge_check_options')->restrictOnDelete();
            $table->foreign('correct_option_id')->references('id')->on('the404_knowledge_check_options')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_knowledge_check_responses');
        Schema::dropIfExists('the404_knowledge_check_attempts');
        Schema::dropIfExists('the404_knowledge_check_options');
        Schema::dropIfExists('the404_knowledge_check_questions');
        Schema::dropIfExists('the404_knowledge_checks');
    }
};

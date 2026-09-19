<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skill-mapping schema for US-905/US-906 (skill-level competency and
     * weak-skill identification).
     *
     * Additive only: one canonical skills catalog, three explicit pivot
     * tables, and nullable skill-key snapshot columns on the evidence
     * tables. No existing column is altered or dropped.
     *
     * Skill keys are globally unique stable machine names (e.g.
     * html.headings). Historical evidence snapshots store keys, never
     * labels, so a label edit can never reinterpret a recorded row.
     * Pre-existing evidence rows keep NULL snapshots (unknown revision),
     * matching the grading-snapshot precedent: never claim mappings that
     * cannot be reconstructed.
     *
     * All constraint names are explicit and short: Laravel's default
     * {table}_{column}_foreign names would exceed MariaDB's 64-character
     * identifier limit on the long the404_* table names.
     */
    public function up(): void
    {
        Schema::create('the404_skills', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('key', 64)->unique();
            $table->string('label', 128);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('the404_mission_skill', function (Blueprint $table) {
            $table->unsignedInteger('mission_id');
            $table->unsignedInteger('skill_id');

            $table->primary(['mission_id', 'skill_id'], 'msk_primary');
            $table->foreign('mission_id', 'msk_mission_foreign')->references('id')->on('the404_missions')->restrictOnDelete();
            $table->foreign('skill_id', 'msk_skill_foreign')->references('id')->on('the404_skills')->restrictOnDelete();
        });

        Schema::create('the404_knowledge_check_question_skill', function (Blueprint $table) {
            $table->unsignedInteger('question_id');
            $table->unsignedInteger('skill_id');

            $table->primary(['question_id', 'skill_id'], 'kqs_primary');
            $table->foreign('question_id', 'kqs_question_foreign')->references('id')->on('the404_knowledge_check_questions')->restrictOnDelete();
            $table->foreign('skill_id', 'kqs_skill_foreign')->references('id')->on('the404_skills')->restrictOnDelete();
        });

        Schema::create('the404_assessment_skill', function (Blueprint $table) {
            $table->unsignedInteger('assessment_id');
            $table->unsignedInteger('skill_id');

            $table->primary(['assessment_id', 'skill_id'], 'ask_primary');
            $table->foreign('assessment_id', 'ask_assessment_foreign')->references('id')->on('the404_assessments')->restrictOnDelete();
            $table->foreign('skill_id', 'ask_skill_foreign')->references('id')->on('the404_skills')->restrictOnDelete();
        });

        Schema::table('the404_progress', function (Blueprint $table) {
            $table->text('skill_keys')->nullable()->after('mission_version');
        });

        Schema::table('the404_knowledge_check_responses', function (Blueprint $table) {
            $table->text('skill_keys')->nullable()->after('explanation_snapshot');
        });

        Schema::table('the404_assessment_attempts', function (Blueprint $table) {
            $table->text('skill_keys')->nullable()->after('passing_score_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('the404_assessment_attempts', function (Blueprint $table) {
            $table->dropColumn('skill_keys');
        });

        Schema::table('the404_knowledge_check_responses', function (Blueprint $table) {
            $table->dropColumn('skill_keys');
        });

        Schema::table('the404_progress', function (Blueprint $table) {
            $table->dropColumn('skill_keys');
        });

        Schema::dropIfExists('the404_assessment_skill');
        Schema::dropIfExists('the404_knowledge_check_question_skill');
        Schema::dropIfExists('the404_mission_skill');
        Schema::dropIfExists('the404_skills');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidence for the Boss Challenge verdict: how many hidden behavioural
     * checks ran, how many passed, and the per-rule/per-test feedback that
     * was computed during evaluation. Before this migration that feedback
     * was computed and discarded, so a student could not tell why a rule
     * failed.
     *
     * Additive and nullable. Legacy pattern-only attempts keep NULL evidence
     * rather than claiming a behavioural result that never ran.
     */
    public function up(): void
    {
        Schema::table('the404_assessment_attempts', function (Blueprint $table): void {
            $table->unsignedInteger('tests_total')->nullable()->after('score');
            $table->unsignedInteger('tests_passed')->nullable()->after('tests_total');
            $table->text('failure_feedback')->nullable()->after('tests_passed');
        });
    }

    public function down(): void
    {
        Schema::table('the404_assessment_attempts', function (Blueprint $table): void {
            $table->dropColumn(['tests_total', 'tests_passed', 'failure_feedback']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curriculum versioning and historical integrity.
     *
     * Additive only: version counters on the five curriculum tables,
     * version references on the history tables, and evaluation snapshots on
     * assessment attempts. Every version column backfills to 1 so rows that
     * predate versioning read as revision 1. No column is dropped or altered,
     * no foreign key is added or changed, no index is added (plain columns
     * only, so no MariaDB 64-character identifier risk).
     *
     * Runs only under RefreshDatabase against the test schema, mirroring the
     * production the404_* columns. Never run against prod system404 without
     * explicit approval.
     */
    public function up(): void
    {
        Schema::table('the404_courses', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('order_num');
        });

        Schema::table('the404_sections', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('description');
        });

        Schema::table('the404_missions', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('points');
        });

        Schema::table('the404_assessments', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('passing_score');
        });

        Schema::table('the404_knowledge_checks', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('status');
        });

        Schema::table('the404_progress', function (Blueprint $table) {
            $table->unsignedInteger('mission_version')->default(1)->after('mission_id');
        });

        Schema::table('the404_assessment_attempts', function (Blueprint $table) {
            $table->unsignedInteger('assessment_version')->default(1)->after('assessment_id');
            $table->mediumText('grading_rule_snapshot')->nullable()->after('code');
            $table->unsignedInteger('passing_score_snapshot')->nullable()->after('grading_rule_snapshot');
        });

        Schema::table('the404_knowledge_check_attempts', function (Blueprint $table) {
            $table->unsignedInteger('knowledge_check_version')->default(1)->after('knowledge_check_id');
        });

        Schema::table('the404_xp_transactions', function (Blueprint $table) {
            $table->unsignedInteger('mission_version')->nullable()->after('mission_id');
            $table->unsignedInteger('assessment_version')->nullable()->after('assessment_id');
        });

        // Explicit backfill: every row that predates versioning is revision 1.
        // Column defaults already cover this on both SQLite and MariaDB; the
        // updates below make the intent verifiable rather than implicit.
        // History rows that predate stamping but carry a source reference
        // belonged to the only curriculum revision that ever existed, so
        // they backfill to 1 — the approved migration contract for this
        // story. (The master spec never asks for unknown historical
        // versions to stay NULL; it asks that attempts retain the
        // rule/version they were evaluated under (§5, §12), which for
        // pre-versioning rows is uniformly revision 1.)
        DB::table('the404_courses')->whereNull('version')->orWhere('version', 0)->update(['version' => 1]);
        DB::table('the404_sections')->whereNull('version')->orWhere('version', 0)->update(['version' => 1]);
        DB::table('the404_missions')->whereNull('version')->orWhere('version', 0)->update(['version' => 1]);
        DB::table('the404_assessments')->whereNull('version')->orWhere('version', 0)->update(['version' => 1]);
        DB::table('the404_knowledge_checks')->whereNull('version')->orWhere('version', 0)->update(['version' => 1]);
        DB::table('the404_progress')->whereNull('mission_version')->orWhere('mission_version', 0)->update(['mission_version' => 1]);
        DB::table('the404_assessment_attempts')->whereNull('assessment_version')->orWhere('assessment_version', 0)->update(['assessment_version' => 1]);
        DB::table('the404_knowledge_check_attempts')->whereNull('knowledge_check_version')->orWhere('knowledge_check_version', 0)->update(['knowledge_check_version' => 1]);
        DB::table('the404_xp_transactions')->whereNotNull('mission_id')->whereNull('mission_version')->update(['mission_version' => 1]);
        DB::table('the404_xp_transactions')->whereNotNull('assessment_id')->whereNull('assessment_version')->update(['assessment_version' => 1]);

        // Assessment evaluation snapshots stay NULL for pre-existing attempts:
        // the rule and threshold that produced an old verdict cannot be
        // reconstructed, and claiming the current row's values would be
        // dishonest. New evaluations always write both snapshots.
    }

    public function down(): void
    {
        Schema::table('the404_xp_transactions', function (Blueprint $table) {
            $table->dropColumn(['mission_version', 'assessment_version']);
        });

        Schema::table('the404_knowledge_check_attempts', function (Blueprint $table) {
            $table->dropColumn('knowledge_check_version');
        });

        Schema::table('the404_assessment_attempts', function (Blueprint $table) {
            $table->dropColumn(['assessment_version', 'grading_rule_snapshot', 'passing_score_snapshot']);
        });

        Schema::table('the404_progress', function (Blueprint $table) {
            $table->dropColumn('mission_version');
        });

        Schema::table('the404_knowledge_checks', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('the404_assessments', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('the404_missions', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('the404_sections', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('the404_courses', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};

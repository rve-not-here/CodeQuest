<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A zero passing threshold made the Boss Challenge pass on any score:
     * the verdict is `score >= passing_score`, and the score is a percentage,
     * so a submission failing every rule scored 0 and cleared a 0 threshold.
     *
     * The additive floor is revision 1: `passing_score` may not be zero for
     * new rows. Existing rows are deliberately NOT backfilled — rewriting an
     * academic threshold is a content decision, so evaluation fails closed on
     * any threshold outside 1..100 instead (AssessmentService::evaluateAttempt)
     * and the admin write path rejects 0 (AdminAssessmentUpdateRequest,
     * AdminAssessmentService). Seeded content already uses 70.
     *
     * Rollback restores the historical default only; it cannot restore a
     * threshold that was never written.
     */
    public function up(): void
    {
        Schema::table('the404_assessments', function (Blueprint $table) {
            $table->unsignedInteger('passing_score')->default(70)->change();
        });
    }

    public function down(): void
    {
        Schema::table('the404_assessments', function (Blueprint $table) {
            $table->unsignedInteger('passing_score')->default(0)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the XP ledger so a transaction can be attributed to an
     * assessment instead of a mission. mission_id becomes nullable (keeping
     * every pre-existing Phase 3 mission transaction valid) and a nullable
     * assessment_id FK is added. Exactly one of the two is populated; that
     * invariant is enforced in the service layer, not by a third schema
     * column (§25).
     */
    public function up(): void
    {
        Schema::table('the404_xp_transactions', function (Blueprint $table) {
            $table->unsignedInteger('mission_id')->nullable()->change();
            $table->unsignedInteger('assessment_id')->nullable()->after('mission_id');
            $table->foreign('assessment_id')->references('id')->on('the404_assessments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('the404_xp_transactions', function (Blueprint $table) {
            $table->dropForeign(['assessment_id']);
            $table->dropColumn('assessment_id');
            $table->unsignedInteger('mission_id')->nullable(false)->change();
        });
    }
};

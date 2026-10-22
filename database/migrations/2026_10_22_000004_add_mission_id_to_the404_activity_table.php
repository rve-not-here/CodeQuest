<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attribute an Activity row to the mission it belongs to. Before this
     * column, a grading_unavailable submission (an infrastructure failure,
     * recorded without XP or completion) could not be counted as a challenge
     * attempt by analytics — the submission silently disappeared from
     * attempt-based metrics.
     *
     * Additive and nullable: historical rows stay unattributed rather than
     * being guessed from their message text. No foreign key is added because
     * the404_activity is an append-only log and mission deletion must not be
     * blocked by historical log rows.
     */
    public function up(): void
    {
        Schema::table('the404_activity', function (Blueprint $table): void {
            $table->unsignedInteger('mission_id')->nullable()->after('type');
            $table->index(['mission_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('the404_activity', function (Blueprint $table): void {
            $table->dropIndex(['mission_id', 'type']);
            $table->dropColumn('mission_id');
        });
    }
};

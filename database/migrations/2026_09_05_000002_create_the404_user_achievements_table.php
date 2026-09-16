<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per earned achievement. An award is a historical record, like
     * an XP transaction or a scored attempt: both FKs restrict on delete so
     * destroying a user or a catalog achievement must not silently carry
     * away earned history. The unique (user_id, achievement_id) pair makes
     * idempotent awarding structural, and the composite serves user-scoped
     * lookups, so no secondary index is needed.
     */
    public function up(): void
    {
        Schema::create('the404_user_achievements', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('achievement_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'achievement_id']);
            $table->foreign('user_id')->references('id')->on('the404_users')->restrictOnDelete();
            $table->foreign('achievement_id')->references('id')->on('the404_achievements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_user_achievements');
    }
};

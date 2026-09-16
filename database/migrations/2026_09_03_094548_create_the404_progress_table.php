<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the production the404_progress table. Note: no updated_at column,
     * matching the production schema.
     */
    public function up(): void
    {
        Schema::create('the404_progress', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mission_id');
            $table->unsignedInteger('pts_earned')->default(0);
            $table->timestamp('completed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'mission_id']);
            $table->index('user_id');
            $table->index('mission_id');
            $table->foreign('user_id')->references('id')->on('the404_users')->cascadeOnDelete();
            $table->foreign('mission_id')->references('id')->on('the404_missions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_progress');
    }
};

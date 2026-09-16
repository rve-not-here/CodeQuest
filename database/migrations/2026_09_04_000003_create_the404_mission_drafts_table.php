<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('the404_mission_drafts', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mission_id');
            $table->mediumText('code');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['user_id', 'mission_id']);
            $table->foreign('user_id')->references('id')->on('the404_users')->cascadeOnDelete();
            $table->foreign('mission_id')->references('id')->on('the404_missions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_mission_drafts');
    }
};

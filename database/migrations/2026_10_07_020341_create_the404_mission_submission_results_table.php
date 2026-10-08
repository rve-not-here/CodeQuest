<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('the404_mission_submission_results', function (Blueprint $table): void {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mission_id');
            $table->unsignedInteger('mission_version');
            $table->string('submission_id', 64);
            $table->char('source_hash', 64);
            $table->text('failures');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'submission_id'], 'mission_submission_operation_unique');
            $table->foreign('user_id', 'mission_submission_user_fk')->references('id')->on('the404_users')->restrictOnDelete();
            $table->foreign('mission_id', 'mission_submission_mission_fk')->references('id')->on('the404_missions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_mission_submission_results');
    }
};

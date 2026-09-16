<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('the404_xp_transactions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('mission_id');
            $table->integer('amount');
            $table->string('type', 40);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->foreign('user_id')->references('id')->on('the404_users')->restrictOnDelete();
            $table->foreign('mission_id')->references('id')->on('the404_missions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_xp_transactions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the production the404_activity table, used to build the test database.
     * Note: no updated_at column, matching the production schema.
     */
    public function up(): void
    {
        Schema::create('the404_activity', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->string('type', 40);
            $table->text('message');
            $table->unsignedInteger('pts')->default(0)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('type');
            $table->index(['user_id', 'created_at']);
            $table->foreign('user_id')->references('id')->on('the404_users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_activity');
    }
};

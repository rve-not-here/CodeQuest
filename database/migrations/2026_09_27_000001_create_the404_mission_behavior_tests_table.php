<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hidden behavioral tests for coding missions. Rows are system-owned
     * grading configuration: never serialized to students, only read
     * server-side during submission grading.
     */
    public function up(): void
    {
        Schema::create('the404_mission_behavior_tests', function (Blueprint $table): void {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('mission_id');
            $table->string('name', 120);
            $table->enum('test_type', ['function', 'console']);
            $table->text('configuration');
            $table->unsignedInteger('order_num')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['mission_id', 'order_num']);
            $table->foreign('mission_id')->references('id')->on('the404_missions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_mission_behavior_tests');
    }
};

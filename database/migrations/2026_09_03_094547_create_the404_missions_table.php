<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the production the404_missions table, used to build the test database.
     */
    public function up(): void
    {
        Schema::create('the404_missions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('order_num')->default(1);
            $table->string('title', 128)->default('');
            $table->enum('difficulty', ['EASY', 'MEDIUM', 'HARD'])->default('EASY');
            $table->text('description')->nullable();
            $table->mediumText('broken_code')->nullable();
            $table->mediumText('solution_code')->nullable();
            $table->mediumText('target_html')->nullable();
            $table->mediumText('validate_rule')->nullable();
            $table->mediumText('hints')->nullable();
            $table->unsignedInteger('points')->default(50);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('course_id');
            $table->index(['course_id', 'order_num']);
            $table->foreign('course_id')->references('id')->on('the404_courses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_missions');
    }
};

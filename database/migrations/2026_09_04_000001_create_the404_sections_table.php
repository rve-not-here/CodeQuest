<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('the404_sections', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('order_num')->default(1);
            $table->string('title', 128);
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index(['course_id', 'order_num']);
            $table->foreign('course_id')->references('id')->on('the404_courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_sections');
    }
};

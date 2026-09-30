<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('the404_classrooms', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('name', 128);
            $table->string('code', 32)->nullable()->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('the404_classroom_teachers', function (Blueprint $table) {
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('teacher_id');
            $table->timestamps();

            $table->unique(['classroom_id', 'teacher_id']);
            $table->index(['teacher_id', 'classroom_id']);
            $table->foreign('classroom_id')->references('id')->on('the404_classrooms')->restrictOnDelete();
            $table->foreign('teacher_id')->references('id')->on('the404_users')->restrictOnDelete();
        });

        Schema::create('the404_classroom_students', function (Blueprint $table) {
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('student_id');
            $table->timestamps();

            $table->unique(['classroom_id', 'student_id']);
            $table->index(['student_id', 'classroom_id']);
            $table->foreign('classroom_id')->references('id')->on('the404_classrooms')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('the404_users')->restrictOnDelete();
        });

        Schema::create('the404_classroom_courses', function (Blueprint $table) {
            $table->unsignedInteger('classroom_id');
            $table->unsignedInteger('course_id');
            $table->timestamps();

            $table->unique(['classroom_id', 'course_id']);
            $table->index(['course_id', 'classroom_id']);
            $table->foreign('classroom_id')->references('id')->on('the404_classrooms')->restrictOnDelete();
            $table->foreign('course_id')->references('id')->on('the404_courses')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_classroom_courses');
        Schema::dropIfExists('the404_classroom_students');
        Schema::dropIfExists('the404_classroom_teachers');
        Schema::dropIfExists('the404_classrooms');
    }
};

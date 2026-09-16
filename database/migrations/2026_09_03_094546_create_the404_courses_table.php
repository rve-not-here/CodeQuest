<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the production the404_courses table, used to build the test database.
     */
    public function up(): void
    {
        Schema::create('the404_courses', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->string('type', 16)->default('html');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'locked', 'draft'])->default('active');
            $table->unsignedInteger('order_num')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_courses');
    }
};

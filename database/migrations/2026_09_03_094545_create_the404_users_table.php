<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirror of the production the404_users table, used to build the test database.
     */
    public function up(): void
    {
        Schema::create('the404_users', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('username', 64)->unique();
            $table->string('password');
            $table->string('name', 128)->default('');
            $table->enum('role', ['student', 'teacher', 'admin', 'operator'])->default('student');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('the404_users');
    }
};

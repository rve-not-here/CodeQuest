<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-authored announcements (US-807, §39/§31). created_by restrictOnDelete
     * so an account can never silently carry away its published announcements.
     * Status (draft/published/archived) is the lifecycle gate; published_at is
     * set once on the first publish. audience targets who receives a SYSTEM_ANNOUNCEMENT
     * notification on publish — operator is excluded from delivery by design.
     */
    public function up(): void
    {
        Schema::create('the404_announcements', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('created_by');
            $table->string('title', 160);
            $table->text('message');
            $table->enum('audience', ['all', 'students', 'teachers', 'admins'])->default('all');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('status');
            $table->index('audience');
            $table->index('created_at');
            $table->foreign('created_by')->references('id')->on('the404_users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_announcements');
    }
};

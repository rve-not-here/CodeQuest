<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedicated admin audit trail (approved with US-701's §28 proposal).
     * Deliberately NOT the404_activity: the student timeline and the admin
     * audit are separate vocabularies (a login/logout row, for example, is a
     * timeline non-event but an admin audit must survive it). Rows are
     * append-only — admin_user_id restricts on delete so an account can never
     * silently carry away its administration history. Only created_at is a
     * useCurrent timestamp; there is no updated_at (audit rows are immutable).
     */
    public function up(): void
    {
        Schema::create('the404_admin_audit', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('admin_user_id');
            $table->string('admin_username', 64);
            $table->string('action', 64);
            $table->string('target_type', 40)->nullable();
            $table->unsignedInteger('target_id')->nullable();
            $table->text('summary');
            $table->enum('result', ['success', 'failed'])->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->index('admin_user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['target_type', 'target_id']);
            $table->foreign('admin_user_id')->references('id')->on('the404_users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_admin_audit');
    }
};

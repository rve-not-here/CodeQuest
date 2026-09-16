<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user notification rows (US-801, §10/§39). RestrictOnDelete on
     * user_id, matching the achievement/attempt/audit precedent: a
     * TEACHER_ATTENTION alert's evidence, or the record that a student was
     * notified of an assessment pass or account event, is exactly the kind of
     * history a future audit or dispute wants preserved. If account deletion
     * ever becomes real (Phase 7 §5 floated it), the restrict FK forces a
     * deliberate review of that history instead of silently destroying it.
     *
     * created_at is the only timestamp — a notification row is append-only
     * from the app's perspective and has no updated_at. The unique
     * (user_id, dedupe_key) index is the duplicate-prevention backstop (§17);
     * NULL dedupe_keys (frequency-governed recurring notifications) are
     * distinct under the unique index.
     */
    public function up(): void
    {
        Schema::create('the404_notifications', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->string('type', 40);
            $table->string('title', 160);
            $table->text('message');
            $table->text('data')->nullable();
            $table->string('dedupe_key', 100)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'dedupe_key']);
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->foreign('user_id')->references('id')->on('the404_users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_notifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-account UI preferences. The theme currently lives here so it follows
     * a user across browsers and devices instead of being trapped in one
     * browser's localStorage.
     *
     * One row per user (unique user_id), cascadeOnDelete like mission_drafts:
     * the row is derived UI state, not audit history, so it can disappear with
     * the account. The migration runs only under RefreshDatabase against the
     * in-memory SQLite test schema — production system404 must be created
     * deliberately with the matching SQL and explicit approval.
     */
    public function up(): void
    {
        Schema::create('the404_user_preferences', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('user_id');
            $table->string('theme', 8)->default('light');
            $table->timestamps();

            // Explicit short names: MariaDB caps identifiers at 64 characters.
            $table->unique('user_id', 'upref_user_unique');
            $table->foreign('user_id', 'upref_user_foreign')
                ->references('id')
                ->on('the404_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_user_preferences');
    }
};

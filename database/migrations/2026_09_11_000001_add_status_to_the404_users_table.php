<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the account-status column to the404_users for the Phase 7 admin
     * accounts (US-701). Runs only on the isolated test connection via
     * RefreshDatabase; it is NOT run against the production system404 database
     * unless the user explicitly approves that migration.
     *
     * 'active' is the default so every pre-existing account stays active on
     * deploy. Deactivating an account never touches its progress, XP, attempts,
     * or activity rows: account status is a sign-in gate, not a learning-state
     * change (§17).
     */
    public function up(): void
    {
        Schema::table('the404_users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('the404_users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

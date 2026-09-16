<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('the404_missions', function (Blueprint $table) {
            $table->unsignedInteger('section_id')->nullable()->after('course_id');
            $table->foreign('section_id')->references('id')->on('the404_sections')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('the404_missions', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn('section_id');
        });
    }
};

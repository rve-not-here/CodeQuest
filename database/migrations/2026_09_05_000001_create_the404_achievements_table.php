<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The achievement catalog (§27/§41). Rows are seeded system content,
     * never user-authored: the slug is the stable machine key the award
     * logic switches on, the human title and description are display.
     */
    public function up(): void
    {
        Schema::create('the404_achievements', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->string('slug', 40)->unique();
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('the404_achievements');
    }
};

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
        Schema::create('log_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_hash')->unique();
            $table->string('category'); // database, validation, auth, etc.
            $table->text('pattern_description');
            $table->text('common_message');
            $table->integer('occurrence_count')->default(1);
            $table->text('ai_suggestion')->nullable();
            $table->timestamp('last_seen');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_patterns');
    }
};

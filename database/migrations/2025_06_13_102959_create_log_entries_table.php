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
        Schema::create('log_entries', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // error, warning, info, debug
            $table->timestamp('log_timestamp');
            $table->string('environment')->nullable();
            $table->text('message');
            $table->longText('context')->nullable(); // JSON
            $table->longText('stack_trace')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->string('hash')->unique(); // For deduplication
            $table->integer('occurrence_count')->default(1);
            $table->timestamps();
            
            $table->index(['level', 'created_at']);
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};

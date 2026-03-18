<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('question_id')->constrained('questions');
            $table->text('answer_text');
            $table->date('target_date');
            $table->timestamp('summarized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'question_id', 'target_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_logs');
    }
};

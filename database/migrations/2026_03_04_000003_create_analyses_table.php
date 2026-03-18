<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('viewer_id')->nullable()->constrained('users');
            $table->string('status')->default('pending');
            $table->text('summary_content')->nullable();
            $table->text('annotation_text')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};

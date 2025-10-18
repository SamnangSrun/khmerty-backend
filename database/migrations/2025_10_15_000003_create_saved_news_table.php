<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('news_post_id')->constrained('news_posts')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate saves
            $table->unique(['user_id', 'news_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_news');
    }
};

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
        Schema::create('review_summaries', function (Blueprint $table) {
            $table->id();
            $table->morphs('reviewable');
            $table->decimal('average_rating', 9, 2)->default(0);
            $table->bigInteger('review_count')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_summaries');
    }
};
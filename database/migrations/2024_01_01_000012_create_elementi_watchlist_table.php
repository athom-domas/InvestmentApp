<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elementi_watchlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watchlist_id')->constrained('watchlists')->cascadeOnDelete();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['watchlist_id', 'titolo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elementi_watchlist');
    }
};

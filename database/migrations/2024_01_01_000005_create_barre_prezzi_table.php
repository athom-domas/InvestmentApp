<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barre_prezzi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->date('data');
            $table->decimal('apertura', 20, 6)->nullable();
            $table->decimal('massimo', 20, 6)->nullable();
            $table->decimal('minimo', 20, 6)->nullable();
            $table->decimal('chiusura', 20, 6);
            $table->decimal('chiusura_rettificata', 20, 6)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->timestamps();

            $table->unique(['titolo_id', 'data']);
            $table->index('data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barre_prezzi');
    }
};

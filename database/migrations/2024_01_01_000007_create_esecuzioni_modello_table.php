<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('esecuzioni_modello', function (Blueprint $table) {
            $table->id();
            $table->string('versione_modello');
            $table->string('universo')->nullable();
            $table->dateTime('data_taglio')->nullable();
            $table->string('hash_configurazione')->nullable();
            $table->string('stato')->default('pending');
            $table->dateTime('iniziato_a')->nullable();
            $table->dateTime('terminato_a')->nullable();
            $table->json('metadati')->nullable();
            $table->timestamps();

            $table->index('versione_modello');
            $table->index('stato');
            $table->index('data_taglio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esecuzioni_modello');
    }
};

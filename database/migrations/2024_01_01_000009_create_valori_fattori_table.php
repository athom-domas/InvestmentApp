<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('valori_fattori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esecuzione_modello_id')->constrained('esecuzioni_modello')->cascadeOnDelete();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->string('codice_fattore');
            $table->decimal('valore_grezzo', 24, 8)->nullable();
            $table->decimal('valore_normalizzato', 12, 8)->nullable();
            $table->decimal('punteggio', 8, 4)->nullable();
            $table->text('spiegazione')->nullable();
            $table->json('metadati')->nullable();
            $table->timestamps();

            // nome breve perché MySQL ha limite 64 char sui nomi degli indici
            $table->unique(
                ['esecuzione_modello_id', 'titolo_id', 'codice_fattore'],
                'valori_fattori_run_titolo_fattore_unique'
            );
            $table->index('titolo_id');
            $table->index('codice_fattore');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('valori_fattori');
    }
};

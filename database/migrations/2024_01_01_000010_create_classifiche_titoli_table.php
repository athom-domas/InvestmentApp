<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('classifiche_titoli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esecuzione_modello_id')->constrained('esecuzioni_modello')->cascadeOnDelete();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->decimal('punteggio_finale', 8, 4);
            $table->unsignedInteger('posizione')->nullable();
            $table->decimal('punteggio_qualita', 8, 4)->nullable();
            $table->decimal('punteggio_valore', 8, 4)->nullable();
            $table->decimal('punteggio_crescita', 8, 4)->nullable();
            $table->decimal('punteggio_momentum', 8, 4)->nullable();
            $table->decimal('punteggio_solidita_finanziaria', 8, 4)->nullable();
            $table->decimal('punteggio_rischio', 8, 4)->nullable();
            $table->text('riepilogo')->nullable();
            $table->text('rischi')->nullable();
            $table->json('metadati')->nullable();
            $table->timestamps();

            $table->unique(['esecuzione_modello_id', 'titolo_id']);
            $table->index('punteggio_finale');
            $table->index('posizione');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classifiche_titoli');
    }
};

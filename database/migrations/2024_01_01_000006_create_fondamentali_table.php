<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fondamentali', function (Blueprint $table) {
            $table->id();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->string('periodo_fiscale')->nullable();
            $table->unsignedSmallInteger('anno_fiscale')->nullable();
            $table->date('data_fine_periodo')->nullable();
            $table->decimal('ricavi', 24, 2)->nullable();
            $table->decimal('utile_lordo', 24, 2)->nullable();
            $table->decimal('reddito_operativo', 24, 2)->nullable();
            $table->decimal('utile_netto', 24, 2)->nullable();
            $table->decimal('ebitda', 24, 2)->nullable();
            $table->decimal('flusso_cassa_libero', 24, 2)->nullable();
            $table->decimal('totale_attivo', 24, 2)->nullable();
            $table->decimal('totale_passivo', 24, 2)->nullable();
            $table->decimal('debito_totale', 24, 2)->nullable();
            $table->decimal('liquidita', 24, 2)->nullable();
            $table->decimal('patrimonio_netto', 24, 2)->nullable();
            $table->decimal('azioni_in_circolazione', 24, 2)->nullable();
            $table->decimal('eps', 20, 6)->nullable();
            $table->decimal('rapporto_pe', 20, 6)->nullable();
            $table->decimal('ev_ebitda', 20, 6)->nullable();
            $table->decimal('prezzo_fatturato', 20, 6)->nullable();
            $table->decimal('prezzo_valore_contabile', 20, 6)->nullable();
            $table->decimal('rendimento_patrimonio', 20, 6)->nullable();
            $table->decimal('rendimento_attivo', 20, 6)->nullable();
            $table->decimal('rapporto_debito_patrimonio', 20, 6)->nullable();
            $table->decimal('margine_lordo', 20, 6)->nullable();
            $table->decimal('margine_operativo', 20, 6)->nullable();
            $table->decimal('margine_netto', 20, 6)->nullable();
            $table->json('metadati')->nullable();
            $table->timestamps();

            // nome breve perché MySQL ha limite 64 char sui nomi degli indici
            $table->unique(
                ['titolo_id', 'periodo_fiscale', 'anno_fiscale', 'data_fine_periodo'],
                'fondamentali_periodo_unique'
            );
            $table->index('data_fine_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fondamentali');
    }
};

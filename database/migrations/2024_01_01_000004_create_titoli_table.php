<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('titoli', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borsa_id')->nullable()->constrained('borse');
            $table->foreignId('settore_id')->nullable()->constrained('settori');
            $table->foreignId('industria_id')->nullable()->constrained('industrie');
            $table->string('ticker');
            $table->string('nome');
            $table->string('isin')->nullable();
            $table->string('valuta', 3)->nullable();
            $table->string('paese')->nullable();
            $table->decimal('capitalizzazione_mercato', 24, 2)->nullable();
            $table->boolean('attivo')->default(true);
            $table->json('metadati')->nullable();
            $table->timestamps();

            $table->unique(['borsa_id', 'ticker']);
            $table->index('ticker');
            $table->index('attivo');
            $table->index('settore_id');
            $table->index('industria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titoli');
    }
};

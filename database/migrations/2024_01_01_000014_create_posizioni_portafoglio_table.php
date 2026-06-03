<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posizioni_portafoglio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portafoglio_id')->constrained('portafogli')->cascadeOnDelete();
            $table->foreignId('titolo_id')->constrained('titoli');
            $table->decimal('quantita', 24, 8);
            $table->decimal('prezzo_medio', 20, 6)->nullable();
            $table->string('valuta', 3)->nullable();
            $table->date('aperto_il')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posizioni_portafoglio');
    }
};

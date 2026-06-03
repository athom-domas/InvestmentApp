<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sorgenti_dati', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->nullable();
            $table->string('url')->nullable();
            $table->text('note_termini')->nullable();
            $table->boolean('attivo')->default(true);
            $table->json('metadati')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorgenti_dati');
    }
};

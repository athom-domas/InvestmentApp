<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('definizioni_fattori', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('nome');
            $table->text('descrizione')->nullable();
            $table->decimal('peso', 8, 4)->default(0);
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('definizioni_fattori');
    }
};

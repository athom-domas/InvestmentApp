<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('borse', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('nome');
            $table->string('paese')->nullable();
            $table->string('valuta', 3)->nullable();
            $table->string('fuso_orario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borse');
    }
};

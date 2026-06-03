<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_bars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_id')->constrained();
            $table->date('date');
            $table->decimal('open', 20, 6)->nullable();
            $table->decimal('high', 20, 6)->nullable();
            $table->decimal('low', 20, 6)->nullable();
            $table->decimal('close', 20, 6);
            $table->decimal('adjusted_close', 20, 6)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->timestamps();

            $table->unique(['security_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_bars');
    }
};

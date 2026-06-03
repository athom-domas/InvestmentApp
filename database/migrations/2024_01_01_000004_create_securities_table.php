<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('securities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->nullable()->constrained();
            $table->foreignId('sector_id')->nullable()->constrained();
            $table->foreignId('industry_id')->nullable()->constrained();
            $table->string('ticker');
            $table->string('name');
            $table->string('isin')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('country')->nullable();
            $table->decimal('market_cap', 24, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['exchange_id', 'ticker']);
            $table->index('ticker');
            $table->index('is_active');
            $table->index('sector_id');
            $table->index('industry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('securities');
    }
};

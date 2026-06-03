<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factor_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_id')->constrained();
            $table->string('factor_code');
            $table->decimal('raw_value', 24, 8)->nullable();
            $table->decimal('normalized_value', 12, 8)->nullable();
            $table->decimal('score', 8, 4)->nullable();
            $table->text('explanation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['model_run_id', 'security_id', 'factor_code']);
            $table->index('security_id');
            $table->index('factor_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factor_values');
    }
};

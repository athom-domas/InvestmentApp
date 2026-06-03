<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('security_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_id')->constrained();
            $table->decimal('final_score', 8, 4);
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('quality_score', 8, 4)->nullable();
            $table->decimal('value_score', 8, 4)->nullable();
            $table->decimal('growth_score', 8, 4)->nullable();
            $table->decimal('momentum_score', 8, 4)->nullable();
            $table->decimal('financial_strength_score', 8, 4)->nullable();
            $table->decimal('risk_score', 8, 4)->nullable();
            $table->text('summary')->nullable();
            $table->text('risks')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['model_run_id', 'security_id']);
            $table->index('final_score');
            $table->index('rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_rankings');
    }
};

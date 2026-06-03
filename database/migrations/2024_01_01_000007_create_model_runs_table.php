<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('model_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model_version');
            $table->string('universe')->nullable();
            $table->dateTime('data_cutoff_at')->nullable();
            $table->string('config_hash')->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('model_version');
            $table->index('status');
            $table->index('data_cutoff_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_runs');
    }
};

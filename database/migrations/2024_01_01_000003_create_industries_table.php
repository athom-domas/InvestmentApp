<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained();
            $table->string('name');
            $table->timestamps();

            $table->unique(['sector_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};

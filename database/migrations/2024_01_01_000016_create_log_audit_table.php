<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('azione');
            $table->string('tipo_entita')->nullable();
            $table->unsignedBigInteger('id_entita')->nullable();
            $table->json('payload')->nullable();
            $table->string('indirizzo_ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('azione');
            $table->index(['tipo_entita', 'id_entita']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_audit');
    }
};

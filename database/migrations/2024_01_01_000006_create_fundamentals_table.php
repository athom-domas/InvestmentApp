<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fundamentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_id')->constrained();
            $table->string('fiscal_period')->nullable();
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->date('period_end_date')->nullable();
            $table->decimal('revenue', 24, 2)->nullable();
            $table->decimal('gross_profit', 24, 2)->nullable();
            $table->decimal('operating_income', 24, 2)->nullable();
            $table->decimal('net_income', 24, 2)->nullable();
            $table->decimal('ebitda', 24, 2)->nullable();
            $table->decimal('free_cash_flow', 24, 2)->nullable();
            $table->decimal('total_assets', 24, 2)->nullable();
            $table->decimal('total_liabilities', 24, 2)->nullable();
            $table->decimal('total_debt', 24, 2)->nullable();
            $table->decimal('cash_and_equivalents', 24, 2)->nullable();
            $table->decimal('shareholders_equity', 24, 2)->nullable();
            $table->decimal('shares_outstanding', 24, 2)->nullable();
            $table->decimal('eps', 20, 6)->nullable();
            $table->decimal('pe_ratio', 20, 6)->nullable();
            $table->decimal('ev_ebitda', 20, 6)->nullable();
            $table->decimal('price_to_sales', 20, 6)->nullable();
            $table->decimal('price_to_book', 20, 6)->nullable();
            $table->decimal('return_on_equity', 20, 6)->nullable();
            $table->decimal('return_on_assets', 20, 6)->nullable();
            $table->decimal('debt_to_equity', 20, 6)->nullable();
            $table->decimal('gross_margin', 20, 6)->nullable();
            $table->decimal('operating_margin', 20, 6)->nullable();
            $table->decimal('net_margin', 20, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // custom name: auto-generated exceeds MySQL's 64-char index name limit
            $table->unique(
                ['security_id', 'fiscal_period', 'fiscal_year', 'period_end_date'],
                'fundamentals_period_unique'
            );
            $table->index('period_end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundamentals');
    }
};

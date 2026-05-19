<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_facts', function (Blueprint $table): void {
            $table->id();
            $table->string('report_key');
            $table->string('bucket');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('store_id');
            $table->string('currency', 3);
            $table->date('reported_at');

            $table->index(['report_key', 'reported_at', 'store_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_facts');
    }
};

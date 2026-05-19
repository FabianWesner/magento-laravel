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
        Schema::create('domain_facts', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_key');
            $table->unsignedInteger('entity_id');
            $table->unsignedInteger('store_id');
            $table->string('store_view');
            $table->json('payload');

            $table->index(['feature_key', 'store_id', 'store_view', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_facts');
    }
};

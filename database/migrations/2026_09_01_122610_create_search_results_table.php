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
        Schema::create('search_results', function (Blueprint $table) {
            $table->foreignId('search_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_snapshot_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');

            $table->primary(['search_id', 'host_snapshot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_results');
    }
};

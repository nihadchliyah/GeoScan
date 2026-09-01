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
        Schema::create('host_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->timestamp('fetched_at');
            $table->timestamp('shodan_last_update')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('organization')->nullable();
            $table->string('isp')->nullable();
            $table->string('asn')->nullable();
            $table->json('hostnames')->nullable();
            $table->json('domains')->nullable();
            $table->json('web_technologies')->nullable();
            $table->json('open_ports')->nullable();
            $table->timestamps();

            $table->index(['host_id', 'fetched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('host_snapshots');
    }
};

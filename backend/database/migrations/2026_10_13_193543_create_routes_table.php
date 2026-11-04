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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_stop_id')->constrained('bus_stops')->onDelete('cascade');
            $table->foreignId('destination_stop_id')->constrained('bus_stops')->onDelete('cascade');
            $table->string('route_name');
            $table->text('description')->nullable();
            $table->integer('estimated_duration')->nullable()->comment('Estimated travel time in minutes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};

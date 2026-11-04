<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('bus_stop_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('sequence');
            $table->decimal('fare_from_origin', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['route_id', 'bus_stop_id']);
            $table->unique(['route_id', 'sequence']);
            $table->index('bus_stop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};

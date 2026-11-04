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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->string('year');
            $table->string('license_plate')->unique();
            $table->string('color');
            $table->integer('capacity')->default(4);
            $table->string('vehicle_type')->default('sedan'); // sedan, suv, minivan, bus
            $table->boolean('is_active')->default(true);
            $table->string('vehicle_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

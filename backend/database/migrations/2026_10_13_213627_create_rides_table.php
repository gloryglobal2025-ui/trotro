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
        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');

            // Origin
            $table->decimal('origin_latitude', 10, 8);
            $table->decimal('origin_longitude', 11, 8);
            $table->string('origin_address');

            // Destination
            $table->decimal('destination_latitude', 10, 8);
            $table->decimal('destination_longitude', 11, 8);
            $table->string('destination_address');

            // Ride details
            $table->enum('status', ['pending', 'accepted', 'driver_arrived', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('pickup_at')->nullable();
            $table->timestamp('dropoff_at')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('fare_amount', 8, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();

            // Ratings
            $table->decimal('driver_rating', 2, 1)->nullable();
            $table->text('driver_comment')->nullable();
            $table->decimal('passenger_rating', 2, 1)->nullable();
            $table->text('passenger_comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rides');
    }
};

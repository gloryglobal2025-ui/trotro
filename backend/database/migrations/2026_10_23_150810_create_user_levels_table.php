<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Bronze, Silver, Gold, Platinum
            $table->string('slug')->unique();
            $table->string('user_type'); // driver, customer
            $table->decimal('commission_rate', 5, 2)->default(0.10); // Commission percentage
            $table->decimal('fare_discount', 5, 2)->default(0.00); // Fare discount percentage
            $table->integer('daily_rides_limit')->nullable();
            $table->integer('min_rating')->default(4.0);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};

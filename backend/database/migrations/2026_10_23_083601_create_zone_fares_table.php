<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_zone_id')->constrained('zones')->onDelete('cascade');
            $table->foreignId('to_zone_id')->constrained('zones')->onDelete('cascade');
            $table->decimal('base_fare', 8, 2)->default(5.00);
            $table->decimal('rate_per_km', 8, 2)->default(2.00);
            $table->decimal('min_fare', 8, 2)->default(5.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['from_zone_id', 'to_zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_fares');
    }
};

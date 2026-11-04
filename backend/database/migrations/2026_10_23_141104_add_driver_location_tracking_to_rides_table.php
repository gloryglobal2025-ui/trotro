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
        Schema::table('rides', function (Blueprint $table) {
            $table->decimal('driver_current_latitude', 10, 8)->nullable()->after('dropoff_at');
            $table->decimal('driver_current_longitude', 11, 8)->nullable()->after('driver_current_latitude');
            $table->timestamp('driver_last_location_update')->nullable()->after('driver_current_longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['driver_current_latitude', 'driver_current_longitude', 'driver_last_location_update']);
        });
    }
};

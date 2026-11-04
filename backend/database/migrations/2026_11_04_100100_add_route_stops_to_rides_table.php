<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('origin_stop_id')->nullable()->constrained('bus_stops')->nullOnDelete();
            $table->foreignId('destination_stop_id')->nullable()->constrained('bus_stops')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
            $table->dropConstrainedForeignId('origin_stop_id');
            $table->dropConstrainedForeignId('destination_stop_id');
        });
    }
};

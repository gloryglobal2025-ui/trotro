<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('passes', function (Blueprint $table) {
            $table->string('approval_channel', 20)->default('device_pin')->after('status');
        });

        Schema::create('pass_charge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pass_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fare_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ride_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 8, 2);
            $table->string('customer_phone', 20);
            $table->string('destination', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('channel', 20)->default('sms');
            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['pass_id', 'status']);
            $table->index(['customer_phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pass_charge_requests');

        Schema::table('passes', function (Blueprint $table) {
            $table->dropColumn('approval_channel');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('license_number', 50)->nullable()->after('role');
            $table->string('license_image')->nullable()->after('license_number');
            $table->date('license_expiry')->nullable()->after('license_image');
            $table->string('ghana_card_number', 50)->nullable()->after('license_expiry');
            $table->string('ghana_card_image')->nullable()->after('ghana_card_number');
            $table->enum('verification_status', ['unverified', 'pending', 'approved', 'rejected'])
                ->default('unverified')
                ->index()
                ->after('ghana_card_image');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->timestamp('verification_submitted_at')->nullable()->after('verification_notes');
            $table->timestamp('verified_at')->nullable()->after('verification_submitted_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'license_number',
                'license_image',
                'license_expiry',
                'ghana_card_number',
                'ghana_card_image',
                'verification_status',
                'verification_notes',
                'verification_submitted_at',
                'verified_at',
                'verified_by',
            ]);
        });
    }
};

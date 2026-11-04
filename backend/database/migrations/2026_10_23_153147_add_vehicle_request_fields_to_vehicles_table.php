<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'request_status')) {
                $table->enum('request_status', ['pending', 'approved', 'rejected'])->default('approved')->after('is_active');
            }
            if (!Schema::hasColumn('vehicles', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('request_status');
            }
            if (!Schema::hasColumn('vehicles', 'request_approved_at')) {
                $table->timestamp('request_approved_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('vehicles', 'request_approved_by')) {
                $table->foreignId('request_approved_by')->nullable()->after('request_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['request_approved_by']);
            $table->dropColumn(['request_status', 'rejection_reason', 'request_approved_at', 'request_approved_by']);
        });
    }
};

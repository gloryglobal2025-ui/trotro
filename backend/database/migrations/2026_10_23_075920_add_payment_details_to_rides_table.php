<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('transaction_id');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
            $table->text('payment_metadata')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_id',
                'payment_reference',
                'paid_at',
                'payment_metadata',
            ]);
        });
    }
};

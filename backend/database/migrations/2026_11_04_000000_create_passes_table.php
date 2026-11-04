<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('ride_count')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_assisted')->default(false)->index();
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->unsignedBigInteger('pass_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn('pass_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_assisted');
        });

        Schema::dropIfExists('passes');
    }
};

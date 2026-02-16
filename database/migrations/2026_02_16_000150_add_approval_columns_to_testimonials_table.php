<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('urls');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
        });

        DB::table('testimonials')
            ->where('is_approved', true)
            ->whereNull('approved_at')
            ->update(['approved_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'is_approved']);
        });
    }
};

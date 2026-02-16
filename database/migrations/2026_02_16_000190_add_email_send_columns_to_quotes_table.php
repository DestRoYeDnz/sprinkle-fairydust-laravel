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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('email_send_status', 32)->nullable()->after('calc_total_amount');
            $table->timestamp('email_send_attempted_at')->nullable()->after('email_send_status');
            $table->json('email_send_response')->nullable()->after('email_send_attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'email_send_status',
                'email_send_attempted_at',
                'email_send_response',
            ]);
        });
    }
};

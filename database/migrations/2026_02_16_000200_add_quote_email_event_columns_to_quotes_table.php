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
            $table->timestamp('client_confirmed_at')->nullable()->after('email_send_response');
            $table->timestamp('email_opened_at')->nullable()->after('client_confirmed_at');
            $table->timestamp('email_last_opened_at')->nullable()->after('email_opened_at');
            $table->unsignedInteger('email_open_count')->default(0)->after('email_last_opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'client_confirmed_at',
                'email_opened_at',
                'email_last_opened_at',
                'email_open_count',
            ]);
        });
    }
};

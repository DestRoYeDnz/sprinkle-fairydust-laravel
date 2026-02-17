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
            $table->timestamp('client_suggested_time_at')->nullable()->after('artist_decline_reason');
            $table->date('client_suggested_event_date')->nullable()->after('client_suggested_time_at');
            $table->time('client_suggested_start_time')->nullable()->after('client_suggested_event_date');
            $table->time('client_suggested_end_time')->nullable()->after('client_suggested_start_time');
            $table->text('client_suggested_time_notes')->nullable()->after('client_suggested_end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'client_suggested_time_at',
                'client_suggested_event_date',
                'client_suggested_start_time',
                'client_suggested_end_time',
                'client_suggested_time_notes',
            ]);
        });
    }
};

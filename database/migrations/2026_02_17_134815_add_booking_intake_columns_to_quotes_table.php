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
            $table->string('phone', 48)->nullable()->after('email');
            $table->unsignedSmallInteger('guest_count')->nullable()->after('phone');
            $table->string('package_name')->nullable()->after('guest_count');
            $table->json('services_requested')->nullable()->after('package_name');
            $table->string('travel_area')->nullable()->after('services_requested');
            $table->string('venue_type', 32)->nullable()->after('travel_area');
            $table->string('heard_about', 120)->nullable()->after('venue_type');
            $table->text('notes')->nullable()->after('heard_about');
            $table->boolean('terms_accepted')->default(false)->after('notes');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'guest_count',
                'package_name',
                'services_requested',
                'travel_area',
                'venue_type',
                'heard_about',
                'notes',
                'terms_accepted',
                'terms_accepted_at',
            ]);
        });
    }
};

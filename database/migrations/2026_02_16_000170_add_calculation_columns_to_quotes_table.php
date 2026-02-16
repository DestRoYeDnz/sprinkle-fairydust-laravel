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
            $table->string('calc_payment_type', 32)->nullable()->after('total_hours');
            $table->decimal('calc_base_amount', 10, 2)->nullable()->after('calc_payment_type');
            $table->decimal('calc_setup_amount', 10, 2)->nullable()->after('calc_base_amount');
            $table->decimal('calc_travel_amount', 10, 2)->nullable()->after('calc_setup_amount');
            $table->decimal('calc_subtotal', 10, 2)->nullable()->after('calc_travel_amount');
            $table->decimal('calc_gst_amount', 10, 2)->nullable()->after('calc_subtotal');
            $table->decimal('calc_total_amount', 10, 2)->nullable()->after('calc_gst_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'calc_payment_type',
                'calc_base_amount',
                'calc_setup_amount',
                'calc_travel_amount',
                'calc_subtotal',
                'calc_gst_amount',
                'calc_total_amount',
            ]);
        });
    }
};

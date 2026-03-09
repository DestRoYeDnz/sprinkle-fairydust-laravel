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
            $table->string('calc_discount_name', 120)->nullable()->after('calc_travel_amount');
            $table->string('calc_discount_description', 255)->nullable()->after('calc_discount_name');
            $table->decimal('calc_discount_amount', 10, 2)->nullable()->after('calc_discount_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'calc_discount_name',
                'calc_discount_description',
                'calc_discount_amount',
            ]);
        });
    }
};

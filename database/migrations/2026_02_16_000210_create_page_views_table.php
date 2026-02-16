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
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('anonymous_id', 80)->index();
            $table->string('page_key', 80)->index();
            $table->string('path', 255)->index();
            $table->string('country_code', 8)->nullable()->index();
            $table->string('referrer', 512)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('viewed_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};

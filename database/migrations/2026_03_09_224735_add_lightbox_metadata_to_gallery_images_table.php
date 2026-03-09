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
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->string('title')->nullable()->after('alt_text');
            $table->text('description')->nullable()->after('title');
        });

        DB::table('gallery_images')
            ->whereNull('event_id')
            ->where('collection', 'gallery')
            ->whereNotNull('alt_text')
            ->update([
                'title' => DB::raw('alt_text'),
            ]);

        DB::table('gallery_images')
            ->whereNull('event_id')
            ->where('collection', 'gallery')
            ->whereNull('title')
            ->update([
                'title' => 'Sprinkle Fairydust Gallery',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};

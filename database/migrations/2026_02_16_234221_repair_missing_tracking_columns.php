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
        $quotesMissingAnonymousId = ! Schema::hasColumn('quotes', 'anonymous_id');
        $pageViewsMissingEventType = ! Schema::hasColumn('page_views', 'event_type');
        $pageViewsMissingDuration = ! Schema::hasColumn('page_views', 'duration_seconds');

        if ($quotesMissingAnonymousId) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->string('anonymous_id', 80)->nullable()->after('email')->index();
            });
        }

        if ($pageViewsMissingEventType || $pageViewsMissingDuration) {
            Schema::table('page_views', function (Blueprint $table) use ($pageViewsMissingDuration, $pageViewsMissingEventType) {
                if ($pageViewsMissingEventType) {
                    $table->string('event_type', 24)->default('view')->after('path')->index();
                }

                if ($pageViewsMissingDuration) {
                    $afterColumn = $pageViewsMissingEventType ? 'event_type' : 'path';
                    $table->unsignedInteger('duration_seconds')->nullable()->after($afterColumn);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('quotes', 'anonymous_id')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropColumn('anonymous_id');
            });
        }

        $pageViewsHasEventType = Schema::hasColumn('page_views', 'event_type');
        $pageViewsHasDuration = Schema::hasColumn('page_views', 'duration_seconds');

        if ($pageViewsHasEventType || $pageViewsHasDuration) {
            Schema::table('page_views', function (Blueprint $table) use ($pageViewsHasDuration, $pageViewsHasEventType) {
                $columns = [];

                if ($pageViewsHasEventType) {
                    $columns[] = 'event_type';
                }

                if ($pageViewsHasDuration) {
                    $columns[] = 'duration_seconds';
                }

                $table->dropColumn($columns);
            });
        }
    }
};

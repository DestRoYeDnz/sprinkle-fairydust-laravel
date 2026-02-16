<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'anonymous_id',
        'page_key',
        'path',
        'event_type',
        'duration_seconds',
        'referrer',
        'country_code',
        'user_agent',
        'viewed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'viewed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];
}

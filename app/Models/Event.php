<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'visibility',
        'address',
        'date',
        'start_time',
        'end_time',
        'description',
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Extra gallery photos attached to this event.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryImage::class)
            ->where('collection', 'events')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }
}

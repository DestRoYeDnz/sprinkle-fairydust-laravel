<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
        'anonymous_id',
        'event_type',
        'event_date',
        'address',
        'start_time',
        'end_time',
        'total_hours',
        'calc_payment_type',
        'calc_base_amount',
        'calc_setup_amount',
        'calc_travel_amount',
        'calc_subtotal',
        'calc_gst_amount',
        'calc_total_amount',
        'email_send_status',
        'email_send_attempted_at',
        'email_send_response',
        'client_confirmed_at',
        'email_opened_at',
        'email_last_opened_at',
        'email_open_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'guest_count' => 'integer',
        'services_requested' => 'array',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'event_date' => 'date',
        'total_hours' => 'float',
        'calc_base_amount' => 'float',
        'calc_setup_amount' => 'float',
        'calc_travel_amount' => 'float',
        'calc_subtotal' => 'float',
        'calc_gst_amount' => 'float',
        'calc_total_amount' => 'float',
        'email_send_attempted_at' => 'datetime',
        'email_send_response' => 'array',
        'client_confirmed_at' => 'datetime',
        'email_opened_at' => 'datetime',
        'email_last_opened_at' => 'datetime',
        'email_open_count' => 'integer',
    ];

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class, 'anonymous_id', 'anonymous_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'travel_date',
        'notes',
        'status'
    ];

    protected $casts = [
        'travel_date' => 'date'
    ];

    protected static function booted(): void
    {
        static::creating(function ($booking) {

            $booking->booking_code =
                'BOOK-'
                . strtoupper(fake()->bothify('###??'));
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
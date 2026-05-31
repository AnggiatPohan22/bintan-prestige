<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONTACTED = 'contacted';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
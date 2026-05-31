<?php

namespace App\Enums;

enum ProductCategory: string
{
    case TAXI = 'Taxi';
    case TOUR_PACKAGE = 'Tour Package';
    case ACTIVITY = 'Activity';
}
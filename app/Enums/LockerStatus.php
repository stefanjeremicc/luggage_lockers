<?php

namespace App\Enums;

enum LockerStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case Offline = 'offline';
}

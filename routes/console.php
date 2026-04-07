<?php

use App\Jobs\HandleExpiredBookings;
use App\Jobs\SendExpiryReminder;
use App\Jobs\SyncAccessCodes;
use App\Jobs\SyncGateways;
use App\Jobs\SyncLockerStatus;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncLockerStatus)->everyTwoMinutes();
Schedule::job(new SyncAccessCodes)->everyThreeMinutes();
Schedule::job(new SyncGateways)->everyFiveMinutes();
Schedule::job(new HandleExpiredBookings)->everyMinute();
Schedule::job(new SendExpiryReminder)->everyMinute();

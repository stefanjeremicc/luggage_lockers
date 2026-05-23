<?php

use App\Jobs\DeleteExpiredBookingPins;
use App\Jobs\HandleExpiredBookings;
use App\Jobs\SendReviewRequest;
use App\Jobs\SyncAccessCodes;
use App\Jobs\SyncGateways;
use App\Jobs\SyncLockerStatus;
use App\Jobs\SyncUnlockRecords;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncLockerStatus)->everyTwoMinutes();
Schedule::job(new SyncAccessCodes)->everyThreeMinutes();
Schedule::job(new SyncGateways)->everyFiveMinutes();
Schedule::job(new SyncUnlockRecords)->everyFiveMinutes();
Schedule::job(new HandleExpiredBookings)->everyMinute();
// TTLock passcodes are scheduled with a +30 min tail buffer. We clean them up
// from TTLock cloud once the buffered window has elapsed (i.e. check_out + 30 min).
Schedule::job(new DeleteExpiredBookingPins)->everyMinute();
// Review-request email fires 30 min after the customer's actual check_out.
Schedule::job(new SendReviewRequest)->everyFiveMinutes();

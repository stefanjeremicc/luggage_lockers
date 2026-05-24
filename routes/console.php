<?php

use App\Jobs\DeleteExpiredBookingPins;
use App\Jobs\HandleExpiredBookings;
use App\Jobs\SendReviewRequest;
use App\Jobs\SyncAccessCodes;
use App\Jobs\SyncGateways;
use App\Jobs\SyncLockerStatus;
use App\Jobs\SyncUnlockRecords;
use Illuminate\Support\Facades\Schedule;

// Frequencies tuned to stay well under TTLock's 30k/month API budget.
// Battery/online + gateway state change slowly; unlock records are scoped to
// active-booking lockers only (and primarily delivered via the TTLock webhook).
Schedule::job(new SyncLockerStatus)->everyThirtyMinutes();
Schedule::job(new SyncGateways)->everyThirtyMinutes();
Schedule::job(new SyncUnlockRecords)->everyThirtyMinutes();
Schedule::job(new HandleExpiredBookings)->everyMinute();
// TTLock passcodes are scheduled with a +30 min tail buffer. We clean them up
// from TTLock cloud once the buffered window has elapsed (i.e. check_out + 30 min).
Schedule::job(new DeleteExpiredBookingPins)->everyMinute();
// Review-request email fires 30 min after the customer's actual check_out.
Schedule::job(new SendReviewRequest)->everyFiveMinutes();

<?php

namespace App\Services\Lock;

use Carbon\Carbon;

interface LockServiceInterface
{
    public function createTimedAccessCode(int $lockId, string $code, Carbon $start, Carbon $end): array;
    public function deleteAccessCode(int $lockId, int $keyboardPwdId): bool;
    public function updateAccessCodeTime(int $lockId, int $keyboardPwdId, Carbon $newEnd): bool;
    public function remoteLock(int $lockId): bool;
    public function remoteUnlock(int $lockId): bool;
    public function getLockDetail(int $lockId): array;
    public function getLockList(): array;
    public function getGatewayList(): array;
    public function getUnlockRecords(int $lockId, Carbon $start, Carbon $end): array;
}

<?php

namespace App\Services\Lock;

use Carbon\Carbon;

interface LockServiceInterface
{
    // Passcodes
    public function createTimedAccessCode(int $lockId, string $code, Carbon $start, Carbon $end): array;
    public function createPasscode(int $lockId, string $code, int $type, ?Carbon $start, ?Carbon $end, ?string $name): array;
    public function deleteAccessCode(int $lockId, int $keyboardPwdId): bool;
    public function updateAccessCodeTime(int $lockId, int $keyboardPwdId, Carbon $newEnd): bool;
    public function getPasscodes(int $lockId, int $page = 1, int $pageSize = 100): array;

    // Remote control
    public function remoteLock(int $lockId): bool;
    public function remoteUnlock(int $lockId): bool;

    // Lock info
    public function getLockDetail(int $lockId): array;
    public function getLockList(): array;
    public function getGatewayList(): array;
    public function renameLockAlias(int $lockId, string $alias): bool;

    // Records
    public function getUnlockRecords(int $lockId, Carbon $start, Carbon $end): array;
}

<?php

namespace App\Services\Booking;

use App\Models\PricingRule;
use App\Models\Setting;

class PricingService
{
    public function calculate(int $locationId, string $size, string $duration, int $qty = 1): array
    {
        // Try location-specific rule first, then global
        $rule = PricingRule::active()
            ->where('location_id', $locationId)
            ->where('locker_size', $size)
            ->where('duration_key', $duration)
            ->first();

        if (!$rule) {
            $rule = PricingRule::active()
                ->global()
                ->where('locker_size', $size)
                ->where('duration_key', $duration)
                ->first();
        }

        if (!$rule) {
            return ['error' => 'No pricing rule found'];
        }

        $unitPrice = (float) $rule->price_eur;
        $subtotal = $unitPrice * $qty;
        $serviceFee = (float) Setting::getValue('service_fee_eur', 0);
        $total = $subtotal + $serviceFee;
        $eurRsdRate = (float) Setting::getValue('eur_rsd_rate', 120);
        // Round to the nearest 10 RSD — cleaner number to display and to pay in cash.
        $totalRsd = (int) (round(($total * $eurRsdRate) / 10) * 10);

        $availabilityService = new AvailabilityService();

        return [
            'unit_price_eur' => $unitPrice,
            'qty' => $qty,
            'subtotal_eur' => $subtotal,
            'service_fee_eur' => $serviceFee,
            'total_eur' => $total,
            'total_rsd' => $totalRsd,
            'duration_label' => $availabilityService->getDurationLabel($duration),
        ];
    }

    /**
     * Multi-size pricing — used when a booking contains lockers of different sizes
     * (e.g. 2× standard + 1× large in the same cart). Returns per-line breakdown plus
     * an aggregated total in EUR + RSD.
     *
     * @param array<int, array{size: string, qty: int}> $items
     */
    public function calculateForItems(int $locationId, array $items, string $duration): array
    {
        $lines = [];
        $totalEur = 0.0;
        $totalQty = 0;

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty < 1) continue;
            $size = $item['size'];

            $rule = PricingRule::active()
                ->where('location_id', $locationId)
                ->where('locker_size', $size)
                ->where('duration_key', $duration)
                ->first()
                ?? PricingRule::active()->global()
                    ->where('locker_size', $size)
                    ->where('duration_key', $duration)
                    ->first();

            if (!$rule) {
                return ['error' => "No pricing rule found for {$size}/{$duration}"];
            }

            $unit = (float) $rule->price_eur;
            $lineTotal = $unit * $qty;
            $totalEur += $lineTotal;
            $totalQty += $qty;
            $lines[] = [
                'size' => $size,
                'qty' => $qty,
                'unit_price_eur' => $unit,
                'subtotal_eur' => $lineTotal,
            ];
        }

        $serviceFee = (float) Setting::getValue('service_fee_eur', 0);
        $totalEur += $serviceFee;

        $eurRsdRate = (float) Setting::getValue('eur_rsd_rate', 120);
        $totalRsd = (int) (round(($totalEur * $eurRsdRate) / 10) * 10);

        $availabilityService = new AvailabilityService();

        return [
            'lines' => $lines,
            'qty' => $totalQty,
            'subtotal_eur' => $totalEur - $serviceFee,
            'service_fee_eur' => $serviceFee,
            'total_eur' => $totalEur,
            'total_rsd' => $totalRsd,
            'duration_label' => $availabilityService->getDurationLabel($duration),
        ];
    }
}

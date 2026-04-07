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
        $eurRsdRate = (float) Setting::getValue('eur_rsd_rate', 117);
        $totalRsd = round($total * $eurRsdRate, 2);

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
}

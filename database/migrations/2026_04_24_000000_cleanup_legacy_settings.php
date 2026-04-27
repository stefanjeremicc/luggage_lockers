<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy keys — phone/whatsapp/fee/JSON are derived or code-authored now.
        Setting::whereIn('key', [
            'company_phone_link',
            'company_whatsapp',
            'company_whatsapp_link',
            'service_fee_eur',
            'how_it_works_steps',
        ])->delete();

        // If the phone exists in old national format, leave it; admin can re-enter.
    }

    public function down(): void
    {
        // No-op: legacy keys are deprecated.
    }
};

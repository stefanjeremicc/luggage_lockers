<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\NotificationTemplate;
use App\Services\Notification\BookingNotifier;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\SettingsSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Guards the one rule that keeps the (dark) emails readable everywhere:
 *
 *   light text must sit on a background declared INLINE on one of its own
 *   ancestors — never on a CSS class in the <head><style> block.
 *
 * Gmail mobile web, the Gmail app on a non-Google account and various webmail
 * gateways drop <style> but keep inline styles. When the backgrounds lived in
 * <style> and the text colours inline, those clients rendered white-on-white
 * and swallowed the locker number, the customer name and the total — which is
 * exactly what customers reported ("the dark one is fine, the light one is
 * broken"). These tests render every template with <style> removed and fail if
 * anything becomes unreadable.
 */
class EmailTemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,string> key.locale => rendered HTML */
    private function renderAll(): array
    {
        (new SettingsSeeder)->run();
        (new NotificationTemplateSeeder)->run();

        DB::table('locations')->insert([
            'id' => 1, 'name' => 'Belgrade Central', 'slug' => 'central',
            'address' => 'Nemanjina 12', 'city' => 'Belgrade', 'lat' => 44.8, 'lng' => 20.45,
            'google_maps_url' => 'https://maps.google.com/?cid=123',
        ]);
        DB::table('customers')->insert([
            'id' => 1, 'uuid' => (string) Str::uuid(), 'full_name' => 'Marko Petrović',
            'email' => 'marko@example.com', 'phone' => '+381601234567', 'locale' => 'en',
        ]);
        foreach ([[1, 'B-03', 'standard'], [2, 'R-12', 'large']] as [$id, $number, $size]) {
            DB::table('lockers')->insert([
                'id' => $id, 'location_id' => 1, 'ttlock_lock_id' => 500 + $id,
                'uuid' => (string) Str::uuid(), 'number' => $number, 'size' => $size, 'status' => 'available',
            ]);
        }
        DB::table('bookings')->insert([
            'id' => 1, 'uuid' => (string) Str::uuid(), 'customer_id' => 1, 'location_id' => 1,
            'locker_size' => 'standard', 'locker_qty' => 2,
            'check_in' => now()->addDay(), 'check_out' => now()->addDays(2),
            'duration_label' => '24h', 'price_eur' => 12, 'total_eur' => 12,
            'booking_status' => 'confirmed', 'cancelled_at' => now(), 'cancel_reason' => 'Change of plans',
        ]);
        foreach ([[1, 'standard', '24h'], [2, 'large', '2_days']] as [$id, $size, $duration]) {
            DB::table('booking_items')->insert([
                'id' => $id, 'booking_id' => 1, 'locker_size' => $size, 'qty' => 1,
                'duration_key' => $duration, 'check_in' => now()->addDay(), 'check_out' => now()->addDays(2),
                'unit_price_eur' => 6, 'line_total_eur' => 6,
            ]);
        }
        foreach ([[1, 1, 1, '9802'], [2, 2, 2, '4417']] as [$id, $lockerId, $itemId, $pin]) {
            DB::table('booking_lockers')->insert([
                'id' => $id, 'booking_id' => 1, 'locker_id' => $lockerId, 'booking_item_id' => $itemId,
                'pin_code_encrypted' => Crypt::encryptString($pin), 'assigned_at' => now(),
            ]);
        }

        $booking = Booking::with(['customer', 'location', 'lockers', 'bookingLockers.locker', 'items'])->find(1);

        $call = function (string $method, array $args) {
            $m = new ReflectionMethod(BookingNotifier::class, $method);
            $m->setAccessible(true);
            return $m->invokeArgs(null, $args);
        };

        $out = [];
        foreach (['en', 'sr'] as $locale) {
            $vars = $call('buildVars', [$booking, $locale])
                + $call('pinVars', [$booking, $locale])
                + [
                    'customer_email' => 'marko@example.com',
                    'customer_phone' => '+381601234567',
                    'booking_id' => 1,
                    'admin_lockers_block' => $call('adminLockersBlock', [$booking, $locale]),
                    'initiated_by_label' => 'customer (self-service link)',
                    'cancelled_at' => 'Sep 19, 2026 at 14:05',
                    'cancel_reason' => 'Change of plans',
                    'removed_lockers' => 'R-12',
                    'removed_lockers_block' => $call('removedLockersBlock', [['R-12'], $locale]),
                ];

            foreach (NotificationTemplate::where('channel', 'email')->where('locale', $locale)->get() as $tpl) {
                $rendered = NotificationTemplate::render($tpl->key, $locale, 'email', $vars);
                $out[$tpl->key.'.'.$locale] = $rendered['body'];
            }
        }

        return $out;
    }

    /** Relative luminance (WCAG) of an inline colour literal, or null if unparseable. */
    private function luminance(string $value): ?float
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-f]{3})$/i', $value, $m)) {
            $value = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        }
        if (preg_match('/^#([0-9a-f]{6})$/i', $value, $m)) {
            [$r, $g, $b] = sscanf($m[1], '%2x%2x%2x');
        } elseif (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $value, $m)) {
            [, $r, $g, $b] = array_map('intval', $m);
        } else {
            return null;
        }

        $channel = static function ($c) {
            $c /= 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
    }

    private function inlineProp(DOMElement $el, string $prop): ?string
    {
        $style = $el->getAttribute('style');
        if ($style && preg_match('/(?:^|;)\s*'.preg_quote($prop, '/').'\s*:\s*([^;]+)/i', $style, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /** The nearest ancestor background that is actually declared inline (or via bgcolor). */
    private function inheritedBackground(DOMElement $el): ?string
    {
        for ($node = $el; $node instanceof DOMElement; $node = $node->parentNode) {
            foreach (['background-color', 'background'] as $prop) {
                $value = $this->inlineProp($node, $prop);
                if ($value !== null && $this->luminance($value) !== null) {
                    return $value;
                }
            }
            if ($node->hasAttribute('bgcolor')) {
                return $node->getAttribute('bgcolor');
            }
        }

        return null;
    }

    public function test_no_light_text_is_left_without_an_inline_dark_background(): void
    {
        $failures = [];

        foreach ($this->renderAll() as $name => $html) {
            // Simulate a client that keeps inline styles but drops <head><style>.
            $stripped = preg_replace('#<style>.*?</style>#si', '', $html);

            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="UTF-8">'.$stripped);
            libxml_clear_errors();

            foreach ((new DOMXPath($doc))->query('//*[@style]') as $el) {
                /** @var DOMElement $el */
                $color = $this->inlineProp($el, 'color');
                if ($color === null) {
                    continue;
                }
                $textLum = $this->luminance($color);
                if ($textLum === null || $textLum < 0.5) {
                    continue; // not light text — can't disappear on a white fallback
                }
                if (trim($el->textContent) === '') {
                    continue;
                }

                $bg = $this->inheritedBackground($el);
                $bgLum = $bg === null ? null : $this->luminance($bg);

                if ($bgLum === null || $bgLum > 0.2) {
                    $failures[] = sprintf(
                        '%s: light text "%s" (%s) has no inline dark background (found: %s)',
                        $name,
                        Str::limit(trim(preg_replace('/\s+/u', ' ', $el->textContent)), 40),
                        $color,
                        $bg ?? 'none — falls back to the client surface'
                    );
                }
            }
        }

        $this->assertSame([], $failures, "Light text without an inline dark background:\n".implode("\n", $failures));
    }

    public function test_templates_do_not_depend_on_style_block_classes(): void
    {
        foreach ($this->renderAll() as $name => $html) {
            foreach (['class="info"', 'class="btn"', 'class="highlight"', 'class="card"'] as $stale) {
                $this->assertStringNotContainsString(
                    $stale,
                    $html,
                    "$name still uses $stale — those classes only exist in the <style> block clients may drop."
                );
            }
        }
    }

    public function test_every_template_has_the_bulletproof_dark_shell(): void
    {
        foreach ($this->renderAll() as $name => $html) {
            $this->assertStringContainsString('bgcolor="#0A0A0A"', $html, "$name is missing the page background");
            $this->assertStringContainsString('bgcolor="#1A1A1A"', $html, "$name is missing the card background");
        }
    }

    public function test_no_placeholder_is_left_unresolved(): void
    {
        foreach ($this->renderAll() as $name => $html) {
            $this->assertDoesNotMatchRegularExpression(
                '/\{\{\s*[a-z_]+\s*\}\}/i',
                $html,
                "$name contains an unresolved template placeholder"
            );
        }
    }

    public function test_confirmation_email_shows_every_locker_number_and_pin(): void
    {
        $html = $this->renderAll()['booking_confirmed.en'];
        $stripped = preg_replace('#<style>.*?</style>#si', '', $html);

        foreach (['B-03', 'R-12', '9802#', '4417#'] as $needle) {
            $this->assertStringContainsString($needle, $stripped, "confirmation email lost $needle");
        }
    }
}

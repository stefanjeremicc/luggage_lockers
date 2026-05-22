<?php

namespace Database\Seeders;

use App\Helpers\SiteSettings;
use App\Models\Location;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Turns the config/seo.php near-POI definitions into editable admin landing
 * pages (type=landing) in EN + SR. Once seeded they appear under
 * "Pages & SEO → SEO landing pages" and the public /near/{slug} (and
 * /sr/blizu/{slug}) routes render the rich landing template from these rows.
 *
 * Uses firstOrCreate so it NEVER overwrites pages an admin has since edited —
 * safe to re-run.
 */
class NearLandingSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::query()->where('is_active', true)->orderBy('sort_order')->first()
            ?? Location::query()->orderBy('sort_order')->first();
        $siteName = SiteSettings::siteName();

        foreach (config('seo.near_pois', []) as $slug => $poi) {
            foreach (['en', 'sr'] as $loc) {
                $isSr  = $loc === 'sr';
                $name  = $isSr ? ($poi['name_sr'] ?? $poi['name']) : $poi['name'];
                $desc  = $isSr ? ($poi['description_sr'] ?? $poi['description']) : ($poi['description'] ?? '');
                $title = $isSr ? "Čuvanje prtljaga blizu {$name}" : "Luggage storage near {$name}";

                Page::firstOrCreate(
                    ['slug' => $slug, 'locale' => $loc, 'type' => 'landing'],
                    [
                        'title'            => $title,
                        'content'          => $desc ? "<p>{$desc}</p>" : null,
                        'sections'         => [
                            'hero' => ['subtitle' => $desc],
                            'geo'  => ['lat' => $poi['lat'] ?? null, 'lng' => $poi['lng'] ?? null],
                            'poi'  => $name,
                        ],
                        'location_id'      => $location?->id,
                        'meta_title'       => $title.' — '.$siteName,
                        'meta_description' => $desc,
                        'is_published'     => true,
                        'published_at'     => now(),
                    ]
                );
            }
        }
    }
}

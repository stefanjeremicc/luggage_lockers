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

        // Generic "luggage storage near me / near you" landing pages — high-volume
        // queries with no specific POI (no geo → generic FAQ in the template).
        $generic = [
            'me' => [
                'title_en' => 'Luggage storage near me — Belgrade, 24/7',
                'title_sr' => 'Čuvanje prtljaga blizu mene — Beograd, 24/7',
                'desc_en'  => 'Looking for luggage storage near you in Belgrade? Drop your bags in our secure, self-service 24/7 smart lockers in the city centre — book online in 60 seconds, pay cash on arrival, and explore Belgrade hands-free.',
                'desc_sr'  => 'Tražiš čuvanje prtljaga u blizini u Beogradu? Ostavi torbe u sigurnim, samouslužnim pametnim ormarićima 24/7 u centru grada — rezerviši online za 60 sekundi, plati keš pri dolasku i istraži Beograd bez prtljaga.',
            ],
            'you' => [
                'title_en' => 'Luggage storage near you in Belgrade',
                'title_sr' => 'Čuvanje prtljaga blizu vas u Beogradu',
                'desc_en'  => 'Wherever you are in Belgrade, secure luggage storage is close by. Use our 24/7 smart lockers in the centre — reserve online in under a minute and free up your hands for the day.',
                'desc_sr'  => 'Gde god da se nalaziš u Beogradu, sigurno čuvanje prtljaga je blizu. Koristi naše pametne ormariće 24/7 u centru — rezerviši online za manje od minuta i oslobodi ruke za ceo dan.',
            ],
        ];
        foreach ($generic as $slug => $g) {
            foreach (['en', 'sr'] as $loc) {
                $isSr  = $loc === 'sr';
                $title = $isSr ? $g['title_sr'] : $g['title_en'];
                $desc  = $isSr ? $g['desc_sr'] : $g['desc_en'];
                Page::firstOrCreate(
                    ['slug' => $slug, 'locale' => $loc, 'type' => 'landing'],
                    [
                        'title'            => $title,
                        'content'          => "<p>{$desc}</p>",
                        'sections'         => ['hero' => ['subtitle' => $desc]],
                        'location_id'      => $location?->id,
                        'meta_title'       => $title.' — '.$siteName,
                        'meta_description' => $desc,
                        'is_published'     => true,
                        'published_at'     => now(),
                    ]
                );
            }
        }

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

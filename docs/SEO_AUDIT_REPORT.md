# SEO Audit Report — Belgrade Luggage Locker

**Datum:** 2026-05-22
**Stack:** Laravel 13 (Blade + Alpine.js public) · Vue 3 SPA admin · EN primarni / SR sekundarni
**Domen:** https://belgradeluggagelocker.com

## Executive Summary

- **Ukupno provereno:** 20 sekcija checkliste
- **High priority popravljeno u ovom prolazu:** 5 · **Medium:** 4 · **Low/note:** ostalo
- **Preostalo (zahteva tebe / van koda):** off-page (GBP, Search Console, citati), CSP, responsive `srcset`/`<picture>`, eksplicitni `width/height` na svim slikama, analytics/consent

Velika većina tehničkog on-page SEO-a je sada implementirana. Glavni preostali posao je **off-page (lokalni SEO)** i fino podešavanje performansi slika.

---

## High Priority — POPRAVLJENO

### [1] LocalBusiness schema bez AggregateRating / geo / offers
**Fajl:** `resources/views/public/partials/schema-markup.blade.php`
**Bilo:** samo osnovni LocalBusiness (name, address, hours, priceRange).
**Sad:** dodato `aggregateRating` (4.9 / 70, **samo na početnoj** gde su recenzije vidljive — izbegnuta Google kazna), `geo` (lat/lng), `areaServed: Belgrade`, `sameAs` (social), `image`, `logo`, `currenciesAccepted`, `paymentAccepted`, i `makesOffer` (Service/Offer za standardni i veliki ormarić sa cenama).
**Zašto:** AggregateRating donosi **zvezdice u SERP-u** (veliki CTR boost); Service/geo pomažu lokalno rangiranje.

### [2] Nema BreadcrumbList schema
**Fajl:** `resources/views/public/partials/breadcrumb-schema.blade.php` (novo), uključeno u `layouts/public.blade.php`
**Sad:** BreadcrumbList na lokacijama, blogu, near i statičkim stranicama; detalj-stranice postavljaju `@section('breadcrumb_name')`.
**Zašto:** breadcrumb u rezultatima + jasnija hijerarhija za crawler.

### [3] Blog postovi bez Article schema
**Fajl:** `resources/views/public/blog/show.blade.php`
**Sad:** `BlogPosting` JSON-LD sa `headline`, `description`, `image`, `datePublished`, `dateModified`, `author`, `publisher`, `inLanguage`.
**Zašto:** rich rezultati za članke, datumi u SERP-u.

### [4] Admin SPA indeksabilan
**Fajl:** `resources/views/admin/spa.blade.php:8`
**Sad:** `<meta name="robots" content="noindex, nofollow">` (uz već postojeći `Disallow: /admin` u robots.txt).
**Zašto:** admin nikad ne sme u indeks.

### [5] Hero slika bez alt + spor LCP
**Fajl:** `resources/views/public/home.blade.php`
**Sad:** keyword-bogat `alt`, `fetchpriority="high"`, `<link rel="preload" as="image">` u head; lazy/`decoding=async` na slikama ispod prevoja.
**Zašto:** image SEO + brži LCP (Core Web Vitals = ranking faktor).

---

## Medium Priority — POPRAVLJENO

### [6] Nema og:locale / WebSite schema
**Fajlovi:** `seo-meta.blade.php`, `schema-markup.blade.php`
**Sad:** `og:locale` (en_US/sr_RS) + `og:locale:alternate` + `og:site_name`; dodat `WebSite` JSON-LD (sa `inLanguage` EN+SR i `publisher → #business`).
**Napomena:** `SearchAction` (sitelinks searchbox) namerno **nije** dodat jer sajt nema on-site pretragu (bio bi nevalidan).

### [7] Sitemap nepotpun (SR blog, lastmod)
**Fajl:** `resources/views/public/partials/sitemap.blade.php`
**Sad:** dodati zasebni SR blog `<loc>` unosi + `<lastmod>` (iz `updated_at`) na sve dinamičke unose.

### [8] Near-me stranice tanke / SR na engleskom / siročići
**Fajlovi:** `config/seo.php`, `resources/views/public/pages/near.blade.php`, `partials/footer.blade.php`
**Sad:** prošireno sa 5 na **11** POI-jeva; **jedinstven opis EN + SR** za svaki; footer linkuje sve near stranice (interno linkovanje na celom sajtu).

### [9] Bez security headera
**Fajl:** `public/.htaccess`
**Sad:** HSTS (`max-age=31536000; includeSubDomains`), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`.
**Napomena:** **CSP nije dodat** — visok rizik da polomi inline skripte / mapu / fontove. Treba pažljivo testirati pa dodati ručno.

---

## Već dobro (zatečeno ispravno)

- Canonical na svakoj stranici (apsolutni `url()->current()`).
- Hreflang EN↔SR + x-default, gradi se iz imena rute (tačni prevedeni slugovi).
- robots.txt dinamičan, linkuje sitemap, blokira `/admin`,`/api`, privatne booking stranice.
- HTTPS redirect + www→non-www + trailing-slash redirect (`.htaccess`).
- Booking confirmation/cancel već `noindex,nofollow`.
- Custom error stranice 404/500/503 postoje.
- FAQPage schema na `/faq`.
- `<html lang>` dinamičan; Google fonts `display=swap` + preconnect.
- Čisti, lokalizovani URL-ovi; legacy Shopify 301 redirecti.
- Jedan `<h1>` po stranici; logo je `<img>` sa alt, ne h1.
- Favicon set (ico/png/apple-touch) + manifest + theme-color.

---

## Preostalo — zahteva ručni rad / van koda

### Off-page (najvažnije za #1–2 lokalno)
1. **Google Business Profile** — verifikacija, kategorija "Luggage storage facility", 24/7, fotke, identičan NAP.
2. **Google Search Console** + **Bing Webmaster** — dodati domen, poslati `sitemap.xml`, zatražiti indeksiranje.
3. **Citati** — TripAdvisor, Foursquare, lokalni travel sajtovi; partnerstva sa hostelima (backlinkovi).
4. **Analytics + cookie consent** — GA4 ili Plausible/Umami; consent banner za EU posetioce (GDPR).

### Tehnički (Low/Medium, opciono)
- **Responsive slike:** `<picture>` + `srcset`/`sizes` (min 3 veličine) i eksplicitni `width/height` na svim `<img>` (sada CLS drži `aspect-*` klasa). Veći refactor.
- **CSP** — definisati i testirati (mapa, fontovi, inline Alpine).
- **`.well-known/security.txt`** — opciono.

### Ručno testiranje (ne može iz koda)
- Lighthouse mobile (cilj 90+), PageSpeed Insights, securityheaders.com (sad bi trebalo A), Rich Results Test za svaki tip scheme, provera `curl https://belgradeluggagelocker.com/.env` (mora 403/404).

---

## Redosled implementacije (sledeći koraci)
1. **Ti:** Google Business Profile + Search Console + sitemap submit (najveći ROI).
2. **Ti/ja:** GA4 ili Plausible + consent banner.
3. **Ja (kad odlučiš):** responsive `<picture>`/`srcset` + `width/height` na slikama.
4. **Ja (oprezno):** CSP politika uz testiranje.
5. **Ti:** citati/backlinkovi (TripAdvisor, hosteli).

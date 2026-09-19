<?php

namespace App\Helpers;

/**
 * Single source of truth for the colours + building blocks of every outgoing
 * email (customer and admin).
 *
 * ── Why this exists ────────────────────────────────────────────────────────
 * The emails are a dark design: light text on near-black panels. The original
 * version put the TEXT colours inline (`style="color:#fff"`) but the BACKGROUND
 * colours in a `<head><style>` block (`.card`, `.highlight`, `.info`).
 *
 * That asymmetry is fatal, because a number of real clients drop `<style>`
 * entirely while keeping inline styles:
 *   • Gmail mobile web (mail.google.com in a phone browser)
 *   • the Gmail app when the account is not a Google account (GANGA)
 *   • Gmail's "clipped message" view
 *   • several corporate/webmail gateways that sanitise <head>
 *
 * When that happens the dark backgrounds vanish, the client falls back to its
 * own white surface, and the inline `color:#fff` text — the locker number, the
 * customer name, the total — becomes WHITE ON WHITE, i.e. invisible. Customers
 * reported exactly this: an email with a blank gap where their locker number
 * should be. It only showed up for people whose mail client renders light,
 * which is why "the black one is fine, the white one is broken".
 *
 * ── The rule this class enforces ───────────────────────────────────────────
 * A background and the text sitting on it must travel together on the SAME
 * element, as an inline style (plus a `bgcolor` attribute on table cells for
 * Outlook). Nothing may depend on the `<style>` block to stay legible. The
 * `<style>` block is kept, but only for things that CANNOT be inlined
 * (media queries, the Apple/Gmail auto-link-detector overrides) and never as
 * the only source of a colour.
 *
 * The result renders identically whether or not the client honours <style>.
 */
final class EmailTheme
{
    // ─── Palette ──────────────────────────────────────────────────────────
    public const PAGE_BG = '#0A0A0A';   // area around the card
    public const CARD_BG = '#1A1A1A';   // the email card itself
    public const PANEL_BG = '#111111';  // inset panel (access codes, etc.)
    public const BORDER = '#2A2A2A';
    public const TEXT = '#FFFFFF';
    public const MUTED = '#A0A0A0';
    public const FAINT = '#6B7280';
    public const ACCENT = '#F59E0B';
    public const SUCCESS = '#10B981';
    public const DANGER = '#EF4444';

    public const FONT = 'Arial,Helvetica,sans-serif';

    /** Body-copy style — replaces the old `.info` class. */
    public const INFO = 'font-family:'.self::FONT.';color:'.self::MUTED.';font-size:14px;line-height:1.6';

    /** Emphasised inline text on the card background (old `<strong style="color:#fff">`). */
    public const STRONG = 'color:'.self::TEXT.';font-weight:bold';

    /** Section-heading style — replaces the bare `<h2>` that relied on the style block. */
    public const H2 = 'margin:20px 0 8px;font-family:'.self::FONT.';color:'.self::ACCENT.';font-size:16px;line-height:1.4';

    /**
     * Full HTML document wrapper.
     *
     * Table-based on purpose: Gmail strips `<body>` styling outright (it
     * rewrites <body> into a <div>), so the page background has to live on a
     * 100%-wide table carrying both `bgcolor` and an inline background-color.
     * The card's background sits inline on the <td> that also sets the default
     * text colour, so every `color:#FFFFFF` nested inside is guaranteed to be
     * on a dark surface.
     */
    public static function shell(string $title, string $titleColor, string $inner): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            // Stop Gmail / iOS Mail turning phone numbers, dates and addresses
            // into their own blue auto-detected links.
            .'<meta name="format-detection" content="telephone=no, address=no, email=no, date=no">'
            .'<meta name="x-apple-disable-message-reformatting">'
            // Declare that this design supplies its own dark colours, so clients
            // that would otherwise force-invert it leave it alone.
            .'<meta name="color-scheme" content="dark">'
            .'<meta name="supported-color-schemes" content="dark">'
            .'<style>'
            .':root{color-scheme:dark;supported-color-schemes:dark}'
            .'body{margin:0!important;padding:0!important;background-color:'.self::PAGE_BG.'}'
            // Override the blue auto-detected links on Apple Mail / iOS.
            .'a[x-apple-data-detectors]{color:inherit!important;text-decoration:none!important;font-size:inherit!important;font-family:inherit!important;font-weight:inherit!important;line-height:inherit!important}'
            // Gmail / Outlook auto-link dates, times, addresses and "24/7" and
            // recolour them blue. Every descendant the client may inject
            // (auto-detector <a>, <span>, <u>) is forced back to white.
            .'.no-link,.no-link *,.no-link a,.no-link a *,.no-link span,.no-link u{color:'.self::TEXT.'!important;text-decoration:none!important;pointer-events:none}'
            // Safety net only — every anchor also carries an inline colour.
            .'a{color:'.self::ACCENT.';text-decoration:none}'
            .'@media only screen and (max-width:620px){.ll-pad{padding:18px!important}}'
            .'</style></head>'
            .'<body style="margin:0;padding:0;background-color:'.self::PAGE_BG.';">'
            .'<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="'.self::PAGE_BG.'"'
            .' style="width:100%;background-color:'.self::PAGE_BG.';border-collapse:collapse;margin:0;padding:0">'
            .'<tr><td align="center" bgcolor="'.self::PAGE_BG.'" style="background-color:'.self::PAGE_BG.';padding:20px;font-family:'.self::FONT.'">'
            // Outlook (Word engine) ignores max-width; this ghost table pins the
            // card to 600px there and is invisible to every other client.
            .'<!--[if mso]><table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600"><tr><td><![endif]-->'
            .'<table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="'.self::CARD_BG.'"'
            .' style="width:100%;max-width:600px;background-color:'.self::CARD_BG.';border:1px solid '.self::BORDER.';border-radius:12px;border-collapse:separate">'
            .'<tr><td class="ll-pad" bgcolor="'.self::CARD_BG.'"'
            .' style="background-color:'.self::CARD_BG.';padding:24px;border-radius:12px;font-family:'.self::FONT.';color:'.self::TEXT.';font-size:14px;line-height:1.6">'
            .'<h1 style="margin:0 0 16px;font-family:'.self::FONT.';color:'.$titleColor.';font-size:24px;line-height:1.3;text-align:center">'.$title.'</h1>'
            .$inner
            .'</td></tr></table>'
            .'<!--[if mso]></td></tr></table><![endif]-->'
            .'</td></tr></table></body></html>';
    }

    /**
     * Inset panel — replaces the old `.highlight` class. Used for access-code
     * cards and removed-locker cards, i.e. the most important content in the
     * whole email, so its background is doubly pinned (bgcolor + inline).
     */
    public static function panel(string $inner): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="'.self::PANEL_BG.'"'
            .' style="width:100%;background-color:'.self::PANEL_BG.';border:1px solid '.self::BORDER.';border-radius:8px;border-collapse:separate;margin:16px 0">'
            .'<tr><td align="center" bgcolor="'.self::PANEL_BG.'"'
            .' style="background-color:'.self::PANEL_BG.';padding:16px;border-radius:8px;text-align:center;font-family:'.self::FONT.'">'
            .$inner
            .'</td></tr></table>';
    }

    /**
     * Bulletproof call-to-action button — replaces the old `.btn` class.
     * Without the style block the old version degraded into a default blue
     * underlined link; here the amber fill is a `bgcolor` cell and the label
     * colour is inline on the anchor.
     */
    public static function button(string $href, string $label): string
    {
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;border-collapse:separate">'
            .'<tr><td align="center" bgcolor="'.self::ACCENT.'" style="background-color:'.self::ACCENT.';border-radius:8px">'
            .'<a href="'.$href.'" style="display:inline-block;padding:12px 24px;font-family:'.self::FONT.';font-size:14px;font-weight:bold;color:#000000;text-decoration:none;border-radius:8px">'.$label.'</a>'
            .'</td></tr></table>';
    }
}

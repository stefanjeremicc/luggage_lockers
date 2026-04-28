<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;background:#0A0A0A;color:#fff;padding:20px}.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}.info{color:#A0A0A0;font-size:14px;line-height:1.6}h1{color:#F59E0B;text-align:center}</style></head>
<body><div class="card">
<h1>Your Locker Time Ends Soon</h1>
<div class="info" style="text-align:center">
@php
    $tz = config('app.display_timezone');
    $items = $booking->items;
    $sameWindow = $items->isNotEmpty() && $items->every(fn($it) => $it->check_out?->equalTo($items->first()->check_out));
@endphp
<p>Your booking at <strong style="color:#fff">{{ $booking->location->name }}</strong> ends at:</p>
@if($items->isEmpty() || $sameWindow)
    @php $end = $items->isEmpty() ? $booking->check_out : $items->first()->check_out; @endphp
    <p style="font-size:24px;color:#F59E0B;font-weight:bold">{{ $end->copy()->setTimezone($tz)->format('h:i A') }}</p>
    <p>Please collect your belongings before this time.</p>
@else
    <ul style="color:#A0A0A0;list-style:none;padding:0;margin:12px 0">
        @foreach($items as $item)
            <li style="margin:6px 0">
                {{ $item->qty }} × {{ ucfirst($item->locker_size->value) }} —
                <strong style="color:#F59E0B">{{ $item->check_out->copy()->setTimezone($tz)->format('d M, h:i A') }}</strong>
            </li>
        @endforeach
    </ul>
    <p>Please collect each item before its end time.</p>
@endif
<p style="margin-top:16px">Need more time? Contact us:<br><strong style="color:#fff">+381 65 332 2319</strong></p>
</div>
</div></body></html>

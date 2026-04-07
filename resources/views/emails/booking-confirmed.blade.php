<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;background:#0A0A0A;color:#fff;padding:20px}
.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}
.info{color:#A0A0A0;font-size:14px;line-height:1.6}
.btn{display:inline-block;background:#F59E0B;color:#000;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;margin-top:16px}
.highlight{background:#111;border:1px solid #2A2A2A;border-radius:8px;padding:16px;text-align:center;margin:16px 0}
h1{color:#10B981;text-align:center}h2{color:#F59E0B;font-size:16px;margin-top:20px}</style></head>
<body><div class="card">
<h1>Booking Confirmed!</h1>

<div class="highlight">
<p style="color:#F59E0B;font-size:14px;margin:0">You will receive your PIN code shortly via email or WhatsApp.</p>
</div>

<h2>Booking Details</h2>
<div class="info">
<p><strong>Location:</strong> {{ $booking->location->name }}</p>
<p><strong>Address:</strong> {{ $booking->location->address }}, Belgrade</p>
<p><strong>Check-in:</strong> {{ $booking->check_in->format('d M Y, h:i A') }}</p>
<p><strong>Check-out:</strong> {{ $booking->check_out->format('d M Y, h:i A') }}</p>
<p><strong>Lockers:</strong> {{ $booking->locker_qty }} x {{ ucfirst($booking->locker_size->value) }}</p>
<p><strong>Total:</strong> &euro;{{ number_format($booking->total_eur, 2) }} — Pay cash on arrival</p>
</div>

<h2>How to Use</h2>
<div class="info">
<ol>
<li>Go to {{ $booking->location->name }} ({{ $booking->location->address }})</li>
<li>You will receive your PIN code before your check-in time</li>
<li>Enter your PIN on the locker keypad</li>
<li>Store your luggage and close the door</li>
<li>Use the same PIN to open when you return</li>
</ol>
</div>

@if($booking->location->google_maps_url)
<div style="text-align:center"><a href="{{ $booking->location->google_maps_url }}" class="btn">Get Directions</a></div>
@endif

<div style="margin-top:24px;padding-top:16px;border-top:1px solid #2A2A2A;text-align:center">
<p class="info">Need to cancel? <a href="{{ url("/booking/{$booking->uuid}/cancel") }}" style="color:#F59E0B">Click here</a></p>
<p class="info">Questions? Contact us: +381 65 332 2319</p>
</div>
</div></body></html>

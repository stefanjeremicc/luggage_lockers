<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>body{font-family:Arial,sans-serif;background:#0A0A0A;color:#fff;padding:20px}.card{background:#1A1A1A;border:1px solid #2A2A2A;border-radius:12px;padding:24px;max-width:600px;margin:0 auto}.info{color:#A0A0A0;font-size:14px;line-height:1.6}h1{color:#A0A0A0;text-align:center}</style></head>
<body><div class="card">
<h1>Booking Cancelled</h1>
<div class="info" style="text-align:center">
<p>Your booking at <strong style="color:#fff">{{ $booking->location->name }}</strong> has been cancelled.</p>
<p>No charges have been made.</p>
<p style="margin-top:16px">Want to book again?<br><a href="{{ url('/') }}" style="color:#F59E0B">Visit our website</a></p>
</div>
</div></body></html>

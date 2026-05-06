@php $isSr = str_starts_with(ltrim(request()->path(), '/'), 'sr'); @endphp
@extends('errors.layout', ['code' => 503])
@section('content')
    <p class="code">503</p>
    <h1 class="title">{{ $isSr ? 'Privremeno nedostupno' : 'Temporarily unavailable' }}</h1>
    <p class="desc">
        {{ $isSr
            ? 'Sajt je trenutno u održavanju. Vraćamo se uskoro.'
            : 'The site is currently down for maintenance. We will be back shortly.' }}
    </p>
@endsection

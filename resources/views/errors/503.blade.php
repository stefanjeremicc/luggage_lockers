@extends('errors.layout', ['code' => 503])
@section('content')
    <p class="code">503</p>
    <h1 class="title">{{ app()->getLocale() === 'sr' ? 'Privremeno nedostupno' : 'Temporarily unavailable' }}</h1>
    <p class="desc">
        {{ app()->getLocale() === 'sr'
            ? 'Sajt je trenutno u održavanju. Vraćamo se uskoro.'
            : 'The site is currently down for maintenance. We will be back shortly.' }}
    </p>
@endsection

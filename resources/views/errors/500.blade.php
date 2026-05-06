@extends('errors.layout', ['code' => 500])
@section('content')
    <p class="code">500</p>
    <h1 class="title">{{ app()->getLocale() === 'sr' ? 'Greška na serveru' : 'Something went wrong' }}</h1>
    <p class="desc">
        {{ app()->getLocale() === 'sr'
            ? 'Došlo je do neočekivane greške. Naš tim je obavešten — pokušaj ponovo za par minuta.'
            : 'An unexpected error occurred. Our team has been notified — please try again in a few minutes.' }}
    </p>
    <a class="btn" href="{{ url(app()->getLocale() === 'sr' ? '/sr' : '/') }}">
        {{ app()->getLocale() === 'sr' ? 'Nazad na početnu' : 'Back to homepage' }}
    </a>
@endsection

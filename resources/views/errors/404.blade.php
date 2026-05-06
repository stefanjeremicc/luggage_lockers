@php $isSr = str_starts_with(ltrim(request()->path(), '/'), 'sr'); @endphp
@extends('errors.layout', ['code' => 404])
@section('content')
    <p class="code">404</p>
    <h1 class="title">{{ $isSr ? 'Stranica nije pronađena' : 'Page not found' }}</h1>
    <p class="desc">
        {{ $isSr
            ? 'Stranica koju tražiš ne postoji ili je premeštena. Vrati se na početnu i nastavi.'
            : 'The page you are looking for does not exist or has been moved. Head back to the homepage to continue.' }}
    </p>
    <a class="btn" href="{{ url($isSr ? '/sr' : '/') }}">
        {{ $isSr ? 'Nazad na početnu' : 'Back to homepage' }}
    </a>
@endsection

<!doctype html>
<html lang="it" data-bs-theme="{{ ($page ?? 'menu') === 'menu' ? 'dark' : 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dal Biondo</title>
        @if (($page ?? 'menu') === 'menu')
        <link rel="icon" href="{{ asset('dal-biondo/slice.svg') }}" type="image/svg+xml">
        <link rel="stylesheet" href="{{ asset('dal-biondo/fonts/josefin-sans.css') }}">
        <link rel="stylesheet" href="{{ asset('dal-biondo/fonts/poppins.css') }}">
        @endif
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app" data-page="{{ $page ?? 'menu' }}"></div>
</body>
</html>
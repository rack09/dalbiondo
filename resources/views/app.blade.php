<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dal Biondo</title>
    @vite('resources/js/app.js')
</head>
<body>
    <div id="app" data-page="{{ $page ?? 'menu' }}"></div>
</body>
</html>
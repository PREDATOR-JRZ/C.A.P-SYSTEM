<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'CICS Admissions') }}</title>
</head>
<body>
    <script>window.location.href = @json(url('/login'));</script>
    <noscript><a href="{{ url('/login') }}">Continue to login</a></noscript>
</body>
</html>

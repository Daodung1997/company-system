<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('passwords.subject', [], $locale) }}</title>
</head>
<body>
    <p>{{ __('passwords.greeting', ['name' => $name], $locale) }}</p>
    <p>{{ __('passwords.message', [], $locale) }}</p>
    <p>{{ __('passwords.otp', ['otp' => $otp], $locale) }}</p>
    <p>{{ __('passwords.note', [], $locale) }}</p>
    <p>{{ __('passwords.footer', [], $locale) }}</p>
</body>
</html>

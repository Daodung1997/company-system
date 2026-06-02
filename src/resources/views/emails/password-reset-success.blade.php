<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('passwords.subject_success', [], $locale) }}</title>
</head>
<body>
    <p>{{ __('passwords.greeting', ['name' => $full_name], $locale) }}</p>
    <p>{{ __('passwords.message_success', [], $locale) }}</p>
    <p>{{ __('passwords.footer', [], $locale) }}</p>
</body>
</html>

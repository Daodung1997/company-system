<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('register.subject', [], $locale) }}</title>
</head>
<body>
    <h2>{{ __('register.subject', [], $locale) }}</h2>
    <p>{{ __('register.greeting', ['name' => $full_name], $locale) }}</p>
    <p>{{ __('register.message', [], $locale) }}</p>
    <p>{{ __('register.email_label', [], $locale) }} {{ $email }}</p>
    <p>{{ __('register.password_label', [], $locale) }} {{ $password }}</p>
    <p>{{ __('register.note', [], $locale) }}</p>
    <p>{!! __('register.footer', ['app_name' => config('app.name')], $locale) !!}</p>
</body>
</html>

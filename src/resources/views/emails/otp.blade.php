@component('mail::message')
# Mã xác thực OTP

Mã xác thực của bạn là:

@component('mail::panel')
<h1 style="text-align: center; font-size: 32px; letter-spacing: 8px;">{{ $code }}</h1>
@endcomponent

Mã này có hiệu lực trong **{{ $expiryMinutes }} phút**.

@if($type === 'register')
Sử dụng mã này để hoàn tất đăng ký tài khoản ViecVat.
@elseif($type === 'forgot_password')
Sử dụng mã này để đặt lại mật khẩu của bạn.
@else
Sử dụng mã này để xác thực Email của bạn.
@endif

**Lưu ý:** Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.

Thanks,<br>
{{ config('app.name') }}
@endcomponent

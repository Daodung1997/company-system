<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Xác thực Email' }} - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
        }
        .icon.success { background: #d4edda; color: #28a745; }
        .icon.error { background: #f8d7da; color: #dc3545; }
        h1 {
            font-size: 24px;
            color: #1a1a2e;
            margin-bottom: 12px;
            font-weight: 700;
        }
        p {
            font-size: 16px;
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        .footer {
            margin-top: 32px;
            font-size: 13px;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <div class="icon success">✓</div>
            <h1>Xác thực thành công!</h1>
            <p>Email của bạn đã được xác thực. Bạn có thể đăng nhập vào ứng dụng ngay bây giờ.</p>
        @else
            <div class="icon error">✕</div>
            <h1>{{ $title ?? 'Xác thực thất bại' }}</h1>
            <p>{{ $message ?? 'Đã có lỗi xảy ra. Vui lòng thử lại.' }}</p>
        @endif
        <div class="footer">© {{ date('Y') }} {{ config('app.name') }}</div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bảng lương tháng {{ $yearMonth }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            font-size: 16px;
            margin: 0;
            color: #1e3a8a;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 10px;
            color: #666666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 3px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        td {
            padding: 5px 3px;
            border: 1px solid #cbd5e1;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .currency {
            white-space: nowrap;
        }
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .footer td {
            border: none;
            font-size: 9px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>BẢNG LƯƠNG NHÂN VIÊN THÁNG {{ $yearMonth }}</h2>
        <p>Hệ thống Quản lý và Tính lương Tự động</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">STT</th>
                <th width="6%">Mã NV</th>
                <th width="12%">Họ và tên</th>
                <th width="8%">Lương cơ bản</th>
                <th width="6%">Công (Thực/TC)</th>
                <th width="5%">OT Thường</th>
                <th width="5%">OT C.Tuần</th>
                <th width="5%">OT Lễ</th>
                <th width="8%">Tiền tăng ca</th>
                <th width="8%">Chuyên cần</th>
                <th width="8%">Trừ Muộn/Phép</th>
                <th width="7%">Phí Công đoàn</th>
                <th width="7%">Thuế TNCN</th>
                <th width="8%">Đã trả trước</th>
                <th width="10%">Thực lĩnh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                @php
                    $deductionsLateLeave = ($item['deduction_late'] ?? 0) + ($item['deduction_leave'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item['employee_code'] ?? '-' }}</td>
                    <td class="font-bold">{{ $item['full_name'] ?? '-' }}</td>
                    <td class="text-right currency">{{ number_format($item['base_salary'] ?? 0) }}đ</td>
                    <td class="text-center">{{ $item['actual_working_days'] ?? 0 }}/{{ $item['standard_working_days'] ?? 0 }}</td>
                    <td class="text-center">{{ $item['overtime_hours_normal'] ?? 0 }}h</td>
                    <td class="text-center">{{ $item['overtime_hours_weekend'] ?? 0 }}h</td>
                    <td class="text-center">{{ $item['overtime_hours_holiday'] ?? 0 }}h</td>
                    <td class="text-right currency font-bold">{{ number_format($item['overtime_salary'] ?? 0) }}đ</td>
                    <td class="text-right currency">{{ number_format($item['allowance_attendance'] ?? 0) }}đ</td>
                    <td class="text-right currency text-rose-500">{{ number_format($deductionsLateLeave) }}đ</td>
                    <td class="text-right currency text-rose-500">{{ number_format($item['deduction_union'] ?? 0) }}đ</td>
                    <td class="text-right currency text-rose-500">{{ number_format($item['deduction_tax'] ?? 0) }}đ</td>
                    <td class="text-right currency">{{ number_format($item['advance_payment'] ?? 0) }}đ</td>
                    <td class="text-right currency font-bold">{{ number_format($item['net_salary'] ?? 0) }}đ</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td width="33%" class="text-center">
                <strong>Người lập biểu</strong><br><br><br><br>
                (Ký, ghi rõ họ tên)
            </td>
            <td width="33%"></td>
            <td width="33%" class="text-center">
                <strong>Người duyệt</strong><br><br><br><br>
                (Ký tên và đóng dấu)
            </td>
        </tr>
    </table>

</body>
</html>

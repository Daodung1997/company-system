<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hợp Đồng - {{ $contract->contract_code }}</title>
    <style>
        @page {
            margin: 2.2cm 2cm 2.2cm 2cm;
            @bottom-right {
                content: "Trang " counter(page) "/" counter(pages);
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 8.5pt;
                color: #6b7280;
            }
        }
        
        /* THEME CONTROLLER */
        @if(($theme ?? 'classic') === 'classic')
            /* CLASSIC TRADITIONAL LEGAL THEME */
            body {
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 10.5pt;
                line-height: 1.65;
                color: #000000;
            }
            .title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: bold;
                font-size: 16pt;
                text-transform: uppercase;
                margin-top: 15pt;
                margin-bottom: 3pt;
                color: #000000;
                letter-spacing: 0.5px;
            }
            .section-title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: bold;
                font-size: 11.5pt;
                text-transform: uppercase;
                margin-top: 18pt;
                margin-bottom: 8pt;
                color: #000000;
                border-bottom: 1.5pt solid #000000;
                padding-bottom: 2pt;
            }
            .compliance-box {
                border: 1.5pt solid #000000;
                padding: 10pt 12pt;
                margin: 14pt 0;
                font-size: 9.5pt;
                background-color: #ffffff;
            }
            .compliance-title {
                font-weight: bold;
                color: #000000;
                margin-bottom: 4pt;
                text-transform: uppercase;
                font-size: 9pt;
            }
            .divider-legal {
                width: 120pt;
                height: 1.2pt;
                background-color: #000000;
                margin: 4pt auto 10pt auto;
            }
            .table-data {
                width: 100%;
            }
            .table-data td {
                border-bottom: 0.5pt dashed #b5b5b5;
                padding: 4.5pt 0;
            }
            .table-data .label {
                color: #000000;
                font-weight: bold;
            }
        @elseif(($theme ?? 'classic') === 'modern')
            /* MODERN TECH CORPORATE THEME */
            body {
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 9.5pt;
                line-height: 1.55;
                color: #1f2937;
            }
            .title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: 900;
                font-size: 17pt;
                text-transform: uppercase;
                color: #059669; /* Emerald Green */
                margin-top: 15pt;
                margin-bottom: 3pt;
                letter-spacing: 0.5px;
            }
            .section-title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: bold;
                font-size: 11pt;
                text-transform: uppercase;
                border-left: 4pt solid #059669;
                padding-left: 8pt;
                margin-top: 20pt;
                margin-bottom: 10pt;
                color: #047857;
            }
            .compliance-box {
                background-color: #f0fdf4;
                border: 1px solid #d1fae5;
                padding: 10pt 14pt;
                border-radius: 8px;
                margin: 14pt 0;
                font-size: 9pt;
                color: #065f46;
            }
            .compliance-title {
                font-weight: bold;
                color: #065f46;
                margin-bottom: 4pt;
                text-transform: uppercase;
                font-size: 8.5pt;
            }
            .divider-legal {
                width: 140pt;
                height: 2.5pt;
                background-color: #34d399;
                margin: 4pt auto 12pt auto;
                border-radius: 2px;
            }
            .table-data {
                width: 100%;
                background-color: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 8pt 12pt;
            }
            .table-data td {
                border-bottom: 1px solid #f3f4f6;
                padding: 5pt 0;
            }
            .table-data .label {
                color: #4b5563;
                font-weight: bold;
            }
        @else
            /* ACADEMIC ELEGANT SLATE THEME */
            body {
                font-family: 'DejaVu Sans', sans-serif;
                font-size: 9.5pt;
                line-height: 1.7;
                color: #2d3748;
            }
            .title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: normal;
                font-size: 17pt;
                text-transform: uppercase;
                color: #1a365d; /* Royal Navy */
                margin-top: 15pt;
                margin-bottom: 3pt;
                letter-spacing: 1.2px;
            }
            .section-title {
                font-family: 'DejaVu Sans', sans-serif;
                font-weight: bold;
                font-size: 11pt;
                text-transform: uppercase;
                margin-top: 20pt;
                margin-bottom: 10pt;
                color: #1a365d;
                border-bottom: 1px solid #cbd5e0;
                padding-bottom: 3pt;
            }
            .compliance-box {
                background-color: #ebf8ff;
                border: 1px solid #bee3f8;
                padding: 10pt 14pt;
                border-radius: 4px;
                margin: 14pt 0;
                font-size: 9pt;
                color: #2b6cb0;
            }
            .compliance-title {
                font-weight: bold;
                color: #2b6cb0;
                margin-bottom: 4pt;
                text-transform: uppercase;
                font-size: 8.5pt;
            }
            .divider-legal {
                width: 100pt;
                height: 1px;
                background-color: #718096;
                margin: 4pt auto 12pt auto;
            }
            .table-data {
                width: 100%;
            }
            .table-data td {
                border-bottom: 1px solid #e2e8f0;
                padding: 4.5pt 0;
            }
            .table-data .label {
                color: #4a5568;
                font-weight: bold;
            }
        @endif

        /* COMMON STANDARD STYLES */
        .header {
            text-align: center;
            margin-bottom: 18pt;
        }
        .national-title {
            font-weight: bold;
            font-size: 10.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2pt;
        }
        .national-sub {
            font-weight: bold;
            font-size: 9.5pt;
            margin-bottom: 4pt;
        }
        .code {
            font-family: monospace;
            font-size: 9pt;
            color: #4b5563;
            margin-top: 4pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
        }
        td {
            padding: 3.5pt 0;
            vertical-align: top;
        }
        .clause-item {
            margin-bottom: 8pt;
            text-align: justify;
        }
        .clause-title-text {
            font-weight: bold;
            display: block;
            margin-bottom: 2pt;
        }
        .signature-container {
            margin-top: 25pt;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
        }
        .signature-role {
            font-weight: bold;
            margin-bottom: 55pt;
            text-transform: uppercase;
            font-size: 9.5pt;
        }
        .signature-name {
            font-weight: bold;
            font-size: 10pt;
        }
        .page-break {
            page-break-after: always;
        }
        .text-justify {
            text-align: justify;
        }
        .ol-legal {
            margin: 0;
            padding-left: 14pt;
        }
        .ol-legal li {
            margin-bottom: 3pt;
        }
    </style>
</head>
<body>

    <!-- PHẦN ĐẦU HỢP ĐỒNG (QUỐC HIỆU - TIÊU NGỮ CHUẨN) -->
    <div class="header">
        <div class="national-title">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
        <div class="national-sub">Độc lập - Tự do - Hạnh phúc</div>
        <div class="divider-legal"></div>
        
        @if($contract->type === 'LABOR')
            <div class="title">HỢP ĐỒNG LAO ĐỘNG</div>
        @elseif($contract->type === 'VENDOR')
            <div class="title">HỢP ĐỒNG THẦU PHỤ THƯƠNG MẠI</div>
        @else
            <div class="title">HỢP ĐỒNG CUNG CẤP DỊCH VỤ</div>
        @endif
        
        <div class="code">Số hiệu pháp lý: {{ $contract->contract_code }}</div>
    </div>

    <!-- CĂN CỨ PHÁP LÝ CHI TIẾT TỪNG LOẠI HỢP ĐỒNG -->
    <div class="text-justify" style="margin-bottom: 12pt; font-style: italic; font-size: 9pt; color: #4a5568; line-height: 1.5;">
        @if($contract->type === 'LABOR')
            - Căn cứ Bộ luật Lao động số 45/2019/QH14 được Quốc hội nước Cộng hòa xã hội chủ nghĩa Việt Nam thông qua ngày 20 tháng 11 năm 2019 và có hiệu lực thi hành từ ngày 01 tháng 01 năm 2021;<br>
            - Căn cứ Luật Bảo hiểm xã hội số 58/2014/QH13 và các văn bản, thông tư hướng dẫn thực hiện chế độ bảo hiểm bắt buộc của Chính phủ;<br>
            - Căn cứ vào nhu cầu tuyển dụng thực tế và ngân sách nhân sự của Người sử dụng lao động;<br>
        @elseif($contract->type === 'VENDOR')
            - Căn cứ Bộ luật Dân sự số 91/2015/QH13 được Quốc hội nước Cộng hòa xã hội chủ nghĩa Việt Nam thông qua ngày 24 tháng 11 năm 2015;<br>
            - Căn cứ Luật Thương mại số 36/2005/QH11 cùng các quy định của pháp luật Việt Nam về việc thuê thầu phụ và thi công thương mại;<br>
            - Căn cứ vào hồ sơ năng lực kỹ thuật của Bên B và phạm vi công trình yêu cầu từ Bên A;<br>
        @else
            - Căn cứ Bộ luật Dân sự số 91/2015/QH13 ban hành ngày 24 tháng 11 năm 2015 và các văn bản hướng dẫn thi hành;<br>
            - Căn cứ Luật Thương mại số 36/2005/QH11 quy định về hoạt động cung ứng dịch vụ thương mại trên thị trường Việt Nam;<br>
            - Căn cứ vào thỏa thuận về chất lượng dịch vụ (SLA) và biểu phí dịch vụ đã thống nhất giữa hai bên;<br>
        @endif
        - Trên cơ sở sự tự nguyện, thiện chí hợp tác và không trái quy định của pháp luật.
    </div>

    <p class="text-justify"><em>Hôm nay, ngày {{ \Carbon\Carbon::parse($contract->sign_date)->format('d') }} tháng {{ \Carbon\Carbon::parse($contract->sign_date)->format('m') }} năm {{ \Carbon\Carbon::parse($contract->sign_date)->format('Y') }}, tại văn phòng làm việc của hai bên, chúng tôi gồm có các thông tin chủ thể ký kết cụ thể dưới đây:</em></p>

    <!-- CHỦ THỂ BÊN A: ĐƠN VỊ ĐIỀU HÀNH HỆ THỐNG / DOANH NGHIỆP CHỦ QUẢN -->
    <div class="section-title">BÊN A: NGƯỜI SỬ DỤNG LAO ĐỘNG / ĐƠN VỊ GIAO THẦU / BÊN CUNG CẤP</div>
    <table class="table-data">
        <tr>
            <td class="label" style="width: 160pt;">Tên doanh nghiệp:</td>
            <td class="value"><strong>{{ $contract->company->name ?? 'Công ty Giải pháp Công nghệ Việt Nam' }}</strong></td>
        </tr>
        @if(isset($contract->company))
        <tr>
            <td class="label">Mã số thuế doanh nghiệp:</td>
            <td class="value" style="font-family: monospace; font-weight: bold;">{{ $contract->company->tax_code }}</td>
        </tr>
        <tr>
            <td class="label">Địa chỉ trụ sở chính:</td>
            <td class="value">{{ $contract->company->address_registered }}</td>
        </tr>
        <tr>
            <td class="label">Người đại diện pháp luật:</td>
            <td class="value">Ông/Bà <strong>{{ $contract->company->legal_representative }}</strong></td>
        </tr>
        @endif
    </table>

    <div style="margin-top: 10pt;"></div>

    <!-- CHỦ THỂ BÊN B: PHÂN LOẠI CỤ THỂ CHO TỪNG LOẠI HỢP ĐỒNG (KHÔNG TRÙNG LẶP) -->
    @if($contract->type === 'LABOR')
        <div class="section-title">BÊN B: NGƯỜI LAO ĐỘNG</div>
        <table class="table-data">
            @if($contract->employee)
            <tr>
                <td class="label" style="width: 160pt;">Họ và tên nhân sự:</td>
                <td class="value"><strong>{{ $contract->employee->full_name }}</strong> (Mã nhân sự: <span style="font-family: monospace; font-weight: bold;">{{ $contract->employee->code }}</span>)</td>
            </tr>
            <tr>
                <td class="label">Số điện thoại di động:</td>
                <td class="value" style="font-family: monospace;">{{ $contract->employee->phone }}</td>
            </tr>
            <tr>
                <td class="label">Địa chỉ thư điện tử:</td>
                <td class="value">{{ $contract->employee->email }}</td>
            </tr>
            <tr>
                <td class="label">Giấy tờ pháp lý CCCD số:</td>
                <td class="value" style="font-family: monospace; font-weight: bold;">{{ $contract->employee->identity_number }} <span style="font-weight: normal; font-size: 8.5pt; color: #4b5563;">(Loại: {{ $contract->employee->identity_type }})</span></td>
            </tr>
            @endif
            @if($contract->job_title)
            <tr>
                <td class="label">Chức danh / Vị trí phân công:</td>
                <td class="value">{{ $contract->job_title }}</td>
            </tr>
            @endif
            @if($contract->work_location)
            <tr>
                <td class="label">Văn phòng / Địa điểm làm việc:</td>
                <td class="value">{{ $contract->work_location }}</td>
            </tr>
            @endif
            @if($contract->bank_name)
            <tr>
                <td class="label">Thông tin tài khoản nhận lương:</td>
                <td class="value">{{ $contract->bank_name }} - Số TK: <span style="font-family: monospace; font-weight: bold;">{{ $contract->bank_account_number }}</span></td>
            </tr>
            @endif
        </table>
    @elseif($contract->type === 'VENDOR')
        <div class="section-title">BÊN B: ĐỐI TÁC THẦU PHỤ / ĐƠN VỊ THI CÔNG</div>
        <table class="table-data">
            <tr>
                <td class="label" style="width: 160pt;">Tên đơn vị thầu phụ:</td>
                <td class="value"><strong>{{ $contract->partner_name ?? 'Đơn vị đối tác phụ trách' }}</strong></td>
            </tr>
            @if($contract->partner_tax_code)
            <tr>
                <td class="label">Mã số thuế đối tác:</td>
                <td class="value" style="font-family: monospace; font-weight: bold;">{{ $contract->partner_tax_code }}</td>
            </tr>
            @endif
            @if($contract->partner_representative)
            <tr>
                <td class="label">Người đại diện ký kết:</td>
                <td class="value">Ông/Bà <strong>{{ $contract->partner_representative }}</strong> @if($contract->partner_representative_role) (Chức vụ: {{ $contract->partner_representative_role }}) @endif</td>
            </tr>
            @endif
            @if($contract->partner_address)
            <tr>
                <td class="label">Địa chỉ trụ sở đăng ký:</td>
                <td class="value">{{ $contract->partner_address }}</td>
            </tr>
            @endif
        </table>
    @else
        <div class="section-title">BÊN B: KHÁCH HÀNG DOANH NGHIỆP / ĐƠN VỊ THỤ HƯỞNG</div>
        <table class="table-data">
            <tr>
                <td class="label" style="width: 160pt;">Tên đơn vị khách hàng:</td>
                <td class="value"><strong>{{ $contract->partner_name ?? 'Khách hàng sử dụng dịch vụ' }}</strong></td>
            </tr>
            @if($contract->partner_tax_code)
            <tr>
                <td class="label">Mã số thuế / GPKD:</td>
                <td class="value" style="font-family: monospace; font-weight: bold;">{{ $contract->partner_tax_code }}</td>
            </tr>
            @endif
            @if($contract->partner_representative)
            <tr>
                <td class="label">Đại diện pháp luật bên mua:</td>
                <td class="value">Ông/Bà <strong>{{ $contract->partner_representative }}</strong> @if($contract->partner_representative_role) (Chức danh: {{ $contract->partner_representative_role }}) @endif</td>
            </tr>
            @endif
            @if($contract->partner_address)
            <tr>
                <td class="label">Địa điểm giao nhận hóa đơn:</td>
                <td class="value">{{ $contract->partner_address }}</td>
            </tr>
            @endif
        </table>
    @endif

    <!-- PAGE BREAK TO ENFORCE STANDARD TWO-PAGE LEGAL LAYOUT -->
    <div class="page-break"></div>

    <!-- ĐIỀU KHOẢN CHI TIẾT TỪNG LOẠI HỢP ĐỒNG (LABOR, VENDOR, CLIENT) -->
    @if($contract->type === 'LABOR')
        <!-- CHƯƠNG ĐIỀU KHOẢN HỢP ĐỒNG LAO ĐỘNG -->
        <div class="section-title">CHƯƠNG ĐIỀU KHOẢN CHI TIẾT CỦA HỢP ĐỒNG LAO ĐỘNG</div>
        
        <div class="clause-item">
            <span class="clause-title-text">Điều 1: Thời hạn hợp đồng, Thời giờ làm việc và Địa điểm</span>
            <div class="text-justify">
                1.1. Loại hợp đồng lao động: Hợp đồng lao động xác định thời hạn có hiệu lực từ ngày <strong>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</strong> 
                @if($contract->end_date) 
                    đến ngày <strong>{{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</strong>.
                @else 
                    (Hợp đồng vô thời hạn theo Bộ luật Lao động Việt Nam).
                @endif<br>
                1.2. Thời gian thử việc: <strong>{{ $contract->probation_period_months ?? 0 }} tháng</strong>. Trong thời gian thử việc, Người lao động được hưởng <strong>{{ $contract->probation_salary_percentage ?? 85 }}%</strong> mức lương chính thức.<br>
                1.3. Thời giờ làm việc tiêu chuẩn: <strong>{{ $contract->working_hours_per_day ?? 8.00 }} giờ/ngày</strong> (Không quá 48 giờ/tuần). Giờ vào làm việc: 08:30 sáng, giờ tan ca: 17:30 chiều. Nghỉ trưa từ 12:00 đến 13:00.<br>
                1.4. Địa điểm làm việc chuyên môn: {{ $contract->work_location ?? 'Văn phòng Công ty' }}.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 2: Chế độ tiền lương, Phụ cấp và Hình thức thanh toán</span>
            <div class="text-justify">
                2.1. Lương cơ bản: <strong>{{ number_format($contract->value ?? 0, 0, ',', '.') }} VNĐ / tháng</strong> (Bằng chữ: {{ trim(App\Supports\Facades\Response\Response::class) ? 'Mười lăm triệu đồng chẵn' : '' }}).<br>
                2.2. Phương thức thanh toán lương: Chuyển khoản ngân hàng (Bank Transfer) vào tài khoản đã đăng ký của Người lao động.<br>
                2.3. Kỳ hạn thanh toán lương: Vào ngày <strong>05</strong> của tháng kế tiếp. Nếu ngày 05 trùng vào ngày nghỉ cuối tuần hoặc ngày lễ quốc gia, Công ty sẽ thanh toán vào ngày làm việc trước đó.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 3: Nghĩa vụ đóng Bảo hiểm và Thuế Thu nhập cá nhân (TNCN)</span>
            <div class="text-justify">
                3.1. Bảo hiểm xã hội: Người lao động được tham gia đầy đủ bảo hiểm xã hội, bảo hiểm y tế và bảo hiểm thất nghiệp theo danh mục: <strong>{{ $contract->insurance_enrolled || 'BHXH, BHYT, BHTN bắt buộc' }}</strong>. Tỷ lệ đóng BHXH được trích từ lương của Người lao động và đóng góp từ quỹ phúc lợi của Người sử dụng lao động theo luật quy chuẩn.<br>
                3.2. Thuế TNCN: Người lao động tự chịu trách nhiệm đóng thuế TNCN. Công ty sẽ hỗ trợ khấu trừ tại nguồn trước khi thực hiện chi trả lương và cung cấp chứng từ khấu trừ thuế điện tử vào cuối năm tài chính.
            </div>
        </div>

        <!-- TUÂN THỦ OVERTIME QUỐC TẾ (NHẬT BẢN - VIỆT NAM) -->
        @if($contract->is_36_agreement_applicable || $contract->overtime_allowance_included)
            <div class="compliance-box">
                <div class="compliance-title">⚠️ ĐIỀU KHOẢN ĐẶC THÙ VỀ TUÂN THỦ LÀM THÊM GIỜ (STANDARDS COOPERATION)</div>
                @if($contract->is_36_agreement_applicable)
                    <p style="margin: 2px 0;">• <strong>Áp dụng Giới hạn làm thêm giờ (Bộ Luật Lao động Việt Nam):</strong> Hệ thống giám sát của Công ty sẽ tự động kiểm soát trần thời gian làm thêm giờ tối đa của nhân sự. Tổng số giờ làm thêm không vượt quá 40 giờ/tháng và tối đa 200 giờ/năm (hoặc tối đa 300 giờ/năm trong các trường hợp đặc biệt được cơ quan nhà nước chấp thuận), tuân thủ chặt chẽ các quy định tại Điều 107 Bộ luật Lao động 2019.</p>
                @endif
                @if($contract->overtime_allowance_included)
                    <p style="margin: 2px 0;">• <strong>Chế độ Lương làm thêm giờ khoán (Lump-sum Overtime):</strong> Lương cơ bản tại Điều 2 đã bao gồm phụ cấp làm thêm giờ gộp sẵn tương ứng với tối đa <strong>{{ $contract->included_overtime_hours ?? 0 }} giờ/tháng</strong>. Mọi giờ làm việc ngoài giờ thực tế vượt quá hạn mức trên sẽ được ghi nhận tự động bằng hệ thống Timesheet và chi trả bổ sung đầy đủ.</p>
                @endif
            </div>
        @endif

        <div class="clause-item">
            <span class="clause-title-text">Điều 4: Nghĩa vụ Bảo mật thông tin doanh nghiệp (NDA)</span>
            <div class="text-justify">
                4.1. Người lao động cam kết bảo mật tuyệt đối các thông tin kinh doanh, mã nguồn, dữ liệu tài chính và bí mật công nghệ của Bên A. Hành vi làm rò rỉ dữ liệu sẽ bị xử lý kỷ luật lao động ở mức cao nhất và phải bồi thường toàn bộ thiệt hại phát sinh trước pháp luật.
            </div>
        </div>

    @elseif($contract->type === 'VENDOR')
        <!-- CHƯƠNG ĐIỀU KHOẢN HỢP ĐỒNG THẦU PHỤ THƯƠNG MẠI -->
        <div class="section-title">CHƯƠNG ĐIỀU KHOẢN CHI TIẾT HỢP ĐỒNG THẦU PHỤ</div>
        
        <div class="clause-item">
            <span class="clause-title-text">Điều 1: Phạm vi công việc thầu phụ và Yêu cầu chất lượng</span>
            <div class="text-justify">
                1.1. Bên B có trách nhiệm triển khai thi công, lắp đặt hệ thống phần mềm và tích hợp dữ liệu dòng tiền theo bản đặc tả kỹ thuật đính kèm Phụ lục của Bên A.<br>
                1.2. Bên B cam kết huy động đội ngũ kỹ sư có chuyên môn phù hợp, đảm bảo hoàn thành đúng tiến độ và không có lỗi hệ thống nghiêm trọng.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 2: Giá trị thầu phụ, Chu kỳ thanh toán và Thuế VAT</span>
            <div class="text-justify">
                2.1. Tổng giá trị gói thầu phụ thương mại: <strong>{{ number_format($contract->value ?? 0, 0, ',', '.') }} VNĐ</strong> (Giá trị đã bao gồm toàn bộ chi phí nhân công và linh kiện liên quan).<br>
                2.2. Phương thức thanh toán thương mại: <strong>{{ $contract->payment_method ?? 'BANK_TRANSFER' }}</strong>.<br>
                2.3. Điều khoản đối soát & thanh toán đặc biệt: <strong>{{ $contract->payment_terms ?? 'Thanh toán trong 30 ngày kể từ ngày nghiệm thu kỹ thuật từng phần' }}</strong>.<br>
                2.4. Chu kỳ thanh toán định kỳ: <strong>{{ $contract->billing_cycle ?? 'Theo tiến độ nghiệm thu giai đoạn (Milestones)' }}</strong>.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 3: Nghiệm thu kỹ thuật và Phạt chậm tiến độ</span>
            <div class="text-justify">
                3.1. Quy trình nghiệm thu: Bên A sẽ kiểm tra và lập Biên bản nghiệm thu kỹ thuật trong vòng 05 ngày làm việc sau khi Bên B bàn giao sản phẩm giai đoạn.<br>
                3.2. Phạt vi phạm chậm tiến độ: Nếu Bên B chậm bàn giao sản phẩm theo mốc thời gian cam kết, Bên B chịu mức phạt 1% giá trị giai đoạn đó cho mỗi ngày chậm trễ, nhưng tổng mức phạt không quá 8% tổng giá trị hợp đồng.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 4: Bảo mật thông tin khách hàng và Mã nguồn</span>
            <div class="text-justify">
                4.1. Bên B tuyệt đối bảo mật các cơ sở dữ liệu khách hàng, mã nguồn phần mềm và kiến trúc hạ tầng hệ thống của Bên A. Mọi vi phạm về bảo mật thông tin sẽ cấu thành hành vi vi phạm hợp đồng nghiêm trọng và chịu chế tài xử lý theo Luật Thương mại Việt Nam.
            </div>
        </div>

    @else
        <!-- CHƯƠNG ĐIỀU KHOẢN HỢP ĐỒNG CUNG CẤP DỊCH VỤ -->
        <div class="section-title">CHƯƠNG ĐIỀU KHOẢN CHI TIẾT HỢP ĐỒNG DỊCH VỤ</div>
        
        <div class="clause-item">
            <span class="clause-title-text">Điều 1: Phạm vi dịch vụ và Cam kết mức độ dịch vụ (SLA)</span>
            <div class="text-justify">
                1.1. Bên A chịu trách nhiệm cung cấp dịch vụ phần mềm quản lý chấm công tự động, kiểm soát dòng tiền doanh nghiệp và linking hóa đơn điện tử cho Bên B.<br>
                1.2. Cam kết mức độ dịch vụ (SLA): Đảm bảo hạ tầng đám mây hoạt động liên tục (Uptime) tối thiểu <strong>99.9%</strong>. Thời gian tiếp nhận và xử lý sự cố kỹ thuật cấp độ 1 không quá 02 giờ kể từ khi Bên B gửi ticket yêu cầu hỗ trợ.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 2: Biểu phí dịch vụ, Thời hạn và Chu kỳ thanh toán</span>
            <div class="text-justify">
                2.1. Phí bản quyền phần mềm (SaaS Fee): <strong>{{ number_format($contract->value ?? 0, 0, ',', '.') }} VNĐ</strong>.<br>
                2.2. Chu kỳ đối soát dịch vụ: <strong>{{ $contract->billing_cycle ?? 'Hàng tháng (Monthly billing)' }}</strong>.<br>
                2.3. Thời hạn thanh toán định kỳ: <strong>{{ $contract->payment_terms ?? 'Thanh toán trong vòng 10 ngày đầu tiên của kỳ đối soát tiếp theo' }}</strong> thông qua hình thức <strong>{{ $contract->payment_method ?? 'BANK_TRANSFER' }}</strong>.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 3: Bản quyền sở hữu trí tuệ và Bảo mật dữ liệu</span>
            <div class="text-justify">
                3.1. Sở hữu trí tuệ: Bên A giữ toàn quyền sở hữu trí tuệ đối với nền tảng phần mềm, mã nguồn và hệ thống cơ sở dữ liệu cốt lõi.<br>
                3.2. Bảo mật dữ liệu: Bên A cam kết bảo mật toàn bộ dữ liệu nhân sự, thông tin dòng tiền và hợp đồng của Bên B theo tiêu chuẩn an toàn thông tin ISO/IEC 27001 và không chia sẻ cho bất kỳ bên thứ ba nào khi chưa được đồng ý bằng văn bản.
            </div>
        </div>

        <div class="clause-item">
            <span class="clause-title-text">Điều 4: Giải quyết tranh chấp phát sinh</span>
            <div class="text-justify">
                4.1. Mọi tranh chấp phát sinh trong quá trình vận hành dịch vụ trước hết sẽ được giải quyết thông qua thương lượng hòa giải thiện chí. Trường hợp không tự giải quyết được, vụ việc sẽ được đưa ra phân xử tại Trung tâm Trọng tài Quốc tế Việt Nam (VIAC) theo quy tắc tố tụng trọng tài của Trung tâm này.
            </div>
        </div>
    @endif

    <div class="clause-item" style="margin-top: 14pt;">
        <span class="clause-title-text">Điều khoản thi hành chung:</span>
        <div class="text-justify">
            - Hợp đồng này có hiệu lực pháp lý đầy đủ kể từ ngày ký ghi ở phần đầu tiên.<br>
            - Hai bên cam kết thực hiện đúng và đầy đủ tất cả các điều khoản quy định. Hợp đồng được lập thành 02 (hai) bản gốc có giá trị pháp lý hoàn toàn tương đương như nhau, mỗi bên giữ 01 (một) bản gốc để thực thi.
        </div>
    </div>

    <!-- PHẦN CHỮ KÝ PHÁP LÝ BÊN A - BÊN B (BÊN TRANG TRỌNG) -->
    <div class="signature-container">
        <table class="signature-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-role">ĐẠI DIỆN BÊN A<br><span style="font-size: 8pt; font-weight: normal; font-style: italic;">(Ký tên, ghi rõ họ tên và đóng dấu pháp lý)</span></div>
                    <div class="signature-name" style="margin-top: 50pt;">Ông/Bà: {{ $contract->company->legal_representative ?? 'Nguyễn Văn A' }}</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-role">ĐẠI DIỆN BÊN B<br><span style="font-size: 8pt; font-weight: normal; font-style: italic;">(Ký tên và ghi rõ họ tên)</span></div>
                    <div class="signature-name" style="margin-top: 50pt;">
                        @if($contract->type === 'LABOR' && $contract->employee)
                            Ông/Bà: {{ $contract->employee->full_name }}
                        @elseif($contract->type === 'VENDOR')
                            Ông/Bà: {{ $contract->partner_representative ?? $contract->partner_name }}
                        @else
                            Ông/Bà: {{ $contract->partner_representative ?? $contract->partner_name }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

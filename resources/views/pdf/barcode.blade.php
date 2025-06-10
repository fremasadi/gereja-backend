<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Code - {{ $record->id }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
            line-height: 1.4;
        }
        
        .qrcode-container {
            border: 3px solid #000;
            padding: 30px;
            margin: 20px auto;
            width: 350px;
            background: white;
            text-align: center;
        }
        
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
            width: 40%;
        }
        
        .info-value {
            color: #666;
            width: 55%;
            text-align: right;
        }
        
        .qrcode-section {
            margin: 30px 0;
            padding: 20px;
            background: white;
            border: 2px solid #333;
        }
        
        .qrcode-image {
            margin: 15px 0;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
        }
        
        .qrcode-image img {
            width: 200px;
            height: 200px;
            max-width: 100%;
        }
        
        .qrcode-id {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-top: 15px;
            letter-spacing: 2px;
            border: 1px solid #ccc;
            padding: 8px;
            background: #f0f0f0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 11px;
            color: #666;
        }
        
        .scan-instruction {
            margin-top: 20px;
            font-size: 13px;
            color: #007bff;
            font-weight: bold;
        }
        
        .data-preview {
            margin-top: 15px;
            font-size: 10px;
            color: #888;
            font-style: italic;
            background: #f9f9f9;
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="qrcode-container">
        <div class="header">
            <h2>QR CODE LABEL</h2>
        </div>
        
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">ID:</span>
                <span class="info-value">{{ $record->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal:</span>
                <span class="info-value">{{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-in:</span>
                <span class="info-value">{{ $record->checkin_time ?? '-' }}</span>
            </div>
        </div>
        
        <div class="qrcode-section">
            <div class="qrcode-image">
                <img src="{{ $qrCodeImage }}" alt="QR Code {{ $record->id }}">
            </div>
            <div class="qrcode-id">
                ID: {{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        
        <div class="scan-instruction">
            📱 Scan QR Code untuk mendapatkan data lengkap
        </div>
        
        <div class="data-preview">
            <strong>Data dalam QR Code:</strong><br>
            ID: {{ $record->id }}<br>
            Tanggal: {{ $record->tanggal ? $record->tanggal->format('Y-m-d') : 'null' }}<br>
            Check-in: {{ $record->checkin_time ?? 'null' }}<br>
            Type: attendance_record
        </div>
        
        <div class="footer">
            <p>Generated: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>System Generated QR Code - Contains Full Data</p>
        </div>
    </div>
</body>
</html>
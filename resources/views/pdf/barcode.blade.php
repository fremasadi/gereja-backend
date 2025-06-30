<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Code Absensi - {{ $record->id }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .qrcode-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 450px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .qrcode-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #4CAF50, #2196F3, #FF9800, #E91E63);
        }
        
        .church-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .church-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .church-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .church-subtitle {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .attendance-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #667eea;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-2px);
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .qrcode-section {
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 20px;
            position: relative;
        }
        
        .qrcode-section::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #667eea, #764ba2, #667eea);
            border-radius: 22px;
            z-index: -1;
        }
        
        .qrcode-image {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .qrcode-image img {
            width: 250px;
            height: 250px;
            max-width: 100%;
            border-radius: 10px;
        }
        
        .qrcode-id {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            background: white;
            padding: 15px 25px;
            border-radius: 25px;
            letter-spacing: 3px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .scan-instruction {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            margin: 25px 0;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }
        
        .scan-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .data-preview {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 20px;
            margin: 25px 0;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            line-height: 1.6;
        }
        
        .data-preview strong {
            color: #495057;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            font-size: 12px;
            color: #95a5a6;
            text-align: center;
        }
        
        .footer-item {
            margin: 5px 0;
        }
        
        .timestamp {
            font-weight: 600;
            color: #667eea;
        }
        
        .security-badge {
            background: #e74c3c;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 10px;
        }
        
        @media print {
            body {
                background: white !important;
                padding: 20px !important;
                margin: 0 !important;
                text-align: center !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 100vh !important;
            }
            
            .qrcode-container {
                box-shadow: none !important;
                border: 2px solid #333 !important;
                text-align: center !important;
                margin: 0 auto !important;
                width: 450px !important;
                max-width: 90% !important;
            }
            
            * {
                text-align: center !important;
            }
        }
    </style>
</head>
<body>
    <div class="qrcode-container">
        <div class="church-header">
            <div class="church-logo">B</div>
            <div class="church-name">Gereja Bethany</div>
            <div class="church-subtitle">Sistem Absensi Ibadah Digital</div>
            <div class="attendance-title">QR Code Check-In</div>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Service ID</div>
                <div class="info-value">{{ $record->id }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Service Name</div>
                <div class="info-value">{{ $record->name }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Service Time</div>
                <div class="info-value">{{ $record->service_time }}</div>
            </div>
            <div class="info-card">
                <div class="info-label">Status</div>
                <div class="info-value">{{ $record->is_active ? 'Active' : 'Inactive' }}</div>
            </div>
        </div>
        
        <div class="qrcode-section">
            <div class="qrcode-image">
                <img src="{{ $qrCodeImage }}" alt="QR Code Absensi {{ $record->id }}">
            </div>
            <div class="qrcode-id">
                @php
                    $decodedData = json_decode($qrData, true);
                @endphp
                ID: {{ $decodedData ? $decodedData['id'] : $qrData }}
            </div>
        </div>
        
        <div class="scan-instruction">
            <span class="scan-icon">📱</span>
            Scan untuk Check-In Ibadah
        </div>
        
        <div class="data-preview">
            <strong>QR Code Data:</strong><br>
            @php
                $decodedData = json_decode($qrData, true);
            @endphp
            @if($decodedData)
                ID: {{ $decodedData['id'] }}<br>
                Name: {{ $decodedData['name'] }}<br>
                Service Time: {{ $decodedData['service_time'] }}<br>
                Status: {{ $decodedData['is_active'] ? 'Active' : 'Inactive' }}<br>
                Created: {{ $decodedData['created_at'] }}
            @else
                Service ID: {{ $qrData }}
            @endif
        </div>
        
        <div class="footer">
            <div class="footer-item">
                <span class="timestamp">Generated: {{ now()->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="footer-item">
                Sistem Absensi Digital Gereja Bethany
            </div>
        </div>
    </div>
</body>
</html>
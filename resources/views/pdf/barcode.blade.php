<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Barcode - {{ $record->id }}</title>
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
        
        .barcode-container {
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
        
        .barcode-section {
            margin: 30px 0;
            padding: 20px;
            background: white;
            border: 2px solid #333;
        }
        
        .barcode-image {
            margin: 15px 0;
        }
        
        .barcode-image img {
            max-width: 100%;
            height: auto;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-top: 10px;
            letter-spacing: 3px;
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
            font-size: 12px;
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <div class="header">
            <h2>BARCODE LABEL</h2>
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
        
        <div class="barcode-section">
            <div class="barcode-image">
                <img src="{{ $barcodeImage }}" alt="Barcode {{ $barcodeData }}">
            </div>
            <div class="barcode-number">
                {{ $barcodeData }}
            </div>
        </div>
        
        <div class="scan-instruction">
            Scan barcode di atas untuk verifikasi data
        </div>
        
        <div class="footer">
            <p>Generated: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>System Generated Barcode - Do Not Duplicate</p>
        </div>
    </div>
</body>
</html>
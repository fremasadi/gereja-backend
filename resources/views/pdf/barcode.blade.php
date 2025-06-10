<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Barcode - {{ $record->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            text-align: center;
        }
        
        .barcode-container {
            border: 2px solid #333;
            padding: 20px;
            margin: 20px auto;
            width: 300px;
            background: white;
        }
        
        .barcode-info {
            margin-bottom: 15px;
        }
        
        .barcode-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .barcode-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            font-weight: bold;
            background: #f0f0f0;
            padding: 10px;
            border: 1px solid #ccc;
            letter-spacing: 2px;
        }
        
        .date-info {
            margin-top: 15px;
            font-size: 12px;
            color: #666;
        }
        
        @media print {
            body { margin: 0; }
            .barcode-container { 
                width: auto; 
                border: 1px solid #333;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <div class="barcode-info">
            <h3>Data Barcode</h3>
            <p><strong>ID:</strong> {{ $record->id }}</p>
            <p><strong>Tanggal:</strong> {{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</p>
            <p><strong>Check-in:</strong> {{ $record->checkin_time ?? '-' }}</p>
        </div>
        
        <div class="barcode-display">
            {{ str_pad($record->id, 8, '0', STR_PAD_LEFT) }}
        </div>
        
        <div class="date-info">
            Generated: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Barcodes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .barcode-item {
            border: 1px solid #333;
            padding: 15px;
            text-align: center;
            background: white;
            page-break-inside: avoid;
        }
        
        .barcode-info h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        
        .barcode-info p {
            margin: 3px 0;
            font-size: 12px;
        }
        
        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px;
            border: 1px solid #ccc;
            letter-spacing: 1px;
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        @media print {
            body { margin: 0; }
            .barcode-grid { 
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            .barcode-item { 
                border: 1px solid #333;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h2>Bulk Barcode Export</h2>
        <p>Total: {{ $records->count() }} items</p>
    </div>
    
    <div class="barcode-grid">
        @foreach($records as $record)
        <div class="barcode-item">
            <div class="barcode-info">
                <h4>ID: {{ $record->id }}</h4>
                <p><strong>Tanggal:</strong> {{ $record->tanggal ? $record->tanggal->format('d/m/Y') : '-' }}</p>
                <p><strong>Check-in:</strong> {{ $record->checkin_time ?? '-' }}</p>
            </div>
            
            <div class="barcode-display">
                {{ str_pad($record->id, 8, '0', STR_PAD_LEFT) }}
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="footer">
        Generated: {{ now()->format('d/m/Y H:i:s') }} | 
        Total Records: {{ $records->count() }}
    </div>
</body>
</html>
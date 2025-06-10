<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulk Barcodes PDF</title>
    <style>
        @page {
            margin: 10mm;
            size: A4;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 12px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        
        .page-header p {
            margin: 5px 0;
            color: #666;
        }
        
        .barcode-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 15px;
        }
        
        .barcode-item {
            width: 48%;
            border: 2px solid #333;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
            background: white;
            page-break-inside: avoid;
        }
        
        .item-header {
            background: #f0f0f0;
            padding: 8px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
        }
        
        .item-header h4 {
            margin: 0;
            font-size: 14px;
            color: #333;
        }
        
        .item-info {
            text-align: left;
            margin: 10px 0;
        }
        
        .item-info p {
            margin: 4px 0;
            font-size: 11px;
        }
        
        .item-info strong {
            color: #333;
            display: inline-block;
            width: 60px;
        }
        
        .barcode-image {
            margin: 12px 0;
            text-align: center;
        }
        
        .barcode-image img {
            max-width: 100%;
            height: auto;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            font-weight: bold;
            background: #f8f9fa;
            padding: 5px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
            margin-top: 8px;
        }
        
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            page-break-inside: avoid;
        }
        
        /* Untuk tampilan 2 kolom yang rapi */
        .barcode-item:nth-child(odd) {
            clear: left;
        }
        
        /* Print specific styles */
        @media print {
            .barcode-item {
                width: 48%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h2>BULK BARCODE EXPORT</h2>
        <p>Generated: {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Total Records: {{ $totalRecords }}</p>
    </div>
    
    <div class="barcode-grid">
        @foreach($barcodeData as $item)
        <div class="barcode-item">
            <div class="item-header">
                <h4>Record #{{ $item['record']->id }}</h4>
            </div>
            
            <div class="item-info">
                <p><strong>ID:</strong> {{ $item['record']->id }}</p>
                <p><strong>Tanggal:</strong> {{ $item['record']->tanggal ? $item['record']->tanggal->format('d/m/Y') : '-' }}</p>
                <p><strong>Check-in:</strong> {{ $item['record']->checkin_time ?? '-' }}</p>
            </div>
            
            <div class="barcode-image">
                <img src="{{ $item['barcodeImage'] }}" alt="Barcode {{ $item['barcodeData'] }}">
            </div>
            
            <div class="barcode-number">
                {{ $item['barcodeData'] }}
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="footer">
        <p>System Generated Barcodes - {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Total Items: {{ $totalRecords }} | Page 1</p>
    </div>
</body>
</html>
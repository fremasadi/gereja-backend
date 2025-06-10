<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode</title>
    <style>
        body { font-family: sans-serif; text-align: center; }
        h2 { margin-bottom: 20px; }
        .barcode { margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Data Barcode</h2>

    <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') }}</p>
    <p><strong>Waktu Checkin:</strong> {{ $record->checkin_time }}</p>

    <div class="barcode">
        {!! DNS1D::getBarcodeHTML($record->id, 'C128') !!}
        <p>{{ $record->id }}</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode</title>
    <style>
        body { font-family: sans-serif; }
        h2 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Data Barcode</h2>
    <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($record->tanggal)->translatedFormat('l, d F Y') }}</p>
    <p><strong>Waktu Checkin:</strong> {{ $record->checkin_time }}</p>
</body>
</html>

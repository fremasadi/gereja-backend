<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Code Absensi - {{ $record->id }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f6f8;
            color: #333;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .subtitle {
            margin-bottom: 30px;
            color: #777;
        }

        .qr-image {
            margin: 30px 0;
        }

        .qr-image img {
            width: 200px;
            height: 200px;
        }

        .data-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: left;
            font-size: 14px;
        }

        .data-box strong {
            color: #444;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>QR Code Ibadah</h1>
        <div class="subtitle">Gereja Bethany - Sistem Absensi Digital</div>

        <div class="qr-image">
            <img src="{{ $qrCodeImage }}" alt="QR Code">
        </div>

        @php
            $decodedData = json_decode($qrData, true);
        @endphp

<div class="data-box">
    @php
        $decodedData = json_decode($qrData, true);
    @endphp

    @if (is_array($decodedData) && isset($decodedData['id']))
        <p><strong>ID:</strong> {{ $decodedData['id'] }}</p>
    @else
        <p><strong>ID:</strong> {{ $qrData }}</p>
    @endif
</div>


        <div class="footer">
            Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>
</body>
</html>

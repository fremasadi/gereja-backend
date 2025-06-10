<!DOCTYPE html>
<html>
<head>
    <title>Absensi PDF</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Data Absensi</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Waktu Check-In</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($record->tanggal)->format('d M Y') }}</td>
                    <td>{{ $record->checkin_time }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

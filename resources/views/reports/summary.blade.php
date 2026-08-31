<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Ringkasan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #14151A; }
        h1 { font-size: 18px; color: #1E3A8A; margin-bottom: 4px; }
        .subtitle { color: #71717A; margin-bottom: 20px; }
        .stats { display: table; width: 100%; margin-bottom: 24px; }
        .stat-box { display: table-cell; width: 33%; padding: 10px; }
        .stat-value { font-size: 20px; font-weight: bold; }
        .stat-label { font-size: 11px; color: #71717A; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        th { background: #f4f5f8; text-transform: uppercase; font-size: 10px; color: #71717A; }
    </style>
</head>
<body>
    <h1>Laporan Ringkasan Kost</h1>
    <p class="subtitle">Dibuat pada {{ $generatedAt->translatedFormat('d F Y, H:i') }} WIB</p>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-value">{{ $occupancy['total'] > 0 ? round($occupancy['occupied'] / $occupancy['total'] * 100, 1) : 0 }}%</div>
            <div class="stat-label">OCCUPANCY RATE</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">Rp {{ number_format($revenue, 0, ',', '.') }}</div>
            <div class="stat-label">TOTAL REVENUE</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $outstanding->count() }}</div>
            <div class="stat-label">TAGIHAN BELUM LUNAS</div>
        </div>
    </div>

    <h3>Detail Tagihan Belum Lunas</h3>
    <table>
        <thead>
            <tr><th>Penghuni</th><th>Kamar</th><th>Periode</th><th>Jumlah</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach ($outstanding as $inv)
                <tr>
                    <td>{{ $inv->contract->tenant->user->name ?? '-' }}</td>
                    <td>{{ $inv->contract->room->room_number ?? '-' }}</td>
                    <td>{{ $inv->period }}</td>
                    <td>Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                    <td>{{ strtoupper($inv->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
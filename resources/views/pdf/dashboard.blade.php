<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Dashboard</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.35;
            color: #1f2937;
        }

        h1 {
            font-size: 15pt;
            margin-bottom: 1mm;
        }

        h2 {
            font-size: 10pt;
            margin: 4mm 0 2mm;
            padding-bottom: 1mm;
            border-bottom: 1px solid #d1d5db;
        }

        .subtitle {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 1mm;
        }

        .muted {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 1.2mm 1.6mm;
            text-align: left;
        }

        .data-table th {
            background: #f3f4f6;
            font-size: 7.5pt;
        }

        .data-table .num {
            text-align: right;
        }

        .stat-table td {
            width: 25%;
            border: 1px solid #d1d5db;
            padding: 2mm;
            vertical-align: top;
        }

        .stat-label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 1mm;
        }

        .pie-cell {
            width: 46%;
            vertical-align: top;
        }

        .legend-cell {
            width: 54%;
            vertical-align: top;
            padding-left: 4mm;
        }

        .legend {
            list-style: none;
        }

        .legend li {
            margin-bottom: 1.2mm;
        }

        .swatch {
            display: inline-block;
            width: 3mm;
            height: 3mm;
            margin-right: 1.5mm;
            vertical-align: middle;
        }

        .empty {
            padding: 6mm;
            text-align: center;
            color: #6b7280;
            border: 1px dashed #d1d5db;
        }

        .footer {
            margin-top: 5mm;
            font-size: 7pt;
            color: #9ca3af;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
    $stats = $data['stats'];
    $perolehan = $data['perolehan'];
    $kelas = $data['kelas'];
    $aktivitas = $data['aktivitas'];

    $totalSuara = array_sum(array_column($perolehan, 'votes'));
    $palette = ['#e76f51', '#2a9d8f', '#264653', '#e9c46a', '#f4a261'];

    // Hitung potongan pie (mulai dari atas, arah jarum jam).
    $slices = [];
    $angle = -90.0;
    foreach ($perolehan as $index => $item) {
    $votes = (int) $item['votes'];
    if ($votes <= 0 || $totalSuara <=0) {
        continue;
        }
        $sweep=$votes / $totalSuara * 360;
        $slices[]=[ 'warna'=> $palette[$index % count($palette)],
        'mulai' => $angle,
        'akhir' => $angle + $sweep,
        'persen' => $votes / $totalSuara * 100,
        ];
        $angle += $sweep;
        }

        $cx = 100;
        $cy = 100;
        $ro = 88; // jari-jari luar donut
        $ri = 55; // jari-jari dalam donut
        $toRad = fn (float $deg): float => deg2rad($deg);
        $px = fn (float $deg, float $rad): float => $cx + $rad * cos($toRad($deg));
        $py = fn (float $deg, float $rad): float => $cy + $rad * sin($toRad($deg));
        @endphp

        <h1>Dashboard KPU</h1>
        <div class="subtitle">
            Partisipasi {{ $stats['partisipasi'] }}% dari {{ $stats['total_pemilih'] }} pemilih
            &middot; {{ $schoolName }}
        </div>

        <table class="stat-table">
            <tr>
                <td>
                    <div class="stat-label">Total Pemilih</div>
                    <div class="stat-value">{{ number_format($stats['total_pemilih'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="stat-label">Sudah Voting</div>
                    <div class="stat-value">{{ number_format($stats['sudah_voting'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="stat-label">Belum Voting</div>
                    <div class="stat-value">{{ number_format($stats['belum_voting'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="stat-label">Partisipasi</div>
                    <div class="stat-value">{{ $stats['partisipasi'] }}%</div>
                </td>
            </tr>
        </table>

        @if ($perolehan !== [])
        <h2>Distribusi Suara per Kandidat</h2>
        <table>
            <tr>
                <td class="pie-cell">
                    @if ($slices === [])
                    <div class="empty">Belum ada suara masuk.</div>
                    @elseif (count($slices) === 1)
                    <svg viewBox="0 0 200 200" width="200" height="200">
                        <path
                            d="M 12 100 A 88 88 0 1 0 188 100 A 88 88 0 1 0 12 100 Z M 45 100 A 55 55 0 1 0 155 100 A 55 55 0 1 0 45 100 Z"
                            fill="{{ $slices[0]['warna'] }}" fill-rule="evenodd" />
                        <text x="{{ $cx }}" y="{{ $cy }}" text-anchor="middle" dy="0.35em"
                            font-size="16" font-weight="bold" fill="{{ $slices[0]['warna'] }}">
                            {{ round($slices[0]['persen']) }}%
                        </text>
                    </svg>
                    @else
                    <svg viewBox="0 0 200 200" width="200" height="200">
                        @foreach ($slices as $slice)
                        @php
                        $large = ($slice['akhir'] - $slice['mulai']) > 180 ? 1 : 0;
                        @endphp
                        <path
                            d="M {{ round($px($slice['mulai'], $ro), 2) }} {{ round($py($slice['mulai'], $ro), 2) }} A {{ $ro }} {{ $ro }} 0 {{ $large }} 1 {{ round($px($slice['akhir'], $ro), 2) }} {{ round($py($slice['akhir'], $ro), 2) }} L {{ round($px($slice['akhir'], $ri), 2) }} {{ round($py($slice['akhir'], $ri), 2) }} A {{ $ri }} {{ $ri }} 0 {{ $large }} 0 {{ round($px($slice['mulai'], $ri), 2) }} {{ round($py($slice['mulai'], $ri), 2) }} Z"
                            fill="{{ $slice['warna'] }}" />
                        @endforeach
                        @foreach ($slices as $slice)
                        @if ($slice['persen'] >= 5)
                        @php
                        $mid = ($slice['mulai'] + $slice['akhir']) / 2;
                        $lx = $px($mid, ($ro + $ri) / 2);
                        $ly = $py($mid, ($ro + $ri) / 2);
                        @endphp
                        <text x="{{ round($lx, 2) }}" y="{{ round($ly, 2) }}"
                            text-anchor="middle" dy="0.35em" font-size="12" font-weight="bold"
                            fill="#ffffff">{{ round($slice['persen']) }}%</text>
                        @endif
                        @endforeach
                    </svg>
                    @endif
                </td>
                <td class="legend-cell">
                    <ul class="legend">
                        @foreach ($perolehan as $index => $item)
                        <li>
                            <span class="swatch"
                                style="background: {{ $palette[$index % count($palette)] }};"></span>
                            <strong>{{ $item['nomor_urut'] }}. {{ $item['nama_lengkap'] }}</strong>
                            <span class="muted">&mdash; {{ number_format($item['votes'], 0, ',', '.') }} suara
                                ({{ $item['percentage'] }}%)</span>
                        </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        </table>

        <h2>Perolehan Suara</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th>Nama Kandidat</th>
                    <th class="num" style="width: 18%;">Suara</th>
                    <th class="num" style="width: 20%;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perolehan as $item)
                <tr>
                    <td>{{ $item['nomor_urut'] }}</td>
                    <td>{{ $item['nama_lengkap'] }}</td>
                    <td class="num">{{ number_format($item['votes'], 0, ',', '.') }}</td>
                    <td class="num">{{ $item['percentage'] }}%</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2"><strong>Total Suara</strong></td>
                    <td class="num"><strong>{{ number_format($totalSuara, 0, ',', '.') }}</strong></td>
                    <td class="num"><strong>100%</strong></td>
                </tr>
            </tbody>
        </table>
        @else
        <h2>Distribusi Suara per Kandidat</h2>
        <div class="empty">Belum ada kandidat.</div>
        @endif

        <h2>Partisipasi per Kelas</h2>
        @if ($kelas !== [])
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th class="num" style="width: 12%;">Total</th>
                    <th class="num" style="width: 12%;">Sudah</th>
                    <th class="num" style="width: 12%;">Belum</th>
                    <th class="num" style="width: 18%;">Partisipasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($kelas as $item)
                <tr>
                    <td>{{ $item['kelas'] }}</td>
                    <td class="num">{{ $item['total'] }}</td>
                    <td class="num">{{ $item['sudah'] }}</td>
                    <td class="num">{{ $item['belum'] }}</td>
                    <td class="num">{{ $item['persentase'] }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty">Belum ada data kelas.</div>
        @endif

        <h2>Riwayat Aktivitas Voting</h2>
        @if ($aktivitas !== [])
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th>Pemilih</th>
                    <th style="width: 16%;">Kelas</th>
                    <th style="width: 14%;">Aksi</th>
                    <th style="width: 18%;">Kandidat</th>
                    <th style="width: 20%;">Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($aktivitas as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['nama_pemilih'] }}</td>
                    <td>{{ $item['kelas'] ?? '-' }}</td>
                    <td>{{ $item['aksi'] }}</td>
                    <td>{{ $item['nama_kandidat'] ?? '-' }}</td>
                    <td>{{ $item['waktu'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty">Belum ada aktivitas voting.</div>
        @endif

        <div class="footer">Dicetak pada {{ now()->translatedFormat('d F Y H:i') }}</div>
</body>

</html>
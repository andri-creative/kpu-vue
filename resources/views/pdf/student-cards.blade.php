<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kartu Siswa</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 5pt;
            line-height: 1.1;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            width: 33.333%;
            border: 1px solid #333;
            padding: 1.5mm;
            vertical-align: top;
        }

        .card-header {
            text-align: center;
            border-bottom: 1px solid #333;
            padding-bottom: 1mm;
            margin-bottom: 1mm;
        }

        .school-name {
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }

        .card-title {
            font-size: 5.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .card-body {
            font-size: 5pt;
        }

        .info-row {
            margin-bottom: 1mm;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 22%;
            vertical-align: top;
        }

        .info-value {
            display: inline-block;
            width: 76%;
            vertical-align: top;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <table>
        @foreach($students->chunk(3) as $row)
        <tr>
            @foreach($row as $student)
            <td>
                <div class="card-header">
                    <div class="school-name">{{ $schoolName }}</div>
                    <div class="card-title">Kartu Siswa</div>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">NIS</span>
                        <span class="info-value">{{ $student->nis }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value">{{ $student->profile->nama_lengkap }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kelas</span>
                        <span class="info-value">{{ $student->profile->kelas }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kode</span>
                        <span class="info-value">{{ $student->kode ?? '–' }}</span>
                    </div>
                </div>
            </td>
            @endforeach
            @for($i = count($row); $i < 3; $i++)
                <td style="border: none;">
                </td>
                @endfor
        </tr>
        @endforeach
    </table>
</body>

</html>
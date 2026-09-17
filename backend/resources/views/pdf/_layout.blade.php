<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Dokumen SIKOMANDO')</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            margin: 20px 25px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .kop-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1a365d;
        }
        .kop-subtitle {
            font-size: 9pt;
            color: #4a5568;
        }
        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0 5px 0;
            color: #2d3748;
        }
        .doc-number {
            text-align: center;
            font-size: 10pt;
            color: #718096;
            margin-bottom: 20px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e0;
            padding: 6px 8px;
            font-size: 9.5pt;
        }
        table.data-table th {
            background-color: #edf2f7;
            font-weight: bold;
            color: #2d3748;
            text-align: left;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 3px 0;
            font-size: 10pt;
            vertical-align: top;
        }
        .info-label {
            width: 160px;
            font-weight: bold;
            color: #4a5568;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 3px;
        }
        .footer-table {
            width: 100%;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8pt;
            color: #a0aec0;
        }
        .signature-section {
            margin-top: 30px;
            width: 100%;
        }
        .signature-box {
            width: 200px;
            text-align: center;
            float: right;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div class="kop-title">SIKOMANDO</div>
                <div class="kop-subtitle">Sistem Informasi Komprehensif Manajemen Digitalisasi Hibah Organisasi</div>
            </td>
            <td class="text-right" style="font-size: 8pt; color: #a0aec0;">
                Dokumen Resmi Sistem
            </td>
        </tr>
    </table>

    <div class="doc-title">@yield('doc-title')</div>
    <div class="doc-number">@yield('doc-number')</div>

    @yield('content')

    <table class="footer-table">
        <tr>
            <td>Dicetak otomatis oleh SIKOMANDO pada {{ $generatedAt }} oleh {{ $generatedBy }}</td>
            <td class="text-right">Halaman 1</td>
        </tr>
    </table>
</body>
</html>


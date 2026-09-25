<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            color: #121212;
            background: #FFFFFF;
        }
        .page {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 190mm;
            padding: 18mm 20mm;
            overflow: hidden;
        }
        .frame {
            position: absolute;
            inset: 8mm;
            border: 2px solid #0E2E5F;
        }
        .frame-inner {
            position: absolute;
            inset: 10mm;
            border: 1px solid #E5E5E5;
        }
        .corner-tr {
            position: absolute;
            top: -20mm;
            right: -25mm;
            width: 90mm;
            height: 90mm;
            background: #0E2E5F;
            border-radius: 50% 0 50% 50%;
            opacity: 0.92;
        }
        .corner-tr-2 {
            position: absolute;
            top: -5mm;
            right: -10mm;
            width: 55mm;
            height: 55mm;
            background: #0A2247;
            border-radius: 50% 40% 50% 40%;
            opacity: 0.85;
        }
        .corner-bl {
            position: absolute;
            bottom: -25mm;
            left: -30mm;
            width: 95mm;
            height: 95mm;
            background: #0E2E5F;
            border-radius: 50% 50% 40% 0;
            opacity: 0.9;
        }
        .corner-bl-2 {
            position: absolute;
            bottom: -8mm;
            left: -8mm;
            width: 50mm;
            height: 50mm;
            background: #E01D2E;
            border-radius: 40% 50% 30% 50%;
            opacity: 0.75;
        }
        .content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding-top: 8mm;
        }
        .brand {
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #0E2E5F;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .brand-sub {
            font-size: 9px;
            color: #4A4A4A;
            margin-bottom: 10mm;
        }
        .title {
            font-size: 28px;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: #0E2E5F;
            font-weight: bold;
            margin: 0 0 2mm 0;
        }
        .title-line {
            width: 60mm;
            height: 2px;
            background: #E01D2E;
            margin: 0 auto 8mm auto;
        }
        .recipient {
            font-size: 26px;
            font-weight: bold;
            color: #121212;
            margin: 6mm 0 3mm 0;
        }
        .phrase {
            font-size: 12px;
            color: #4A4A4A;
            margin-bottom: 6mm;
        }
        .meta {
            width: 85%;
            margin: 0 auto 8mm auto;
            border-collapse: collapse;
        }
        .meta td {
            padding: 4mm 3mm 2mm 3mm;
            vertical-align: bottom;
            text-align: left;
        }
        .meta .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0E2E5F;
            font-weight: bold;
            display: block;
            margin-bottom: 2mm;
        }
        .meta .value {
            font-size: 13px;
            color: #121212;
            border-bottom: 1px solid #0E2E5F;
            padding-bottom: 2mm;
            display: block;
        }
        .tipo-badge {
            display: inline-block;
            margin-top: 2mm;
            padding: 2mm 4mm;
            background: #F5F5F5;
            border: 1px solid #E5E5E5;
            color: #0E2E5F;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-table {
            width: 100%;
            margin-top: 10mm;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: bottom;
            padding: 0 3mm;
        }
        .sig-line {
            border-top: 1px solid #4A4A4A;
            width: 45mm;
            margin: 0 auto 2mm auto;
        }
        .sig-name {
            font-size: 9px;
            color: #4A4A4A;
            text-align: center;
        }
        .verify {
            text-align: left;
            font-size: 8px;
            color: #4A4A4A;
            line-height: 1.5;
        }
        .verify strong {
            color: #0E2E5F;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 8px;
        }
        .qr-box {
            text-align: right;
        }
        .qr-box img {
            width: 28mm;
            height: 28mm;
            border: 1px solid #E5E5E5;
            padding: 1mm;
        }
        .qr-caption {
            font-size: 7px;
            color: #4A4A4A;
            margin-top: 1mm;
            text-align: center;
        }
        .seal {
            position: absolute;
            top: 14mm;
            right: 22mm;
            width: 22mm;
            height: 22mm;
            border: 2px solid #0E2E5F;
            border-radius: 50%;
            z-index: 3;
            text-align: center;
            padding-top: 5mm;
            font-size: 7px;
            color: #0E2E5F;
            font-weight: bold;
            letter-spacing: 0.5px;
            background: rgba(255,255,255,0.9);
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="corner-tr"></div>
        <div class="corner-tr-2"></div>
        <div class="corner-bl"></div>
        <div class="corner-bl-2"></div>
        <div class="frame"></div>
        <div class="frame-inner"></div>
        <div class="seal">UMSS<br>FC</div>

        <div class="content">
            <div class="brand">Universidad Mayor de San Simón</div>
            <div class="brand-sub">Formación Continua</div>

            <h1 class="title">Certificado</h1>
            <div class="title-line"></div>

            <div class="recipient">{{ $certificado['participante'] }}</div>
            <div class="phrase">Por haber completado el curso de:</div>

            <table class="meta">
                <tr>
                    <td style="width: 62%;">
                        <span class="label">Curso</span>
                        <span class="value">{{ $certificado['curso'] }}</span>
                    </td>
                    <td style="width: 38%;">
                        <span class="label">Fecha</span>
                        <span class="value">{{ $certificado['emitido_en'] }}</span>
                    </td>
                </tr>
            </table>

            <div class="tipo-badge">{{ $certificado['tipo'] }}</div>

            <table class="footer-table">
                <tr>
                    <td style="width: 38%;" class="verify">
                        Identificador único:<br>
                        <strong>{{ $certificado['codigo_unico'] }}</strong>
                    </td>
                    <td style="width: 24%; text-align: center;">
                        <div class="sig-line"></div>
                        <div class="sig-name">Director Académico<br>Formación Continua</div>
                    </td>
                    <td style="width: 24%; text-align: center;">
                        <div class="sig-line"></div>
                        <div class="sig-name">Docente del curso<br>{{ $certificado['instructor'] }}</div>
                    </td>
                    <td style="width: 14%;" class="qr-box">
                        @if (! empty($certificado['qr_data_uri']))
                            <img src="{{ $certificado['qr_data_uri'] }}" alt="QR">
                            <div class="qr-caption">Escanear</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

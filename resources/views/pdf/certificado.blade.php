<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: {{ \App\Support\DesignTokens::BLACK }};
            background: {{ \App\Support\DesignTokens::WHITE }};
        }
        .page {
            position: relative;
            width: 297mm;
            height: 210mm;
            overflow: hidden;
            background: {{ \App\Support\DesignTokens::WHITE }};
        }

        .frame {
            position: absolute;
            top: 7mm;
            left: 7mm;
            right: 7mm;
            bottom: 7mm;
            border: 1.8pt solid {{ \App\Support\DesignTokens::NAVY }};
            z-index: 1;
        }
        .frame-inner {
            position: absolute;
            top: 9mm;
            left: 9mm;
            right: 9mm;
            bottom: 9mm;
            border: 0.6pt solid {{ \App\Support\DesignTokens::RED }};
            z-index: 1;
        }

        .wave-r-1 {
            position: absolute;
            top: 0; right: -38mm;
            width: 100mm; height: 210mm;
            background: {{ \App\Support\DesignTokens::NAVY }};
            border-radius: 85mm 0 0 85mm;
            z-index: 0;
        }
        .wave-r-2 {
            position: absolute;
            top: 20mm; right: -30mm;
            width: 82mm; height: 170mm;
            background: {{ \App\Support\DesignTokens::NAVY_DARK }};
            border-radius: 72mm 0 0 72mm;
            z-index: 0;
        }
        .wave-r-3 {
            position: absolute;
            top: 50mm; right: -18mm;
            width: 52mm; height: 110mm;
            background: {{ \App\Support\DesignTokens::RED }};
            border-radius: 48mm 0 0 48mm;
            opacity: 0.65;
            z-index: 0;
        }
        .wave-bl-1 {
            position: absolute;
            left: -42mm; bottom: -48mm;
            width: 115mm; height: 100mm;
            background: {{ \App\Support\DesignTokens::NAVY }};
            border-radius: 0 85mm 0 0;
            z-index: 0;
        }
        .wave-bl-2 {
            position: absolute;
            left: -28mm; bottom: -36mm;
            width: 85mm; height: 72mm;
            background: {{ \App\Support\DesignTokens::RED }};
            border-radius: 0 65mm 0 0;
            opacity: 0.35;
            z-index: 0;
        }

        .content {
            position: absolute;
            top: 12mm;
            left: 14mm;
            right: 52mm;
            bottom: 12mm;
            z-index: 2;
        }

        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }

        .logo-cell { width: 28mm; text-align: left; }
        .logo-img {
            width: 24mm;
            height: auto;
            display: block;
        }

        .title-block { padding-left: 4mm; }
        .title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: {{ \App\Support\DesignTokens::NAVY }};
            line-height: 1.05;
        }
        .title-sub {
            font-size: 9px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
            margin-top: 2mm;
            letter-spacing: 0.4px;
        }
        .title-line {
            width: 42mm;
            height: 1.5px;
            background: {{ \App\Support\DesignTokens::RED }};
            margin-top: 2.5mm;
        }

        .badge-cell { width: 38mm; text-align: right; }
        .doc-badge {
            display: inline-block;
            border: 1.5px solid {{ \App\Support\DesignTokens::NAVY }};
            padding: 2.5mm 3mm;
            text-align: center;
            background: {{ \App\Support\DesignTokens::WHITE }};
        }
        .doc-badge-top {
            font-size: 7px;
            font-weight: bold;
            color: {{ \App\Support\DesignTokens::NAVY }};
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .doc-badge-mid {
            font-size: 10px;
            font-weight: bold;
            color: {{ \App\Support\DesignTokens::RED }};
            margin-top: 1mm;
            letter-spacing: 1px;
        }
        .doc-badge-bot {
            font-size: 6px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
            margin-top: 1mm;
            text-transform: uppercase;
        }

        .recipient {
            margin-top: 11mm;
            font-size: 30px;
            font-weight: bold;
            color: {{ \App\Support\DesignTokens::BLACK }};
            line-height: 1.15;
        }

        .phrase {
            margin-top: 4mm;
            font-size: 11px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
        }

        .meta {
            width: 100%;
            margin-top: 6mm;
            border-collapse: collapse;
        }
        .meta td {
            vertical-align: bottom;
            padding-right: 5mm;
            padding-bottom: 2.5mm;
        }
        .meta .label {
            display: block;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ \App\Support\DesignTokens::NAVY }};
            margin-bottom: 1.5mm;
        }
        .meta .value {
            display: block;
            font-size: 12.5px;
            color: {{ \App\Support\DesignTokens::BLACK }};
            border-bottom: 1px solid {{ \App\Support\DesignTokens::NAVY }};
            padding-bottom: 1.8mm;
            min-height: 5.5mm;
        }

        .tipo {
            display: inline-block;
            margin-top: 4mm;
            padding: 1.5mm 4mm;
            border: 1px solid {{ \App\Support\DesignTokens::NAVY }};
            color: {{ \App\Support\DesignTokens::NAVY }};
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: {{ \App\Support\DesignTokens::GRAY_100 }};
        }

        .verify-block {
            margin-top: 6mm;
            font-size: 8.5px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
            line-height: 1.55;
        }
        .verify-block strong {
            color: {{ \App\Support\DesignTokens::NAVY }};
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
        }
        .verify-url {
            color: {{ \App\Support\DesignTokens::SKY }};
            word-break: break-all;
            font-size: 7.5px;
        }

        .bottom {
            position: absolute;
            left: 14mm;
            right: 52mm;
            bottom: 13mm;
            z-index: 2;
        }
        .bottom-table { width: 100%; border-collapse: collapse; }
        .bottom-table td { vertical-align: bottom; }

        .sig { text-align: center; width: 40%; padding-right: 3mm; vertical-align: bottom; }
        .sig-container {
            height: 18mm;
            text-align: center;
            margin-bottom: 1mm;
        }
        .sig-stamp {
            max-height: 18mm;
            max-width: 45mm;
            display: inline-block;
        }
        .sig-line {
            border-top: 1px solid {{ \App\Support\DesignTokens::GRAY_700 }};
            width: 48mm;
            margin: 0 auto 2mm auto;
        }
        .sig-role {
            font-size: 8px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
            line-height: 1.35;
        }
        .sig-role strong { color: {{ \App\Support\DesignTokens::BLACK }}; }

        .qr-frame {
            display: inline-block;
            border: 1px solid {{ \App\Support\DesignTokens::NAVY }};
            padding: 1.5mm;
            background: {{ \App\Support\DesignTokens::WHITE }};
        }
        .qr-frame img {
            width: 26mm;
            height: 26mm;
            display: block;
        }
        .qr-code-text {
            margin-top: 1.5mm;
            font-size: 6.5px;
            font-family: 'DejaVu Sans Mono', monospace;
            color: {{ \App\Support\DesignTokens::NAVY }};
            font-weight: bold;
        }
        .brand-foot {
            margin-top: 1.5mm;
            font-size: 6px;
            color: {{ \App\Support\DesignTokens::GRAY_700 }};
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="wave-r-1"></div>
        <div class="wave-r-2"></div>
        <div class="wave-r-3"></div>
        <div class="wave-bl-1"></div>
        <div class="wave-bl-2"></div>
        <div class="frame"></div>
        <div class="frame-inner"></div>

        <div class="content">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if (! empty($certificado['logo_data_uri']))
                            <img class="logo-img" src="{{ $certificado['logo_data_uri'] }}" alt="Escudo UMSS">
                        @endif
                    </td>
                    <td class="title-block">
                        <div class="title">Certificado</div>
                        <div class="title-sub">Universidad Mayor de San Simón · Formación Continua</div>
                        <div class="title-line"></div>
                    </td>
                    <td class="badge-cell">
                        <div class="doc-badge">
                            <div class="doc-badge-top">Certificación</div>
                            <div class="doc-badge-mid">UMSS</div>
                            <div class="doc-badge-bot">Documento oficial</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="recipient">{{ $certificado['participante'] }}</div>
            <div class="phrase">{{ $certificado['frase'] ?? 'Por haber completado el curso de:' }}</div>

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
                <tr>
                    <td>
                        <span class="label">Carga horaria</span>
                        <span class="value">{{ $certificado['carga_horaria'] }}</span>
                    </td>
                    <td>
                        <span class="label">Periodo del curso</span>
                        <span class="value">{{ $certificado['fechas_curso'] }}</span>
                    </td>
                </tr>
                @if (! empty($certificado['es_instructor']) && ! empty($certificado['grupos']) && $certificado['grupos'] !== '—')
                <tr>
                    <td colspan="2">
                        <span class="label">Grupo(s) dictados</span>
                        <span class="value">{{ $certificado['grupos'] }}</span>
                    </td>
                </tr>
                @endif
            </table>

            <div class="tipo">{{ $certificado['tipo'] }}</div>

            <div class="verify-block">
                Identificador único: <strong>{{ $certificado['codigo_unico'] }}</strong><br>
                Este certificado se puede validar en:<br>
                <span class="verify-url">{{ $certificado['url_verificacion'] }}</span>
            </div>
        </div>

        <div class="bottom">
            <table class="bottom-table">
                <tr>
                    <td class="sig">
                        <div class="sig-container">
                            @if (! empty($certificado['firma_director_data_uri']))
                                <img src="{{ $certificado['firma_director_data_uri'] }}" class="sig-stamp" alt="Firma Director">
                            @endif
                        </div>
                        <div class="sig-line"></div>
                        <div class="sig-role">
                            <strong>{{ ! empty($certificado['director_nombre']) ? $certificado['director_nombre'] : 'Director Académico' }}</strong><br>
                            {{ ! empty($certificado['director_cargo']) ? $certificado['director_cargo'] : 'Formación Continua' }}
                        </div>
                    </td>
                    <td class="sig">
                        <div class="sig-container">
                            @if (! empty($certificado['es_instructor']))
                                @if (! empty($certificado['firma_jefe_data_uri']))
                                    <img src="{{ $certificado['firma_jefe_data_uri'] }}" class="sig-stamp" alt="Firma Jefe de Departamento">
                                @endif
                            @else
                                @if (! empty($certificado['firma_instructor_data_uri']))
                                    <img src="{{ $certificado['firma_instructor_data_uri'] }}" class="sig-stamp" alt="Firma Docente">
                                @endif
                            @endif
                        </div>
                        <div class="sig-line"></div>
                        <div class="sig-role">
                            @if (! empty($certificado['es_instructor']))
                                <strong>{{ ! empty($certificado['jefe_nombre']) ? $certificado['jefe_nombre'] : 'Jefe de Departamento' }}</strong><br>
                                {{ ! empty($certificado['jefe_cargo']) ? $certificado['jefe_cargo'] : 'Formación Continua' }}
                            @else
                                <strong>{{ $certificado['instructor'] }}</strong><br>
                                Docente del curso
                            @endif
                        </div>
                    </td>
                    <td style="width: 34mm; text-align: center;">
                        @if (! empty($certificado['qr_data_uri']))
                            <div class="qr-frame">
                                <img src="{{ $certificado['qr_data_uri'] }}" alt="Código QR de verificación">
                            </div>
                        @endif
                        <div class="qr-code-text">{{ $certificado['codigo_unico'] }}</div>
                        <div class="brand-foot">UMSS · Verificar</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

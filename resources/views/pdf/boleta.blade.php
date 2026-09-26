<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #121212; font-size: 12px; }
        .border { border: 2px solid #0E2E5F; }
        .bg-navy { background: #0E2E5F; color: #FFFFFF; }
        .title { text-align: center; }
        .title h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .table td, .table th { border: 1px solid #D6D6D6; padding: 7px 10px; vertical-align: top; }
        .table th { background: #F5F5F5; text-align: left; text-transform: uppercase; font-size: 10px; width: 32%; }
        .monto { font-size: 16px; font-weight: bold; }
        .footer { font-size: 9px; color: #4A4A4A; margin-top: 18px; text-align: center; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="border">
        <div class="bg-navy title" style="padding: 12px 16px;">
            <h1>Boleta de Inscripción</h1>
            <div>Formación Continua — Universidad Mayor de San Simón</div>
        </div>

        <div style="padding: 16px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;"><strong>N°:</strong> {{ $boleta['correlativo'] }}</td>
                    <td style="width: 50%; text-align: right;"><strong>Fecha de emisión:</strong> {{ $boleta['emitida_en']->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <table class="table">
                <tr>
                    <th>Participante</th>
                    <td>{{ $boleta['participante'] }}</td>
                </tr>
                <tr>
                    <th>Cédula de Identidad</th>
                    <td>{{ $boleta['ci'] }}</td>
                </tr>
                <tr>
                    <th>Código SIS</th>
                    <td>{{ $boleta['cod_sis'] ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Categoría</th>
                    <td>{{ $boleta['tipo_participante'] }}</td>
                </tr>
                <tr>
                    <th>Curso</th>
                    <td>{{ $boleta['curso'] }}</td>
                </tr>
                <tr>
                    <th>Carga horaria</th>
                    <td>{{ $boleta['carga_horaria'] }} horas académicas</td>
                </tr>
                <tr>
                    <th>Grupo / Aula</th>
                    <td>Grupo {{ $boleta['grupo'] }} — Aula {{ $boleta['aula'] }}</td>
                </tr>
                <tr>
                    <th>Horario</th>
                    <td>{{ $boleta['horario'] }}</td>
                </tr>
                <tr>
                    <th>Método / Comprobante</th>
                    <td>{{ strtoupper($boleta['metodo']) }} — {{ $boleta['numero_comprobante'] ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Monto pagado</th>
                    <td class="monto">Bs. {{ number_format((float) $boleta['monto'], 2) }}</td>
                </tr>
            </table>

            <p style="font-size: 10px; color: #4A4A4A; margin-top: 12px;">
                Presentar esta boleta junto con su cédula de identidad al inicio de clases para la validación de asistencia.
            </p>
        </div>
    </div>

    <div class="footer">
        Documento generado electrónicamente por el sistema de Formación Continua de la UMSS. Estado de la inscripción: {{ $boleta['estado'] }}.
    </div>
</body>
</html>
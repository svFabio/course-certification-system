<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px;">
        <h1 style="color: #0E2E5F; font-size: 20px; margin-bottom: 20px;">Pago confirmado</h1>

        <p style="color: #4A4A4A; font-size: 14px; line-height: 1.6;">
            Estimado/a {{ $payment->preinscription->nombres }} {{ $payment->preinscription->apellido_paterno }},
        </p>

        <p style="color: #4A4A4A; font-size: 14px; line-height: 1.6;">
            Su pago ha sido verificado exitosamente. Su inscripcion al curso esta confirmada.
        </p>

        <p style="margin: 20px 0;">
            <a href="{{ URL::signedRoute('boleta.descargar', ['preinscription' => $payment->preinscription_id]) }}"
               style="background-color: #0E2E5F; color: #FFFFFF; padding: 12px 24px; border-radius: 6px; text-decoration: none; display: inline-block; font-size: 14px;">
                Descargar boleta de inscripcion
            </a>
        </p>

        <div style="background: #F5F5F5; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 5px 0; font-size: 13px; color: #4A4A4A;">
                <strong>Curso:</strong> {{ $payment->preinscription->group->course->nombre }}
            </p>
            <p style="margin: 5px 0; font-size: 13px; color: #4A4A4A;">
                <strong>Grupo:</strong> {{ $payment->preinscription->group->nombre }}
            </p>
            <p style="margin: 5px 0; font-size: 13px; color: #4A4A4A;">
                <strong>Monto pagado:</strong> Bs. {{ number_format($payment->monto, 2) }}
            </p>
            <p style="margin: 5px 0; font-size: 13px; color: #4A4A4A;">
                <strong>Metodo:</strong> {{ ucfirst($payment->metodo instanceof \BackedEnum ? $payment->metodo->value : (string) $payment->metodo) }}
            </p>
        </div>

        <p style="color: #4A4A4A; font-size: 14px; line-height: 1.6;">
            Recibirá notificaciones sobre el inicio del curso y la programacion de sesiones.
        </p>

        <hr style="border: none; border-top: 1px solid #F5F5F5; margin: 20px 0;">

        <p style="color: #4A4A4A; font-size: 11px; text-align: center;">
            Universidad Mayor de San Simon — Cursos
        </p>
    </div>
</body>
</html>

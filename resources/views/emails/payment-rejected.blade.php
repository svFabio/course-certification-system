<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-umss-gray-100 p-5 font-sans">
    <div class="max-w-xl mx-auto bg-white rounded-lg p-8">
        <h1 class="text-umss-red text-xl font-bold mb-5">Pago rechazado</h1>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Estimado/a {{ $payment->preinscription?->nombres }} {{ $payment->preinscription?->apellido_paterno }},
        </p>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Su comprobante de pago no pudo ser validado y la preinscripción quedó rechazada. El cupo fue liberado.
        </p>

        <div class="bg-umss-gray-100 rounded-md p-4 my-5">
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Curso:</strong> {{ $payment->preinscription?->group?->course?->nombre ?? '—' }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Grupo:</strong> {{ $payment->preinscription?->group?->nombre ?? '—' }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Monto:</strong> Bs. {{ number_format((float) $payment->monto, 2) }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Motivo del rechazo:</strong> {{ $payment->motivo_rechazo ?? '—' }}
            </p>
        </div>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Si considera que se trata de un error, acérquese a caja o contacte a la coordinación del curso para regularizar su situación.
        </p>

        <hr class="border-t border-umss-gray-200 my-5">

        <p class="text-umss-gray-700 text-xs text-center">
            Universidad Mayor de San Simón — Cursos
        </p>
    </div>
</body>
</html>

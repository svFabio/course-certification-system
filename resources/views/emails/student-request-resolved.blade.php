<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-umss-gray-100 p-5 font-sans">
    @php($aprobada = $studentRequest->estado === \App\Enums\StudentRequestStatus::APROBADA)
    <div class="max-w-xl mx-auto bg-white rounded-lg p-8">
        <h1 class="{{ $aprobada ? 'text-umss-green' : 'text-umss-red' }} text-xl font-bold mb-5">
            {{ $aprobada ? 'Solicitud aprobada' : 'Solicitud rechazada' }}
        </h1>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Estimado/a {{ $studentRequest->preinscription?->nombres }} {{ $studentRequest->preinscription?->apellido_paterno }},
        </p>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            @if ($aprobada)
                Su solicitud fue aprobada. Los cambios ya se encuentran aplicados en su inscripción.
            @else
                Su solicitud no pudo ser aprobada. A continuación encontrará el motivo indicado por la coordinación.
            @endif
        </p>

        <div class="bg-umss-gray-100 rounded-md p-4 my-5">
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Curso:</strong> {{ $studentRequest->preinscription?->group?->course?->nombre ?? '—' }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Tipo de solicitud:</strong> {{ $studentRequest->tipo?->getLabel() ?? '—' }}
            </p>
            @if ($studentRequest->tipo === \App\Enums\StudentRequestType::CAMBIO_GRUPO && $studentRequest->destinationGroup)
                <p class="my-1 text-xs text-umss-gray-700">
                    <strong>Grupo de destino:</strong> {{ $studentRequest->destinationGroup->nombre }}
                </p>
            @endif
            @unless ($aprobada)
                <p class="my-1 text-xs text-umss-gray-700">
                    <strong>Motivo del rechazo:</strong> {{ $studentRequest->motivo_rechazo ?? '—' }}
                </p>
            @endunless
        </div>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Si tiene alguna duda, contacte a la coordinación del curso.
        </p>

        <hr class="border-t border-umss-gray-200 my-5">

        <p class="text-umss-gray-700 text-xs text-center">
            Universidad Mayor de San Simón — Cursos
        </p>
    </div>
</body>
</html>

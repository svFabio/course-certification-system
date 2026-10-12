<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-umss-gray-100 p-5 font-sans">
    <div class="max-w-xl mx-auto bg-white rounded-lg p-8">
        <h1 class="text-umss-navy text-xl font-bold mb-5">Solicitud recibida</h1>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Estimado/a {{ $studentRequest->preinscription?->nombres }} {{ $studentRequest->preinscription?->apellido_paterno }},
        </p>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Hemos registrado su solicitud. La coordinación del curso la revisará y le comunicará la decisión por este mismo medio.
        </p>

        <div class="bg-umss-gray-100 rounded-md p-4 my-5">
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Curso:</strong> {{ $studentRequest->preinscription?->group?->course?->nombre ?? '—' }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Tipo de solicitud:</strong> {{ $studentRequest->tipo?->getLabel() ?? '—' }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Motivo:</strong> {{ $studentRequest->motivo }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Estado:</strong> {{ $studentRequest->estado?->getLabel() ?? '—' }}
            </p>
        </div>

        <hr class="border-t border-umss-gray-200 my-5">

        <p class="text-umss-gray-700 text-xs text-center">
            Universidad Mayor de San Simón — Cursos
        </p>
    </div>
</body>
</html>

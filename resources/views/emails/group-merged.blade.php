<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-umss-gray-100 p-5 font-sans">
    <div class="max-w-xl mx-auto bg-white rounded-lg p-8">
        <h1 class="text-umss-navy text-xl font-bold mb-5">Preinscripción trasladada</h1>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Hola {{ $studentNombres }}, tu preinscripción en el curso {{ $courseNombre }} fue trasladada del grupo {{ $originNombre }} al grupo {{ $destinationNombre }}. Conserva tu comprobante de pago.
        </p>

        <div class="bg-umss-gray-100 rounded-md p-4 my-5">
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Curso:</strong> {{ $courseNombre }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Grupo de origen:</strong> {{ $originNombre }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Grupo de destino:</strong> {{ $destinationNombre }}
            </p>
        </div>

        <hr class="border-t border-umss-gray-200 my-5">

        <p class="text-umss-gray-700 text-xs text-center">
            Universidad Mayor de San Simón — Cursos
        </p>
    </div>
</body>
</html>

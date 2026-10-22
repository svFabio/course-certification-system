<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-umss-gray-100 p-5 font-sans">
    <div class="max-w-xl mx-auto bg-white rounded-lg p-8">
        <h1 class="text-umss-navy text-xl font-bold mb-5">Cupo disponible</h1>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Estimado/a interesado/a,
        </p>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Le informamos que el curso en el que registró su interés ya cuenta con cupo disponible o un nuevo grupo habilitado.
        </p>

        <div class="bg-umss-gray-100 rounded-md p-4 my-5">
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Curso:</strong> {{ $course->nombre }}
            </p>
            <p class="my-1 text-xs text-umss-gray-700">
                <strong>Periodo:</strong> {{ $course->periodo ?? '—' }}
            </p>
        </div>

        <p class="text-umss-gray-700 text-sm leading-relaxed">
            Puede completar su preinscripción ingresando al catálogo público de Formación Continua. Le recomendamos hacerlo a la brevedad, ya que los cupos son limitados.
        </p>

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

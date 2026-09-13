<x-app-layout>
    <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white rounded-lg p-12 mb-8">
        <h1 class="text-4xl font-bold mb-4">Formación Continua con Certificación Digital Verificable</h1>
        <p class="text-xl text-blue-100 mb-8">Fortalece tus competencias con cursos certificados por la UMSS.</p>
        <div class="flex gap-4">
            <a href="{{ route('catalogo') }}"
                class="bg-white text-blue-600 px-6 py-3 rounded-lg font-medium hover:bg-blue-50 transition">
                Ver Catálogo
            </a>
            <a href="{{ route('verificar-certificado') }}"
                class="border border-white text-white px-6 py-3 rounded-lg font-medium hover:bg-white/10 transition">
                Verificar Certificado
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-blue-600 text-3xl mb-2">📚</div>
            <h3 class="font-bold text-lg mb-2">Cursos Destacados</h3>
            <p class="text-gray-600">Programas de formación en tecnología, gestión y más áreas.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-blue-600 text-3xl mb-2">✅</div>
            <h3 class="font-bold text-lg mb-2">Certificación Verificable</h3>
            <p class="text-gray-600">Certificados digitales con código QR verificable en línea.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6">
            <div class="text-blue-600 text-3xl mb-2">📍</div>
            <h3 class="font-bold text-lg mb-2">Asistencia Geolocalizada</h3>
            <p class="text-gray-600">Registro de asistencia mediante código QR y geolocalización.</p>
        </div>
    </div>
</x-app-layout>

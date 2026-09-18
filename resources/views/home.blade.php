<x-app-layout>
    <div class="bg-gradient-to-br from-umss-navy to-umss-navy-dark text-white rounded-lg p-12 mb-8 shadow-sm">
        <h1 class="text-4xl font-bold mb-4 font-sans">Cursos con Certificación Digital Verificable</h1>
        <p class="text-xl text-umss-gray-100 mb-8">Fortalece tus competencias con cursos certificados por la UMSS.</p>
        <div class="flex gap-4">
            <a href="{{ route('home') }}"
                class="bg-umss-white text-umss-navy px-6 py-3 rounded-lg font-medium hover:bg-umss-gray-100 transition">
                Ver Catálogo
            </a>
            <a href="{{ route('certificado.verificar') }}"
                class="border border-white text-white px-6 py-3 rounded-lg font-medium hover:bg-white/10 transition">
                Verificar Certificado
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white shadow rounded-lg p-6 border border-gray-100">
            <h3 class="font-bold text-lg mb-2 text-umss-navy">Cursos Destacados</h3>
            <p class="text-umss-gray-700">Programas de formación en gestión y áreas estratégicas.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 border border-gray-100">
            <h3 class="font-bold text-lg mb-2 text-umss-navy">Certificación Verificable</h3>
            <p class="text-umss-gray-700">Certificados digitales con código QR verificable en línea.</p>
        </div>
        <div class="bg-white shadow rounded-lg p-6 border border-gray-100">
            <h3 class="font-bold text-lg mb-2 text-umss-navy">Asistencia Geolocalizada</h3>
            <p class="text-umss-gray-700">Registro de asistencia mediante código QR y geolocalización.</p>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <div class="relative overflow-hidden bg-gradient-to-br from-umss-navy via-umss-navy to-umss-navy-dark text-white rounded-2xl p-10 sm:p-14 mb-10 shadow-lg border border-umss-navy-dark/50">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/10 text-umss-gray-100 rounded-full mb-4 backdrop-blur-sm border border-white/20">
                Formación Continua UMSS
            </span>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight mb-4 font-sans leading-tight">
                Cursos con Certificación Digital Verificable
            </h1>
            <p class="text-base sm:text-lg text-umss-gray-100 mb-8 leading-relaxed">
                Fortalece tus competencias profesionales y académicas con programas certificados oficialmente por la Universidad Mayor de San Simón.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('home') }}"
                    class="btn-primary !h-11 !px-6 !bg-white !text-umss-navy hover:!bg-umss-gray-100 font-semibold shadow-md transition">
                    Ver Catálogo de Cursos
                </a>
                <a href="{{ route('certificado.verificar') }}"
                    class="btn-secondary !h-11 !px-6 !bg-transparent !border-white/40 !text-white hover:!bg-white/10 font-semibold transition">
                    Verificar Certificado
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="card-umss p-6 bg-white border border-umss-gray-100 hover:border-umss-navy/30 transition shadow-sm hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-umss-navy/10 flex items-center justify-center text-umss-navy mb-4 font-bold text-lg">
                📚
            </div>
            <h3 class="font-bold text-base mb-2 text-umss-navy">Cursos Actualizados</h3>
            <p class="text-umss-gray-700 text-xs leading-relaxed">Programas de formación en áreas técnicas, científicas y profesionales con docentes calificados.</p>
        </div>
        <div class="card-umss p-6 bg-white border border-umss-gray-100 hover:border-umss-navy/30 transition shadow-sm hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-umss-navy/10 flex items-center justify-center text-umss-navy mb-4 font-bold text-lg">
                🛡️
            </div>
            <h3 class="font-bold text-base mb-2 text-umss-navy">Certificación Digital Segura</h3>
            <p class="text-umss-gray-700 text-xs leading-relaxed">Certificados digitales únicos con código QR para validación instantánea en línea ante cualquier entidad.</p>
        </div>
        <div class="card-umss p-6 bg-white border border-umss-gray-100 hover:border-umss-navy/30 transition shadow-sm hover:shadow-md">
            <div class="w-10 h-10 rounded-lg bg-umss-navy/10 flex items-center justify-center text-umss-navy mb-4 font-bold text-lg">
                📍
            </div>
            <h3 class="font-bold text-base mb-2 text-umss-navy">Asistencia Geolocalizada</h3>
            <p class="text-umss-gray-700 text-xs leading-relaxed">Registro ágil de asistencia presencial mediante escaneo QR y validación perimetral por GPS.</p>
        </div>
    </div>
</x-app-layout>

<template>
  <div class="attendance-scanner card-umss p-8">
    <!-- Idle state -->
    <div v-if="state === 'idle'" class="text-center py-8">
      <p class="text-sm text-[#4A4A4A] mb-6">Escanee el código QR provisto en la sesión y verifique su ubicación geográfica.</p>
      <button @click="startScanning" class="btn-primary !h-12 !px-8 text-base">
        Iniciar Escaneo
      </button>
    </div>

    <!-- Scanning state -->
    <div v-if="state === 'scanning'" class="relative">
      <div id="qr-reader" class="w-full rounded-lg overflow-hidden border border-[#E5E5E5]"></div>
      <p class="text-center mt-4 text-xs font-medium text-[#4A4A4A]">Apunte la cámara al código QR de la sesión</p>
    </div>

    <!-- Loading state -->
    <div v-if="state === 'loading'" class="text-center py-8">
      <div class="animate-spin h-10 w-10 border-4 border-[#0E2E5F] border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-sm font-medium text-[#4A4A4A]">Validando ubicación y registrando asistencia...</p>
    </div>

    <!-- Success state -->
    <div v-if="state === 'success'" class="text-center py-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 text-2xl font-bold mb-4">✓</div>
      <h2 class="text-lg font-semibold text-emerald-800">Asistencia Registrada</h2>
      <p class="mt-2 text-sm text-[#4A4A4A]">Distancia al laboratorio: <strong class="text-[#121212]">{{ result.distance }} metros</strong></p>
      <div class="mt-6">
        <button @click="reset" class="btn-secondary">
          Escanear otro código
        </button>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="state === 'error'" class="text-center py-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-100 text-[#E01D2E] text-2xl font-bold mb-4">✕</div>
      <h2 class="text-lg font-semibold text-[#8B0000]">{{ errorMessage }}</h2>
      <div class="mt-6">
        <button @click="reset" class="btn-secondary">
          Intentar de nuevo
        </button>
      </div>
    </div>

    <!-- Out of range state -->
    <div v-if="state === 'out-of-range'" class="text-center py-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 text-amber-800 text-2xl font-bold mb-4">!</div>
      <h2 class="text-lg font-semibold text-amber-900">Ubicación fuera del radio permitido</h2>
      <p class="mt-2 text-sm text-[#4A4A4A]">Límite permitido: {{ maxDistance }}m. Distancia detectada: <strong class="text-[#121212]">{{ result.distance }}m</strong>.</p>
      <p class="text-xs text-[#4A4A4A] mt-1 bg-amber-50 border border-amber-200 rounded-md p-2 inline-block">El registro fue enviado a revisión manual del docente.</p>
      <div class="mt-6">
        <button @click="reset" class="btn-secondary">
          Intentar de nuevo
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';

const state = ref('idle');
const errorMessage = ref('');
const result = ref({ distance: 0 });
const maxDistance = ref(100);
let html5QrcodeInstance = null;

const startScanning = async () => {
  state.value = 'scanning';
  try {
    html5QrcodeInstance = new Html5Qrcode('qr-reader');
    await html5QrcodeInstance.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      () => {}
    );
  } catch (err) {
    state.value = 'error';
    errorMessage.value = 'No se pudo acceder a la cámara. Verifique los permisos.';
  }
};

const onScanSuccess = async (decodedText) => {
  if (html5QrcodeInstance) {
    await html5QrcodeInstance.stop();
    html5QrcodeInstance.clear();
  }
  state.value = 'loading';

  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
      });
    });

    const response = await fetch('/api/asistencia/registrar', {
      method: 'POST',

      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        session_code: decodedText,
        lat: position.coords.latitude,
        lng: position.coords.longitude,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      result.value = { distance: data.distance_metros };
      maxDistance.value = data.max_distance || 100;
      if (data.status === 'para_revision') {
        state.value = 'out-of-range';
      } else {
        state.value = 'success';
      }
    } else {
      state.value = 'error';
      errorMessage.value = data.message || 'Error al registrar asistencia';
    }
  } catch (err) {
    state.value = 'error';
    errorMessage.value = 'Error al obtener geolocalización o al enviar datos.';
  }
};

const reset = () => {
  state.value = 'idle';
  errorMessage.value = '';
  result.value = { distance: 0 };
};
</script>

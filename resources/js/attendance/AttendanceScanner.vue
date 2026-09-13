<template>
  <div class="attendance-scanner">
    <!-- Idle state -->
    <div v-if="state === 'idle'" class="text-center py-12">
      <button @click="startScanning" class="bg-blue-600 text-white px-6 py-3 rounded-lg text-lg">
        Iniciar Escaneo
      </button>
    </div>

    <!-- Scanning state -->
    <div v-if="state === 'scanning'" class="relative">
      <div id="qr-reader" class="w-full"></div>
      <p class="text-center mt-4 text-gray-600">Apunte la cámara al código QR de la sesión</p>
    </div>

    <!-- Loading state -->
    <div v-if="state === 'loading'" class="text-center py-12">
      <div class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-gray-600">Registrando asistencia...</p>
    </div>

    <!-- Success state -->
    <div v-if="state === 'success'" class="text-center py-12">
      <div class="text-green-600 text-6xl mb-4">✓</div>
      <h2 class="text-xl font-bold text-green-700">Asistencia Registrada</h2>
      <p class="mt-2 text-gray-600">Distancia: {{ result.distance }} metros</p>
      <button @click="reset" class="mt-6 bg-gray-600 text-white px-4 py-2 rounded">
        Escanear otro código
      </button>
    </div>

    <!-- Error state -->
    <div v-if="state === 'error'" class="text-center py-12">
      <div class="text-red-600 text-6xl mb-4">✗</div>
      <h2 class="text-xl font-bold text-red-700">{{ errorMessage }}</h2>
      <button @click="reset" class="mt-6 bg-gray-600 text-white px-4 py-2 rounded">
        Intentar de nuevo
      </button>
    </div>

    <!-- Out of range state -->
    <div v-if="state === 'out-of-range'" class="text-center py-12">
      <div class="text-yellow-600 text-6xl mb-4">⚠</div>
      <h2 class="text-xl font-bold text-yellow-700">Fuera de rango</h2>
      <p class="mt-2 text-gray-600">Debe estar dentro de {{ maxDistance }}m del laboratorio</p>
      <p class="text-gray-600">Distancia detectada: {{ result.distance }} metros</p>
      <p class="text-sm text-gray-500 mt-2">Su registro será enviado para revisión.</p>
      <button @click="reset" class="mt-6 bg-gray-600 text-white px-4 py-2 rounded">
        Intentar de nuevo
      </button>
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

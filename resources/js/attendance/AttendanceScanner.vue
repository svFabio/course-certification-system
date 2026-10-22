<template>
  <div class="attendance-scanner card-umss p-8">
    <!-- Idle state -->
    <div v-if="state === 'idle'" class="text-center py-8">
      <p class="text-sm text-umss-gray-700 mb-6">Escanee el código QR provisto en la sesión para registrar su asistencia.</p>
      <button @click="startScanning" class="btn-primary !h-12 !px-8 text-base">
        Iniciar Escaneo
      </button>
    </div>

    <!-- Scanning state -->
    <div v-if="state === 'scanning'" class="relative">
      <div id="qr-reader" class="w-full rounded-lg overflow-hidden border border-umss-gray-200"></div>
      <p class="text-center mt-4 text-xs font-medium text-umss-gray-700">Apunte la cámara al código QR de la sesión</p>
    </div>

    <!-- Loading state -->
    <div v-if="state === 'loading'" class="text-center py-8">
      <div class="animate-spin h-10 w-10 border-4 border-umss-navy border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-sm font-medium text-umss-gray-700">Registrando asistencia...</p>
    </div>

    <!-- Success state -->
    <div v-if="state === 'success'" class="text-center py-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-umss-green-light text-umss-green-dark text-2xl font-bold mb-4">✓</div>
      <h2 class="text-lg font-semibold text-umss-green-dark">Asistencia Registrada</h2>
      <p class="mt-2 text-sm text-umss-gray-700">Su asistencia a la sesión fue confirmada correctamente.</p>
      <div class="mt-6">
        <button @click="reset" class="btn-secondary">
          Escanear otro código
        </button>
      </div>
    </div>

    <!-- Error state -->
    <div v-if="state === 'error'" class="text-center py-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-umss-red/10 text-umss-red text-2xl font-bold mb-4">✕</div>
      <h2 class="text-lg font-semibold text-umss-red-dark">{{ errorMessage }}</h2>
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
    const response = await fetch('/api/asistencia/registrar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        session_code: decodedText,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      state.value = 'success';
    } else {
      state.value = 'error';
      errorMessage.value = data.message || 'Error al registrar asistencia';
    }
  } catch (err) {
    state.value = 'error';
    errorMessage.value = 'Error al procesar el código o al enviar datos.';
  }
};

const reset = () => {
  state.value = 'idle';
  errorMessage.value = '';
};
</script>

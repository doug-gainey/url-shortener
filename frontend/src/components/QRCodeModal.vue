<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
  >
    <div class="rounded-3xl bg-white p-8 shadow-lg">
      <div class="mb-6 flex items-center justify-between">
        <h3 class="text-lg font-semibold">QR Code</h3>
        <button
          @click="close"
          class="text-2xl font-bold text-slate-400 hover:text-slate-600"
        >
          ×
        </button>
      </div>

      <div class="mb-6 flex justify-center">
        <QRCode :value="shortUrl" :size="256" level="H" />
      </div>

      <p class="mb-4 text-center text-sm text-slate-600">{{ shortUrl }}</p>

      <button
        @click="downloadQR"
        class="w-full rounded-full bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
      >
        Download QR Code
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import QRCode from "qrcode.vue";

interface Props {
  isOpen: boolean;
  shortUrl: string;
}

defineProps<Props>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

const close = () => {
  emit("close");
};

const downloadQR = () => {
  const qrCanvas = document.querySelector("canvas");
  if (!qrCanvas) return;

  const link = document.createElement("a");
  link.href = qrCanvas.toDataURL("image/png");
  link.download = `qr-code-${Date.now()}.png`;
  link.click();
};
</script>

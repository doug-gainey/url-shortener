<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click="closeModal"
  >
    <div class="w-full max-w-lg rounded-3xl bg-white p-8 shadow-lg" @click.stop>
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900">Link Statistics</h2>
        <p class="mt-2 text-sm text-slate-500">{{ stats?.short_code }}</p>
      </div>

      <div class="mb-6 space-y-4">
        <div
          class="flex items-center justify-between rounded-xl bg-slate-50 p-4"
        >
          <span class="text-sm font-medium text-slate-600">Total Clicks</span>
          <span class="text-2xl font-bold text-sky-600">{{
            stats?.clicks || 0
          }}</span>
        </div>

        <div
          class="flex items-center justify-between rounded-xl bg-slate-50 p-4"
        >
          <span class="text-sm font-medium text-slate-600">Created</span>
          <span class="text-sm text-slate-900">{{
            formatDate(stats?.created_at)
          }}</span>
        </div>

        <div
          class="flex items-center justify-between rounded-xl bg-slate-50 p-4"
        >
          <span class="text-sm font-medium text-slate-600">Last Clicked</span>
          <span class="text-sm text-slate-900">{{
            stats?.last_clicked_at ? formatDate(stats.last_clicked_at) : "Never"
          }}</span>
        </div>

        <div
          class="flex items-center justify-between rounded-xl bg-slate-50 p-4"
        >
          <span class="text-sm font-medium text-slate-600">Expires</span>
          <span class="text-sm text-slate-900">{{
            stats?.expires_at ? formatDate(stats.expires_at) : "Never"
          }}</span>
        </div>

        <div class="rounded-xl bg-slate-50 p-4">
          <span class="text-sm font-medium text-slate-600"
            >Destination URL</span
          >
          <p class="mt-2 break-all text-sm text-slate-900">
            {{ stats?.original_url }}
          </p>
        </div>

        <div v-if="stats?.custom_alias" class="rounded-xl bg-slate-50 p-4">
          <span class="text-sm font-medium text-slate-600">Custom Alias</span>
          <p class="mt-2 text-sm text-slate-900">{{ stats.custom_alias }}</p>
        </div>
      </div>

      <button
        @click="closeModal"
        class="w-full rounded-full bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-800"
      >
        Close
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface StatsData {
  short_code: string;
  original_url: string;
  custom_alias: string | null;
  clicks: number;
  last_clicked_at: string | null;
  created_at: string;
  expires_at: string | null;
}

defineProps<{
  isOpen: boolean;
  stats: StatsData | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

const closeModal = () => {
  emit("close");
};

const formatDate = (value: string | null) => {
  if (!value) return "N/A";
  return new Date(value.replace(" ", "T") + "Z").toLocaleString();
};
</script>

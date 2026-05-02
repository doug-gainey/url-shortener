<template>
  <div>
    <div
      v-if="loading"
      class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-slate-500"
    >
      Loading links...
    </div>

    <div
      v-else-if="links.length === 0"
      class="rounded-3xl border border-slate-200 bg-slate-50 p-8 text-center text-slate-500"
    >
      No links yet. Create one to get started.
    </div>

    <table
      v-else
      class="min-w-full table-auto border-separate border-spacing-y-3 text-left"
    >
      <thead>
        <tr>
          <th class="px-4 py-3 text-sm font-semibold text-slate-500">
            Short code
          </th>
          <th class="px-4 py-3 text-sm font-semibold text-slate-500">
            Destination
          </th>
          <th class="px-4 py-3 text-sm font-semibold text-slate-500">Clicks</th>
          <th class="px-4 py-3 text-sm font-semibold text-slate-500">
            Created
          </th>
          <th class="px-4 py-3 text-sm font-semibold text-slate-500">Action</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="link in links"
          :key="link.short_code"
          class="rounded-3xl bg-white shadow-sm"
        >
          <td
            class="whitespace-nowrap px-4 py-4 text-sm font-medium text-slate-900"
          >
            <a
              :href="shortUrl(link.short_code)"
              target="_blank"
              rel="noreferrer"
              class="text-sky-600 hover:underline"
              @click="incrementClick(link)"
              >{{ link.short_code }}</a
            >
          </td>
          <td class="px-4 py-4 text-sm text-slate-600">
            {{ link.original_url }}
          </td>
          <td class="px-4 py-4 text-sm text-slate-600">{{ link.clicks }}</td>
          <td class="px-4 py-4 text-sm text-slate-600">
            {{ formatDate(link.created_at) }}
          </td>
          <td class="px-4 py-4 text-sm text-slate-600">
            <div class="flex gap-2">
              <button
                @click="openStats(link)"
                class="rounded-full bg-green-500 px-3 py-2 text-white transition hover:bg-green-600"
              >
                Stats
              </button>
              <button
                @click="openQR(link)"
                class="rounded-full bg-blue-500 px-3 py-2 text-white transition hover:bg-blue-600"
              >
                QR
              </button>
              <button
                @click="$emit('delete', link.short_code)"
                class="rounded-full bg-rose-600 px-4 py-2 text-white transition hover:bg-rose-700"
              >
                Delete
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <QRCodeModal
      :is-open="showQRModal"
      :short-url="selectedQRUrl"
      @close="showQRModal = false"
    />

    <StatsModal
      :is-open="showStatsModal"
      :stats="selectedStats"
      @close="showStatsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import QRCodeModal from "./QRCodeModal.vue";
import StatsModal from "./StatsModal.vue";
import { api } from "../api/client";

interface LinkItem {
  short_code: string;
  original_url: string;
  clicks: number;
  created_at: string;
}

interface StatsData {
  short_code: string;
  original_url: string;
  custom_alias: string | null;
  clicks: number;
  last_clicked_at: string | null;
  created_at: string;
  expires_at: string | null;
}

const props = defineProps<{
  links: LinkItem[];
  loading: boolean;
  appBaseUrl: string;
}>();

const emit = defineEmits<{
  (e: "delete", code: string): void;
}>();

const showQRModal = ref(false);
const selectedQRUrl = ref("");
const showStatsModal = ref(false);
const selectedStats = ref<StatsData | null>(null);

const incrementClick = (link: LinkItem) => {
  link.clicks++;
};

const shortUrl = (code: string) => {
  const base = props.appBaseUrl?.trim() || window.location.origin;
  return `${base.replace(/\/+$/, "")}/${code}`;
};

const openQR = (link: LinkItem) => {
  selectedQRUrl.value = shortUrl(link.short_code);
  showQRModal.value = true;
};

const openStats = async (link: LinkItem) => {
  try {
    const response = await api.get(`/links/${link.short_code}/stats`);
    console.log(response);
    if (response && response.data.data) {
      selectedStats.value = response.data.data;
      showStatsModal.value = true;
    }
  } catch (error) {
    console.error("Failed to fetch stats:", error);
  }
};

const formatDate = (value: string) =>
  new Date(value.replace(" ", "T") + "Z").toLocaleString();
</script>

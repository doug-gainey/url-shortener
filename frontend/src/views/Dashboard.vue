<template>
  <div class="space-y-8">
    <section class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200">
      <h2 class="text-xl font-semibold">Create a short link</h2>
      <form @submit.prevent="submit" class="mt-5 space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700"
            >Destination URL</label
          >
          <input
            v-model="form.original_url"
            type="url"
            placeholder="https://example.com/page"
            class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
            required
          />
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-slate-700"
              >Custom alias (optional)</label
            >
            <input
              v-model="form.custom_alias"
              type="text"
              placeholder="custom-alias"
              class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700"
              >Expires at (optional)</label
            >
            <input
              v-model="form.expires_at"
              type="datetime-local"
              class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
            />
          </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
          <button
            type="submit"
            class="rounded-full bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            Create link
          </button>
          <p v-if="store.error" class="text-sm text-rose-600">
            {{ store.error }}
          </p>
        </div>
      </form>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-sm shadow-slate-200">
      <div
        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h2 class="text-xl font-semibold">Links</h2>
          <p class="mt-1 text-sm text-slate-500">
            Manage your generated short URLs.
          </p>
        </div>
        <button
          class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
          @click="store.fetchLinks"
        >
          Refresh
        </button>
      </div>

      <div class="mt-6">
        <LinkTable
          :links="store.list"
          :loading="store.loading"
          :app-base-url="store.appBaseUrl"
          @delete="store.deleteLink"
        />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useLinksStore } from "../stores/links";
import LinkTable from "../components/LinkTable.vue";

const store = useLinksStore();
const form = ref({
  original_url: "",
  custom_alias: "",
  expires_at: "",
});

const submit = async () => {
  await store.createLink({
    original_url: form.value.original_url,
    custom_alias: form.value.custom_alias || undefined,
    expires_at: form.value.expires_at || undefined,
  });

  if (!store.error) {
    form.value.original_url = "";
    form.value.custom_alias = "";
    form.value.expires_at = "";
  }
};

onMounted(() => {
  store.fetchAppConfig();
  store.fetchLinks();
});
</script>

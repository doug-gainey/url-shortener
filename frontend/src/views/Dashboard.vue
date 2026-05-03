<template>
  <div class="space-y-8">
    <section class="rounded-lg bg-white p-6 shadow-sm shadow-slate-200">
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
            class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
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
              class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700"
              >Expires at (optional)</label
            >
            <input
              v-model="form.expires_at"
              type="datetime-local"
              class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100"
            />
          </div>
        </div>
        <div class="flex items-center gap-3 pt-2">
          <button
            type="submit"
            class="rounded-lg bg-sky-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
          >
            Create link
          </button>
          <p v-if="store.error" class="text-sm text-rose-600">
            {{ store.error }}
          </p>
        </div>
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-2"
        >
          <div
            v-if="showCopyNotification"
            class="flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-emerald-700 text-sm"
          >
            <span>✓</span>
            <span>Link copied to clipboard!</span>
          </div>
        </transition>
      </form>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm shadow-slate-200">
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
          class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
          @click="applyFilters"
        >
          Refresh
        </button>
      </div>

      <div class="mt-6 space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-600">Status:</label>
            <select
              v-model="filterStatus"
              @change="applyFilters"
              class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none"
            >
              <option value="">All</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-600">Sort by:</label>
            <select
              v-model="sortBy"
              @change="applyFilters"
              class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-sky-500 focus:outline-none"
            >
              <option value="created_at">Created (Newest)</option>
              <option value="created_at_asc">Created (Oldest)</option>
              <option value="clicks">Clicks (Most)</option>
              <option value="expires_at">Expires</option>
            </select>
          </div>
        </div>

        <LinkTable
          :links="store.list"
          :loading="store.loading"
          :app-base-url="store.appBaseUrl"
          @delete="store.deleteLink"
        />

        <div
          v-if="store.pagination"
          class="flex items-center justify-between border-t border-slate-200 pt-4"
        >
          <div class="text-sm text-slate-600">
            Showing {{ store.pagination.offset + 1 }} to
            {{
              Math.min(
                store.pagination.offset + store.pagination.limit,
                store.pagination.total,
              )
            }}
            of {{ store.pagination.total }} links
          </div>
          <div class="flex gap-2">
            <button
              @click="previousPage"
              :disabled="store.pagination.offset === 0"
              class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition disabled:opacity-50 hover:bg-slate-50 enabled:hover:bg-slate-100"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="!store.pagination.has_more"
              class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition disabled:opacity-50 hover:bg-slate-50 enabled:hover:bg-slate-100"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useLinksStore } from "../stores/links";
import LinkTable from "../components/LinkTable.vue";
import { copyToClipboard } from "../utils/clipboard";

const store = useLinksStore();
const form = ref({
  original_url: "",
  custom_alias: "",
  expires_at: "",
});

const filterStatus = ref("");
const sortBy = ref("created_at");
const showCopyNotification = ref(false);

const applyFilters = async () => {
  const sort = sortBy.value === "created_at_asc" ? "created_at" : sortBy.value;
  const order = sortBy.value === "created_at_asc" ? "ASC" : "DESC";
  await store.fetchLinks({
    status: filterStatus.value || undefined,
    sortBy: sort,
    sortOrder: order,
  });
};

const nextPage = async () => {
  if (store.pagination && store.pagination.has_more) {
    const sort =
      sortBy.value === "created_at_asc" ? "created_at" : sortBy.value;
    const order = sortBy.value === "created_at_asc" ? "ASC" : "DESC";
    await store.fetchLinks({
      offset: store.pagination.offset + store.pagination.limit,
      status: filterStatus.value || undefined,
      sortBy: sort,
      sortOrder: order,
    });
  }
};

const previousPage = async () => {
  if (store.pagination && store.pagination.offset > 0) {
    const newOffset = Math.max(
      0,
      store.pagination.offset - store.pagination.limit,
    );
    const sort =
      sortBy.value === "created_at_asc" ? "created_at" : sortBy.value;
    const order = sortBy.value === "created_at_asc" ? "ASC" : "DESC";
    await store.fetchLinks({
      offset: newOffset,
      status: filterStatus.value || undefined,
      sortBy: sort,
      sortOrder: order,
    });
  }
};

const submit = async () => {
  await store.createLink({
    original_url: form.value.original_url,
    custom_alias: form.value.custom_alias || undefined,
    expires_at: form.value.expires_at || undefined,
  });

  if (!store.error) {
    // Copy new link to clipboard if available
    try {
      const newLink = store.list[0];
      if (newLink && store.appBaseUrl) {
        const fullUrl = `${store.appBaseUrl.replace(/\/+$/, "")}/${newLink.short_code}`;
        await copyToClipboard(fullUrl);
        showCopyNotification.value = true;
        setTimeout(() => {
          showCopyNotification.value = false;
        }, 2000);
      }
    } catch (error) {
      console.error("Failed to copy link:", error);
    }

    // Reset form and filters
    form.value.original_url = "";
    form.value.custom_alias = "";
    form.value.expires_at = "";
    filterStatus.value = "";
    sortBy.value = "created_at";

    // Refresh link list from first page
    await store.fetchLinks({
      limit: 20,
      offset: 0,
      status: undefined,
      sortBy: "created_at",
      sortOrder: "DESC",
    });
  }
};

onMounted(() => {
  store.fetchAppConfig();
  store.fetchLinks();
});
</script>

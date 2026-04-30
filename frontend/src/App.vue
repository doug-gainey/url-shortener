<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-6xl px-4 py-6">
      <header class="mb-8">
        <h1 class="text-3xl font-semibold text-slate-900">URL Shortener</h1>
        <p class="mt-2 text-slate-600">
          Create and manage short links backed by Redis and SQLite.
        </p>
      </header>
      <router-view />
    </main>
  </div>
</template>

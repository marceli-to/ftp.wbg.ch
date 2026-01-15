<template>
  <div class="min-h-screen bg-cover bg-center bg-no-repeat flex flex-col justify-center bg-wbg">
    <div class="max-w-xl mx-auto w-full min-h-300">
      <div class="bg-white pt-30 px-30 pb-20 bg-g">
        <!-- Header -->
        <div class="flex items-center justify-between mb-32">
            <h1 class="font-medium">
              <template v-if="$route.name == 'upload'">
                Upload
              </template>
              <template v-else>
                Dateien
              </template>
            </h1>
            <router-link
                v-if="$route.name === 'upload'"
                to="/"
                class="hover:text-crimson transition-colors">
                Dateien
            </router-link>
            <router-link
                v-else
                to="/upload"
                class="hover:text-crimson transition-colors">
                Datei hochladen
            </router-link>
        </div>

        <!-- Router View -->
        <router-view />

        <!-- Footer -->
        <footer class="mt-32 text-crimson uppercase">
          <p>WBG AG – Visuelle Kommunikation</p>
          <p>Binzstrasse 39, CH-8045 Zürich, +41 44 269 43 43, mail@wbg.ch</p>
        </footer>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div
    v-if="toast"
    class="fixed top-16 right-16 bg-green-700 text-white px-12 py-8 transition-opacity duration-300">
    {{ toast }}
  </div>
</template>

<script setup>
import { ref, provide } from 'vue';

const toast = ref(null);

const showToast = (message) => {
    toast.value = message;
    setTimeout(() => {
        toast.value = null;
    }, 3000);
};

provide('showToast', showToast);

const logout = async () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    await fetch('/logout', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    });

    window.location.href = '/login';
};
</script>

<template>
  <div>

    <div v-if="loading" class="text-center py-32">
      Laden...
    </div>

    <div v-else-if="files.length === 0" class="text-center py-32">
      Keine Dateien vorhanden
    </div>

    <div v-else >
      <div
        v-for="file in files"
        :key="file.id"
        class="flex items-center justify-between gap-16 py-12 border-b border-stone-200 first:border-t">

        <div class="flex-1 min-w-0">
          <a :href="file.download_url" class="font-medium truncate hover:text-crimson transition-colors">
            {{ file.original_name }}
          </a>
          <div class="flex gap-8 mt-4">
            <span class="px-8 py-2 bg-stone-100 text-stone-600 text-sm">
              {{ file.formatted_size }}
            </span>
          </div>
        </div>

        <div class="flex items-center shrink-0 gap-8">
          <!-- Copy Link -->
          <button
            @click="copyLink(file.download_url)"
            class="p-4 cursor-pointer text-stone-600 hover:text-crimson transition-colors"
            title="Link kopieren">
            <PhCopy :size="20" />
          </button>

          <!-- Delete -->
          <button
            @click="deleteFile(file)"
            class="p-4 cursor-pointer text-stone-600 hover:text-crimson transition-colors"
            title="Löschen">
            <PhTrash :size="20" />
          </button>
        </div>
      </div>
    </div>
    
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { PhCopy, PhTrash } from '@phosphor-icons/vue';

const files = ref([]);
const loading = ref(true);
const showToast = inject('showToast');

const fetchFiles = async () => {
    try {
        const response = await fetch('/api/files', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
            },
        });
        files.value = await response.json();
    } catch (error) {
        console.error('Error fetching files:', error);
    } finally {
        loading.value = false;
    }
};

const copyLink = async (url) => {
    try {
        await navigator.clipboard.writeText(url);
        showToast('Link kopiert');
    } catch (error) {
        console.error('Error copying link:', error);
    }
};

const deleteFile = async (file) => {
    if (!confirm('Datei wirklich löschen?')) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const response = await fetch(`/api/files/${file.id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            files.value = files.value.filter(f => f.id !== file.id);
            showToast('Datei gelöscht');
        }
    } catch (error) {
        console.error('Error deleting file:', error);
    }
};

onMounted(fetchFiles);
</script>

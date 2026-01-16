<template>
    <div>
        <file-pond
            ref="pond"
            name="file"
            :server="serverConfig"
            :allow-multiple="false"
            :max-files="1"
            :chunk-uploads="chunkUploads"
            :chunk-size="chunkSize"
            :chunk-force="chunkForce"
            label-idle="Datei hierher ziehen oder <span class='filepond--label-action'>durchsuchen</span>"
            @processfile="onProcessFile"
            @error="onError"
        />
    </div>
</template>

<script>
import vueFilePond from 'vue-filepond';
import 'filepond/dist/filepond.min.css';

import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginFileValidateSize);

export default {
    components: {
        FilePond,
    },
};
</script>

<script setup>
import { inject, computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const showToast = inject('showToast');

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const serverConfig = computed(() => ({
    process: '/api/chunks',
    revert: '/api/chunks',
    patch: '/api/chunks/',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
    },
}));

// Chunk upload settings
const chunkUploads = true;
const chunkSize = 5000000; // 5MB chunks
const chunkForce = true;

const onProcessFile = () => {
    showToast('Datei hochgeladen');
    router.push('/');
};

const onError = (error) => {
    console.error('FilePond error:', error);
    showToast('Fehler beim Hochladen');
};
</script>

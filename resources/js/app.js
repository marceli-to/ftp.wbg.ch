import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import FileList from './pages/FileList.vue';
import FileUpload from './pages/FileUpload.vue';

const routes = [
    { path: '/', name: 'files', component: FileList },
    { path: '/upload', name: 'upload', component: FileUpload },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const app = createApp(App);
app.use(router);
app.mount('#app');

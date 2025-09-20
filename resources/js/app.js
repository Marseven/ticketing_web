import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';

const pinia = createPinia();
const app = createApp(App);

app.use(router);
app.use(pinia);

// Initialiser le store d'authentification
import { useAuthStore } from './stores/auth.js';
app.mount('#app');

// Initialiser l'authentification après le montage
const authStore = useAuthStore();
authStore.initialize();

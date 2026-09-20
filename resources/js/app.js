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

// Loader global : instrumente fetch + axios (instance par défaut) pour que
// tout clic déclenchant un appel réseau affiche la barre/pastille de chargement.
// (Après app.use(pinia) : le store est résolu au moment de chaque appel.)
import { installFetchLoader } from './utils/loaderFetch';
import { installAxiosLoader } from './utils/loaderAxios';
installFetchLoader();
installAxiosLoader();

// Initialiser le store d'authentification
import { useAuthStore } from './stores/auth.js';

app.mount('#app');

// Initialiser l'authentification après le montage
const authStore = useAuthStore();
authStore.initialize();

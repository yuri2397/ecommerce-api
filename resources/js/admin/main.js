import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Aura from '@primeuix/themes/aura';
// PrimeVue CSS
// import 'primevue/resources/themes/lara-light-indigo/theme.css';
// import 'primevue/resources/primevue.min.css';
// import 'primeicons/primeicons.css';

// Composants PrimeVue fréquemment utilisés
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Card from 'primevue/card';
import Toast from 'primevue/toast';

// Création de l'application
const app = createApp(App);

// Utilisation des plugins
app.use(createPinia());
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura
    }
});
app.use(ToastService);
app.use(ConfirmationService);

// Enregistrement des composants globaux
app.component('Button', Button);
app.component('InputText', InputText);
app.component('Card', Card);
app.component('Toast', Toast);

// Configuration Axios pour inclure le token CSRF
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Montage de l'application
app.mount('#admin-app');

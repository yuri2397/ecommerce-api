import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Aura from '@primeuix/themes/aura';

// Composants PrimeVue fréquemment utilisés
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Card from 'primevue/card';
import Toast from 'primevue/toast';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';

// Création de l'application
const app = createApp(App);

// Utilisation des plugins
app.use(createPinia());
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura
    },
    ripple: true,
    // Cette option est importante pour PrimeVue 3+
    linkActiveOptions: {
        // Configure comment PrimeVue détecte les liens actifs
        exact: false
    }
});
app.use(ToastService);
app.use(ConfirmationService);

// Enregistrement des composants globaux
app.component('Button', Button);
app.component('InputText', InputText);
app.component('Card', Card);
app.component('Toast', Toast);
app.component('Password', Password);
app.component('Checkbox', Checkbox);
app.component('Dropdown', Dropdown);
app.component('Calendar', Calendar);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('ColumnGroup', ColumnGroup);

// Configuration Axios pour inclure le token CSRF
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Montage de l'application
app.mount('#admin-app');

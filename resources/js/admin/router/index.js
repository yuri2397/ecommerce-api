import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/auth/Login.vue';
import Dashboard from '../views/dashboard/Dashboard.vue';
import { useAuthStore } from '../store/auth';

const routes = [
    {
        path: '/admin/login',
        name: 'login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/admin',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        // Route par défaut - redirige vers le dashboard
        path: '/admin/:pathMatch(.*)*',
        redirect: { name: 'dashboard' }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Navigation guard pour vérifier l'authentification
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false);

    // Si la route requiert l'authentification et l'utilisateur n'est pas connecté
    if (requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    }
    // Si l'utilisateur est déjà connecté et essaie d'accéder à la page de login
    else if (authStore.isAuthenticated && to.name === 'login') {
        next({ name: 'dashboard' });
    }
    // Dans tous les autres cas, permettre la navigation
    else {
        next();
    }
});

export default router;

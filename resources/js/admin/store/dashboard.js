import { defineStore } from 'pinia';
import axios from 'axios';
import { useAuthStore } from './auth';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        stats: {
            products: 0,
            categories: 0,
            orders: 0,
            users: 0
        },
        loading: false,
        error: null
    }),

    actions: {
        async fetchStats() {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.get('/api/admin/dashboard/stats', {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                this.stats = response.data;
                return this.stats;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du chargement des statistiques';
                console.error('Erreur lors du chargement des statistiques:', error);
                return null;
            } finally {
                this.loading = false;
            }
        }
    }
});

<template>
    <div class="dashboard-layout">
        <AdminHeader />

        <div class="dashboard-container">
            <AdminSidebar />

            <div class="dashboard-content">
                <div class="card">
                    <h1>Tableau de bord</h1>
                    <p>Bienvenue dans le panneau d'administration</p>
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <i class="pi pi-shopping-bag"></i>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.products }}</span>
                                <span class="stat-label">Produits</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <i class="pi pi-list"></i>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.categories }}</span>
                                <span class="stat-label">Catégories</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <i class="pi pi-shopping-cart"></i>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.orders }}</span>
                                <span class="stat-label">Commandes</span>
                            </div>
                        </div>

                        <div class="stat-card">
                            <i class="pi pi-users"></i>
                            <div class="stat-info">
                                <span class="stat-value">{{ stats.users }}</span>
                                <span class="stat-label">Utilisateurs</span>
                            </div>
                        </div>
                    </div>

                    <div class="recent-orders">
                        <h2>Commandes récentes</h2>
                        <!-- Un tableau de commandes récentes serait ici -->
                        <DataTable :value="orders" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
                            :totalRecords="totalRecords" :loading="loading">
                            <Column field="id" header="ID"></Column>
                            <Column field="customer" header="Client"></Column>
                            <Column field="date" header="Date"></Column>
                            <Column field="total" header="Total"></Column>
                        </DataTable>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import AdminHeader from '../../components/layout/AdminHeader.vue';
import AdminSidebar from '../../components/layout/AdminSidebar.vue';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

export default {
    name: 'DashboardView',
    components: {
        AdminHeader,
        AdminSidebar
    },
    setup() {
        const authStore = useAuthStore();
        const stats = ref({
            products: 0,
            categories: 0,
            orders: 0,
            users: 0
        });

        const fetchStats = async () => {
            try {
                // Exemple de requête pour obtenir des statistiques (à adapter à votre API)
                const response = await axios.get('/api/admin/dashboard/stats', {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                stats.value = response.data;
            } catch (error) {
                console.error('Erreur lors de la récupération des statistiques:', error);
                // Valeurs par défaut pour la démonstration
                stats.value = {
                    products: 125,
                    categories: 18,
                    orders: 32,
                    users: 87
                };
            }
        };

        onMounted(() => {
            fetchStats();
        });

        return {
            stats
        };
    }
}
</script>

<style scoped>
.dashboard-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.dashboard-container {
    display: flex;
    flex: 1;
}

.dashboard-content {
    flex: 1;
    padding: 1.5rem;
    background-color: #f5f7f9;
}

.card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.stat-card i {
    font-size: 2.5rem;
    margin-right: 1rem;
    color: #3f51b5;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #3f51b5;
}

.stat-label {
    color: #6c757d;
}

.recent-orders {
    margin-top: 2rem;
}

h1 {
    margin-top: 0;
    color: #3f51b5;
}

h2 {
    color: #495057;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}
</style>

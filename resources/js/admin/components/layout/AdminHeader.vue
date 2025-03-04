<template>
    <header class="admin-header">
        <div class="header-logo">
            <i class="pi pi-shopping-bag"></i>
            <span class="site-name">E-Commerce Admin</span>
        </div>

        <div class="header-actions">
            <Menu ref="menu" :model="items" :popup="true" />
            <Button type="button" @click="toggle" class="user-menu-button" icon="pi pi-user" aria-label="User menu">
                <span class="user-name">{{ userFullName }}</span>
            </Button>
        </div>
    </header>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../store/auth';
import Menu from 'primevue/menu';

export default {
    name: 'AdminHeader',
    components: {
        Menu
    },
    setup() {
        const menu = ref();
        const router = useRouter();
        const authStore = useAuthStore();

        const items = [
            {
                label: 'Profil',
                icon: 'pi pi-user-edit',
                command: () => {
                    // Navigation vers la page de profil (à implémenter)
                    // router.push({ name: 'profile' });
                }
            },
            {
                separator: true
            },
            {
                label: 'Déconnexion',
                icon: 'pi pi-sign-out',
                command: async () => {
                    await authStore.logout();
                    router.push({ name: 'login' });
                }
            }
        ];

        const toggle = (event) => {
            menu.value.toggle(event);
        };

        return {
            menu,
            items,
            toggle,
            userFullName: authStore.userFullName || 'Administrateur'
        };
    }
}
</script>

<style scoped>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #3f51b5;
    color: white;
    padding: 0 1.5rem;
    height: 64px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.header-logo {
    display: flex;
    align-items: center;
    font-size: 1.2rem;
    font-weight: 500;
}

.header-logo i {
    font-size: 1.5rem;
    margin-right: 0.75rem;
}

.header-actions {
    display: flex;
    align-items: center;
}

.user-menu-button {
    background-color: transparent !important;
    border: none !important;
    color: white !important;
    font-weight: normal !important;
}

.user-menu-button:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.user-menu-button:focus {
    box-shadow: none !important;
}

.user-name {
    margin-left: 0.5rem;
}
</style>

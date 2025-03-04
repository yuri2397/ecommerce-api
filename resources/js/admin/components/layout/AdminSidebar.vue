<template>
    <div class="admin-sidebar">
        <div class="sidebar-content">
            <PanelMenu :model="menuItems" class="sidebar-menu" />
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import PanelMenu from 'primevue/panelmenu';

export default {
    name: 'AdminSidebar',
    components: {
        PanelMenu
    },
    setup() {
        const menuItems = ref([
            {
                label: 'Tableau de bord',
                icon: 'pi pi-home',
                to: '/admin'
            },
            {
                label: 'Catalogue',
                icon: 'pi pi-list',
                items: [
                    {
                        label: 'Produits',
                        icon: 'pi pi-shopping-bag',
                        to: '/admin/products'
                    },
                    {
                        label: 'Catégories',
                        icon: 'pi pi-tags',
                        to: '/admin/categories'
                    }
                ]
            },
            {
                label: 'Commandes',
                icon: 'pi pi-shopping-cart',
                to: '/admin/orders'
            },
            {
                label: 'Clients',
                icon: 'pi pi-users',
                to: '/admin/customers'
            },
            {
                label: 'Avis & Commentaires',
                icon: 'pi pi-comments',
                to: '/admin/comments'
            },
            {
                label: 'Paramètres',
                icon: 'pi pi-cog',
                to: '/admin/settings'
            }
        ]);

        return {
            menuItems
        };
    }
}
</script>

<style scoped>
.admin-sidebar {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    overflow-y: auto;
}

.sidebar-content {
    padding: 1rem;
}

.sidebar-menu {
    border: none;
}

/* Personnalisation du PanelMenu de PrimeVue */
:deep(.p-panelmenu .p-panelmenu-header > a) {
    padding: 0.75rem 1rem;
}

:deep(.p-panelmenu .p-panelmenu-content .p-menuitem .p-menuitem-link) {
    padding: 0.75rem 1rem 0.75rem 2rem;
}

:deep(.p-panelmenu .p-panelmenu-header:not(.p-highlight):not(.p-disabled) > a:hover) {
    background-color: #f8f9fa;
    color: #3f51b5;
}

:deep(.p-panelmenu .p-panelmenu-content .p-menuitem .p-menuitem-link:not(.p-disabled):hover) {
    background-color: #f8f9fa;
    color: #3f51b5;
}

:deep(.p-panelmenu .p-panelmenu-header.p-highlight > a) {
    background-color: #ebeffe;
    color: #3f51b5;
    border-color: #d8deff;
}

:deep(.p-panelmenu .p-panelmenu-content .p-menuitem .p-menuitem-link.router-link-active) {
    background-color: #ebeffe;
    color: #3f51b5;
}
</style>

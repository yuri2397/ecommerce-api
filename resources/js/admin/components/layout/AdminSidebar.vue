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
                to: { name: 'dashboard' }  // Utilisez le nom de la route au lieu du chemin
            },
            {
                label: 'Catégories',
                icon: 'pi pi-tags',
                to: { name: 'categories' }  // Nom de la route
            },
            {
                label: 'Catalogue',
                icon: 'pi pi-list',
                items: [
                    {
                        label: 'Produits',
                        icon: 'pi pi-shopping-bag',
                        to: { name: 'products' }  // Nom de la route
                    },
                    {
                        label: 'Catégories',
                        icon: 'pi pi-tags',
                        to: { name: 'categories' }  // Nom de la route
                    }
                ]
            },
            {
                label: 'Ajouter un produit',
                icon: 'pi pi-plus',
                to: { name: 'products.create' }
            },
            {
                label: 'Ajouter une catégorie',
                icon: 'pi pi-plus',
                to: { name: 'categories.create' }
            },
            // Autres menus...
        ]);

        return {
            menuItems
        };
    }
}
</script>

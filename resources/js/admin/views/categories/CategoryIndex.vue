<template>
    <AdminLayout>
        <div class="categories-page">
            <div class="card-header">
                <h1 class="card-title">Gestion des Catégories</h1>
                <Button label="Nouvelle Catégorie" icon="pi pi-plus" class="p-button-success"
                    @click="navigateToCreate" />
            </div>
            <div class="card">

                <div class="filter-bar">
                    <div class="p-inputgroup filter-input">
                        <InputText class="w-50" v-model="filters.search" placeholder="Rechercher une catégorie..." />
                        <Button icon="pi pi-search" label="Rechercher" @click="loadCategories" />
                    </div>

                    <div class="p-inputgroup filter-select">
                        <label for="status-filter d-block">Statut</label>
                        <Dropdown id="status-filter" v-model="filters.is_active" :options="statusOptions"
                            optionLabel="name" optionValue="value" placeholder="Tous les statuts"
                            @change="loadCategories" />
                    </div>

                    <!-- <div class="p-inputgroup filter-select">
                        <label for="parent-filter">Catégorie parente</label>
                        <Dropdown id="parent-filter" v-model="filters.parent_id" :options="parentCategories"
                            optionLabel="name" optionValue="id" placeholder="Toutes les catégories"
                            @change="loadCategories" />
                    </div> -->
                </div>

                <DataTable :value="categories" :loading="loading" stripedRows responsiveLayout="scroll"
                    :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="{first} à {last} sur {totalRecords} catégories"
                    v-model:filters="tableFilters" filterDisplay="menu">

                    <Column field="name" header="Nom" sortable>
                        <template #body="slotProps">
                            <router-link :to="{ name: 'categories.edit', params: { id: slotProps.data.id } }"
                                class="category-name-link">
                                {{ slotProps.data.name }}
                            </router-link>
                        </template>
                    </Column>

                    <Column field="slug" header="Slug" sortable></Column>

                    <Column field="parent.name" header="Catégorie parente" sortable>
                        <template #body="slotProps">
                            <span v-if="slotProps.data.parent">{{ slotProps.data.parent.name }}</span>
                            <span v-else>-</span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Statut" sortable>
                        <template #body="slotProps">
                            <Badge :severity="slotProps.data.is_active ? 'success' : 'danger'"
                                :value="slotProps.data.is_active ? 'Actif' : 'Inactif'" />
                        </template>
                    </Column>

                    <Column field="products_count" header="Produits" sortable>
                        <template #body="slotProps">
                            <Badge :value="slotProps.data.products_count || '0'" severity="info" />
                        </template>
                    </Column>

                    <Column header="Actions" :exportable="false" style="min-width: 8rem">
                        <template #body="slotProps">
                            <div class="action-buttons">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-success p-button-sm mr-2"
                                    @click="editCategory(slotProps.data)" title="Modifier" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-sm"
                                    @click="confirmDelete(slotProps.data)" title="Supprimer" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <ConfirmDialog></ConfirmDialog>
        </div>
    </AdminLayout>
</template>

<script>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import ConfirmDialog from 'primevue/confirmdialog';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useCategoriesStore } from '../../store/categories';
import AdminLayout from '../../components/layout/AdminLayout.vue';
import Tag from 'primevue/tag';
export default {
    name: 'CategoryIndex',
    components: {
        DataTable,
        Column,
        InputText,
        Dropdown,
        Button,
        Badge,
        ConfirmDialog,
        AdminLayout,
        },

    setup() {
        const router = useRouter();
        const toast = useToast();
        const confirm = useConfirm();
        const authStore = useAuthStore();
        const categoriesStore = useCategoriesStore();

        const categories = ref([]);
        const parentCategories = ref([]);
        const loading = ref(false);
        const tableFilters = ref({});

        const filters = reactive({
            search: '',
            is_active: null,
            parent_id: null
        });

        const statusOptions = [
            { name: 'Tous', value: null },
            { name: 'Actif', value: true },
            { name: 'Inactif', value: false }
        ];

        const loadCategories = async () => {
            loading.value = true;

            try {
                // Construire les paramètres de requête
                const params = {
                    with: ['parent'],
                    page: 1
                };

                // Ajouter les filtres si présents
                if (filters.search) {
                    params.search = filters.search;
                }

                if (filters.is_active !== null) {
                    params.filter = {
                        ...params.filter,
                        is_active: filters.is_active
                    };
                }

                if (filters.parent_id) {
                    params.filter = {
                        ...params.filter,
                        parent_id: filters.parent_id
                    };
                }

                const response = await axios.get('/api/admin/categories', {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                categories.value = response.data.data;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger les catégories',
                    life: 3000
                });
            } finally {
                loading.value = false;
            }
        };

        const loadParentCategories = async () => {
            try {
                const response = await axios.get('/api/admin/categories/dropdown', {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                parentCategories.value = response.data.data;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories parentes:', error);
            }
        };

        const navigateToCreate = () => {
            router.push({ name: 'categories.create' });
        };

        const editCategory = (category) => {
            router.push({
                name: 'categories.edit',
                params: { id: category.id }
            });
        };

        const confirmDelete = (category) => {
            confirm.require({
                message: `Êtes-vous sûr de vouloir supprimer la catégorie "${category.name}" ?`,
                header: 'Confirmation de suppression',
                icon: 'pi pi-exclamation-triangle',
                acceptClass: 'p-button-danger',
                accept: () => deleteCategory(category),
                reject: () => { }
            });
        };

        const deleteCategory = async (category) => {
            try {
                await axios.delete(`/api/admin/categories/${category.id}`, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: `La catégorie "${category.name}" a été supprimée`,
                    life: 3000
                });

                // Recharger la liste des catégories
                loadCategories();
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de supprimer cette catégorie',
                    life: 3000
                });
            }
        };

        onMounted(() => {
            loadCategories();
            loadParentCategories();
        });

        return {
            categories,
            parentCategories,
            loading,
            filters,
            tableFilters,
            statusOptions,
            loadCategories,
            navigateToCreate,
            editCategory,
            confirmDelete
        };
    }
}
</script>

<style scoped>
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-input {
    flex: 2;
    min-width: 400px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-select {
    flex: 1;
    min-width: 200px;
}

.filter-select label {
    margin-right: 0.5rem;
    align-self: center;
}

.category-name-link {
    color: #3f51b5;
    text-decoration: none;
    font-weight: 500;
}

.category-name-link:hover {
    text-decoration: underline;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
    }

    .filter-input,
    .filter-select {
        width: 100%;
    }
}
</style>

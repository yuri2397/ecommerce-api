<template>
    <AdminLayout>
        <div class="products-page">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="card-title">Gestion des Produits</h1>
                <Button label="Nouveau Produit" icon="pi pi-plus" class="p-button-success" @click="navigateToCreate" />
            </div>
            <br>
            <div class="card">
                <div class="d-flex gap-2 align-items-center justify-content-between">
                    <div class="p-inputgroup d-flex w-100">
                        <InputText class="w-100" v-model="filters.search" placeholder="Rechercher un produit..." />
                        <Button icon="pi pi-search" class="mx-2 px-2" @click="loadProducts" label="Rechercher" />
                    </div>
                    <div class="d-flex gap-2 w-100 justify-content-end">
                        <div class="p-inputgroup ">
                            <Select v-model="filters.category_id" :options="categories" optionLabel="name"
                                optionValue="id" placeholder="Sélectionner une catégorie" class="w-100" :filter="true"
                                :showClear="true" @filter="onFilterCategories" :loading="loading" />

                        </div>
                        <div class="p-inputgroup filter-select">
                            <Dropdown id="status-filter" v-model="filters.is_active" :options="statusOptions"
                                optionLabel="name" optionValue="value" placeholder="Tous les statuts"
                                @change="loadProducts" />
                        </div>
                    </div>
                </div>


                <DataTable :value="products" :loading="loading" stripedRows responsiveLayout="scroll" :paginator="true"
                    :rows="10" :rowsPerPageOptions="[10, 20, 50]"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="{first} à {last} sur {totalRecords} produits"
                    v-model:filters="tableFilters" filterDisplay="menu">
                    <Column field="thumbnail_url" header="Image">
                        <template #body="slotProps">
                            <img :src="slotProps.data.thumbnail_url || '/img/no-image.png'" :alt="slotProps.data.name"
                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />
                        </template>
                    </Column>

                    <Column field="name" header="Nom" sortable>
                        <template #body="slotProps">
                            <router-link :to="{ name: 'products.details', params: { id: slotProps.data.id } }"
                                class="product-name-link">
                                {{ slotProps.data.name }}
                            </router-link>
                        </template>
                    </Column>

                    <Column field="price" header="Prix" sortable>
                        <template #body="slotProps">
                            {{ formatPrice(slotProps.data.sale_price) }}
                            <span v-if="slotProps.data.price !== slotProps.data.sale_price"
                                class="sale-price">
                                {{ formatPrice(slotProps.data.price) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="category.name" header="Catégorie" sortable>
                        <template #body="slotProps">
                            <span v-if="slotProps.data.category">{{ slotProps.data.category.name }}</span>
                            <span v-else>-</span>
                        </template>
                    </Column>

                    <Column field="stock_quantity" header="Stock" sortable>
                        <template #body="slotProps">
                            <Badge v-if="slotProps.data.stock_quantity <= 0" severity="danger" value="Épuisé" />
                            <Badge v-else-if="slotProps.data.stock_quantity <= 5" severity="warning"
                                :value="slotProps.data.stock_quantity.toString()" />
                            <Badge v-else severity="success" :value="slotProps.data.stock_quantity.toString()" />
                        </template>
                    </Column>

                    <Column field="is_active" header="Statut" sortable>
                        <template #body="slotProps">
                            <Badge :severity="slotProps.data.is_active ? 'success' : 'danger'"
                                :value="slotProps.data.is_active ? 'Actif' : 'Inactif'" />
                        </template>
                    </Column>

                    <Column header="Actions" :exportable="false" style="min-width: 8rem">
                        <template #body="slotProps">
                            <div class="action-buttons">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-success p-button-sm mr-2"
                                    @click="editProduct(slotProps.data)" title="Modifier" />
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
import Select from 'primevue/select';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import ConfirmDialog from 'primevue/confirmdialog';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useCategoriesStore } from '../../store/categories';
import AdminLayout from '../../components/layout/AdminLayout.vue';

export default {
    name: 'ProductsIndex',
    components: {
        DataTable,
        Column,
        InputText,
        Select,
        Button,
        Badge,
        ConfirmDialog,
        AdminLayout
    },
    setup() {
        const router = useRouter();
        const toast = useToast();
        const confirm = useConfirm();
        const authStore = useAuthStore();
        const categoriesStore = useCategoriesStore();

        const products = ref([]);
        const categories = ref([]);
        const loading = ref(false);
        const tableFilters = ref({});

        const filters = reactive({
            search: '',
            category_id: null,
            is_active: null
        });

        const statusOptions = [
            { name: 'Tous', value: null },
            { name: 'Actif', value: true },
            { name: 'Inactif', value: false }
        ];

        const loadProducts = async () => {
            loading.value = true;

            try {
                // Construire les paramètres de requête
                const params = {
                    with: ['category'],
                    page: 1,
                    per_page: 10
                };

                // Ajouter les filtres si présents
                if (filters.search) {
                    params.search = filters.search;
                }

                if (filters.category_id) {
                    params.filter = {
                        ...params.filter,
                        category_id: filters.category_id
                    };
                }

                if (filters.is_active !== null) {
                    params.filter = {
                        ...params.filter,
                        is_active: filters.is_active
                    };
                }

                const response = await axios.get('/api/admin/products', {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                products.value = response.data.data;
            } catch (error) {
                console.error('Erreur lors du chargement des produits:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger les produits',
                    life: 3000
                });
            } finally {
                loading.value = false;
            }
        };

        const loadCategories = async () => {
            try {
                await categoriesStore.fetchCategories();
                categories.value = categoriesStore.categories;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories:', error);
            }
        };

        const onFilterCategories = async (event) => {
            try {
                loading.value = true;

                // Appel à l'API avec le terme de recherche
                await categoriesStore.fetchCategories({ is_active: true, search: event.value });
                categories.value = categoriesStore.categories;
                categories.value = data;
            } catch (error) {
                console.error('Erreur lors de la recherche de catégories:', error);
            } finally {
                loading.value = false;
            }
        };

        const navigateToCreate = () => {
            router.push({ name: 'products.create' });
        };

        const editProduct = (product) => {
            router.push({
                name: 'products.edit',
                params: { id: product.id }
            });
        };

        const confirmDelete = (product) => {
            confirm.require({
                message: `Êtes-vous sûr de vouloir supprimer le produit "${product.name}" ?`,
                header: 'Confirmation de suppression',
                icon: 'pi pi-exclamation-triangle',
                acceptClass: 'p-button-danger',
                accept: () => deleteProduct(product),
                reject: () => { }
            });
        };

        const deleteProduct = async (product) => {
            try {
                await axios.delete(`/api/admin/products/${product.id}`, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: `Le produit "${product.name}" a été supprimé`,
                    life: 3000
                });

                // Recharger la liste des produits
                loadProducts();
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de supprimer ce produit',
                    life: 3000
                });
            }
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'xof'
            }).format(price);
        };

        onMounted(() => {
            loadProducts();
            loadCategories();
        });

        return {
            products,
            categories,
            loading,
            filters,
            tableFilters,
            statusOptions,
            loadProducts,
            navigateToCreate,
            editProduct,
            confirmDelete,
            formatPrice,
            onFilterCategories
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
    min-width: 250px;
}

.filter-select {

    flex: 1;
    min-width: 200px;
}

.filter-select label {
    margin-right: 0.5rem;
    align-self: center;
}

.product-name-link {
    color: #3f51b5;
    text-decoration: none;
    font-weight: 500;
}

.product-name-link:hover {
    text-decoration: underline;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.sale-price {
    color: #f44336;
    margin-left: 0.5rem;
    font-size: 0.9em;
    text-decoration: line-through;
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

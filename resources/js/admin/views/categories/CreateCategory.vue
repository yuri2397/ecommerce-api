<template>
    <AdminLayout>
        <div class="category-create-page">
            <div class="card-header">
                <h1 class="card-title">Créer une nouvelle catégorie</h1>
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
            </div>
            <div class="card">
                <form @submit.prevent="saveCategory" class="p-fluid">
                    <div class="row">
                        <!-- Colonne principale -->
                        <div class="col-md-6">
                            <label for="name">Nom de la catégorie*</label>
                            <InputText class="w-100" id="name" v-model="category.name"
                                placeholder="Entrez le nom de la catégorie"
                                :class="{ 'p-invalid': submitted && !category.name }" aria-required="true" />
                            <small v-if="submitted && !category.name" class="p-error">Le nom de la catégorie est
                                requis</small>
                        </div>
                        <div class="col-md-6">
                            <label for="parent_id">Catégorie parente</label>
                            <Dropdown class="w-100" id="parent_id" v-model="category.parent_id"
                                :options="parentCategories" optionLabel="name" optionValue="id"
                                placeholder="Sélectionner une catégorie parente (optionnel)" />
                        </div>

                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="description">Description</label>
                            <Textarea class="w-100" id="description" v-model="category.description" rows="5" />
                        </div>
                        <div class="field">
                            <div class="d-flex align-items-center gap-2">
                                <Checkbox id="is_active" v-model="category.is_active" :binary="true" />
                                <label for="is_active">Catégorie active</label>

                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-secondary" @click="navigateBack"
                            type="button" />
                        <Button label="Enregistrer" icon="pi pi-save" class="p-button-success" type="submit"
                            :loading="saving" />
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import AdminLayout from '../../components/layout/AdminLayout.vue';


export default {
    name: 'CreateCategory',
    components: {
        InputText,
        Textarea,
        Dropdown,
        Checkbox,
        Button,
        AdminLayout
    },
    setup() {
        const router = useRouter();
        const toast = useToast();
        const authStore = useAuthStore();

        const submitted = ref(false);
        const saving = ref(false);
        const parentCategories = ref([]);

        const category = reactive({
            name: '',
            description: '',
            parent_id: null,
            is_active: true,
            meta: {
                keywords: '',
                description: ''
            }
        });

        const loadParentCategories = async () => {
            try {
                const response = await axios.get('/api/admin/categories/dropdown', {
                    params: {
                        is_active: true
                    },
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                parentCategories.value = response.data.data;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories parentes:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger les catégories parentes',
                    life: 3000
                });
            }
        };

        const saveCategory = async () => {
            submitted.value = true;

            // Validation
            if (!category.name) {
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Veuillez remplir tous les champs obligatoires',
                    life: 3000
                });
                return;
            }

            saving.value = true;

            try {
                const response = await axios.post('/api/admin/categories', category, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Catégorie créée avec succès',
                    life: 3000
                });

                // Redirection vers la liste des catégories
                router.push({ name: 'categories' });
            } catch (error) {
                console.error('Erreur lors de la création de la catégorie:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de créer la catégorie',
                    life: 3000
                });
            } finally {
                saving.value = false;
            }
        };

        const navigateBack = () => {
            router.push({ name: 'categories' });
        };

        onMounted(() => {
            loadParentCategories();
        });

        return {
            category,
            parentCategories,
            submitted,
            saving,
            saveCategory,
            navigateBack
        };
    }
}
</script>

<style scoped>
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.field {
    margin-bottom: 1.5rem;
}

.p-error {
    color: #f44336;
    font-size: 0.875rem;
}

.ml-2 {
    margin-left: 0.5rem;
}

.meta-container {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<template>
    <AdminLayout>
        <div class="category-create-page">
            <div class="card-header">
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
                <h1 class="card-title">Créer une nouvelle catégorie</h1>

            </div>
            <div class="card">
                <form @submit.prevent="saveCategory" class="p-fluid">
                    <div class="row">
                        <!-- Colonne principale -->
                        <div class="col-md-6">
                            <label for="name">Nom de la catégorie <span class="text-danger">*</span></label>
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
                            <div class="d-flex gap-2">
                                <!-- Ajout du champ pour l'icône -->
                                <div>
                                    <label for="icon">Icône de la catégorie</label>
                                    <FileUpload id="icon" mode="basic" accept="image/*" :maxFileSize="500000"
                                        chooseLabel="Choisir une icône" class="mb-3" @select="onIconSelect" :auto="true"
                                        :customUpload="true" @uploader="onIconUpload" />
                                </div>

                                <!-- Aperçu de l'icône -->
                                <div v-if="iconPreview" class="icon-preview mt-2">
                                    <img :src="iconPreview" alt="Aperçu de l'icône" class="preview-image" />
                                    <Button icon="pi pi-times"
                                        class="p-button-rounded p-button-danger p-button-sm remove-icon"
                                        @click="removeIcon" title="Supprimer" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <Checkbox id="is_active" v-model="category.is_active" :binary="true" />
                                <label for="is_active">Catégorie active</label>
                            </div>
                        </div>
                    </div>
                    <br>

                    <div class="col-md-6">
                        <label for="description">Description</label>
                        <Textarea class="w-100" id="description" placeholder="Description de la catégorie"
                            v-model="category.description" rows="5" />
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
import FileUpload from 'primevue/fileupload';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import AdminLayout from '../../components/layout/AdminLayout.vue';
import { useCategoriesStore } from '../../store/categories';
export default {
    name: 'CreateCategory',
    components: {
        InputText,
        Textarea,
        Dropdown,
        Checkbox,
        Button,
        FileUpload,
        AdminLayout
    },
    setup() {
        const router = useRouter();
        const toast = useToast();
        const authStore = useAuthStore();
        const categoryStore = useCategoriesStore();
        const submitted = ref(false);
        const saving = ref(false);
        const parentCategories = ref([]);
        const iconFile = ref(null);
        const iconPreview = ref(null);

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
                const response = await categoryStore.fetchCategoriesDropdown(
                    {
                        is_active: true
                    }
                );
                parentCategories.value = response;
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

        const onIconSelect = (e) => {
            iconFile.value = e.files[0];

            // Créer un aperçu de l'icône
            const reader = new FileReader();
            reader.onload = (event) => {
                iconPreview.value = event.target.result;
            };
            reader.readAsDataURL(iconFile.value);
        };

        const onIconUpload = (event) => {
            // Conserver uniquement la référence au fichier choisi
            // L'upload se fera lors de la soumission du formulaire
            event.options.clear();
        };

        const removeIcon = () => {
            iconFile.value = null;
            iconPreview.value = null;
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
                // Créer un FormData pour pouvoir envoyer le fichier
                const formData = new FormData();

                // Ajouter les données de la catégorie
                Object.keys(category).forEach(key => {
                    if (key === 'meta') {
                        formData.append(key, JSON.stringify(category[key]));
                    } else if (category[key] !== null && category[key] !== undefined) {
                        formData.append(key, category[key]);
                    }
                });

                // Ajouter l'icône si présente
                if (iconFile.value) {
                    formData.append('icon', iconFile.value);
                }

                const response = await axios.post('/api/admin/categories', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
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
                router.push({ name: 'categories.index' });
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
            router.push({ name: 'categories.index' });
        };

        onMounted(() => {
            loadParentCategories();
        });

        return {
            category,
            parentCategories,
            submitted,
            saving,
            iconFile,
            iconPreview,
            saveCategory,
            navigateBack,
            onIconSelect,
            onIconUpload,
            removeIcon
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

.icon-preview {
    position: relative;
    display: inline-block;
    margin-top: 1rem;
}

.preview-image {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    background-color: #f9f9f9;
}

.remove-icon {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 24px;
    height: 24px;
}

.ml-2 {
    margin-left: 0.5rem;
}

@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<template>
    <AdminLayout>
        <div class="category-edit-page">
            <div class="card-header">
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
                <h1 class="card-title">Modifier la catégorie</h1>

            </div>
            <div class="card">
                <div v-if="loading" class="loading-container">
                    <ProgressSpinner />
                    <p>Chargement de la catégorie...</p>
                </div>
                <form v-else @submit.prevent="updateCategory" class="p-fluid">
                    <div class="row">
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
                            <Select v-model="category.parent_id" :options="parentCategories" optionLabel="name"
                                optionValue="id" placeholder="Sélectionner une catégorie" class="w-100" :filter="true"
                                :showClear="true" @filter="onFilterParentCategories" :loading="loading" />
                        </div>
                    </div>
                    <br>
                    <div class="row">

                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <div>
                                    <!-- Affichage de l'icône existante -->
                                    <label>Icône de la catégorie</label>
                                    <div v-if="existingIcon" class="existing-icon mb-3">
                                        <img :src="existingIcon" alt="Icône actuelle" class="preview-image" />
                                        <Button icon="pi pi-times"
                                            class="p-button-rounded p-button-danger p-button-sm remove-icon"
                                            @click="removeExistingIcon" title="Supprimer" />
                                    </div>

                                    <!-- Champ pour télécharger une nouvelle icône -->
                                    <FileUpload id="icon" mode="basic" accept="image/*" :maxFileSize="500000"
                                        :disabled="!!iconPreview" chooseLabel="Choisir une nouvelle icône" class="mb-3"
                                        @select="onIconSelect" :auto="true" :customUpload="true"
                                        @uploader="onIconUpload" />
                                </div>

                                <!-- Aperçu de la nouvelle icône -->
                                <div v-if="iconPreview" class="icon-preview mt-2">
                                    <img :src="iconPreview" alt="Nouvelle icône" class="preview-image" />
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
                        <Textarea class="w-100" id="description" v-model="category.description" rows="5" />
                    </div>
                    <div class="slug-field mb-3">
                        <label for="slug">Slug</label>
                        <div class="p-inputgroup">
                            <InputText id="slug" v-model="category.slug" disabled />
                            <Button type="button" icon="pi pi-refresh" class="p-button-secondary"
                                @click="regenerateSlug" title="Régénérer le slug" />
                        </div>
                        <small class="text-secondary">Le slug est utilisé dans les URLs. Modifiez le nom pour le
                            régénérer.</small>
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
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import AdminLayout from '../../components/layout/AdminLayout.vue';
import { useCategoriesStore } from '../../store/categories';
export default {
    name: 'EditCategory',
    components: {
        InputText,
        Textarea,
        Select,
        Checkbox,
        Button,
        FileUpload,
        ProgressSpinner,
        AdminLayout
    },
    setup() {
        const router = useRouter();
        const route = useRoute();
        const toast = useToast();
        const authStore = useAuthStore();
        const categoriesStore = useCategoriesStore();
        const categoryId = route.params.id;
        const submitted = ref(false);
        const saving = ref(false);
        const loading = ref(true);
        const parentCategories = ref([]);
        const iconFile = ref(null);
        const iconPreview = ref(null);
        const existingIcon = ref(null);
        const removeIconFlag = ref(false);

        const category = reactive({
            id: null,
            name: '',
            description: '',
            slug: '',
            parent_id: null,
            is_active: true,

        });

        const loadCategory = async () => {
            try {
                const response = await categoriesStore.fetchCategoryById(categoryId);

                const categoryData = response;

                // Remplir les données de la catégorie
                Object.keys(category).forEach(key => {
                    if (key in categoryData) {
                        category[key] = categoryData[key];
                    }
                });

                // Charger l'icône si elle existe
                if (categoryData.icon_url) {
                    existingIcon.value = categoryData.icon_url;
                }

            } catch (error) {
                console.error('Erreur lors du chargement de la catégorie:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger la catégorie',
                    life: 3000
                });
                router.push({ name: 'categories.index' });
            } finally {
                loading.value = false;
            }
        };

        const onFilterParentCategories = async (event) => {
            try {
                const response = await categoriesStore.fetchCategoriesDropdown({ is_active: true, search: event.value });

                // Filtrer pour exclure la catégorie actuelle et ses enfants
                parentCategories.value = response.filter(cat => cat.id !== categoryId);
            } catch (error) {
                console.error('Erreur lors de la recherche de catégories:', error);
            } finally {
                loading.value = false;
            }
        };

        const loadParentCategories = async () => {
            try {
                const response = await categoriesStore.fetchCategoriesDropdown({ is_active: true });

                // Filtrer pour exclure la catégorie actuelle et ses enfants
                parentCategories.value = response.filter(cat => cat.id !== categoryId);

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
                existingIcon.value = null; // Masquer l'icône existante
            };
            reader.readAsDataURL(iconFile.value);
        };

        const onIconUpload = (event) => {
            // L'upload se fera lors de la soumission du formulaire
            event.options.clear();
        };

        const removeIcon = () => {
            iconFile.value = null;
            iconPreview.value = null;

            // Si une icône existait, la réafficher
            if (existingIcon.value) {
                existingIcon.value = existingIcon.value;
            }
        };

        const removeExistingIcon = () => {
            existingIcon.value = null;
            removeIconFlag.value = true;
        };

        const regenerateSlug = () => {
            // Simple slug generation (a more robust version would be on the server)
            if (category.name) {
                category.slug = category.name
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-');
            }
        };

        const updateCategory = async () => {
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

                // Ajouter la méthode PUT
                formData.append('_method', 'PUT');

                // Ajouter les champs textuels et les booléens
                Object.keys(category).forEach(key => {
                    if (category[key] !== null && category[key] !== undefined) {
                        // Traitement spécial pour les booléens
                        if (typeof category[key] === 'boolean') {
                            // Convertir en 0/1 ou envoyer la valeur réelle en string
                            formData.append(key, category[key] ? '1' : '0');
                        }
                        else {
                            formData.append(key, category[key]);
                        }
                    }
                });

                // Ajouter l'icône si présente
                if (iconFile.value) {
                    formData.append('icon', iconFile.value);
                }

                // Si on veut supprimer l'icône existante
                if (removeIconFlag.value) {
                    formData.append('remove_icon', '1');
                }

                const response = await axios.post(`/api/admin/categories/${categoryId}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Catégorie mise à jour avec succès',
                    life: 3000
                });

                // Redirection vers la liste des catégories
                router.push({ name: 'categories.index' });
            } catch (error) {
                console.error('Erreur lors de la mise à jour de la catégorie:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de mettre à jour la catégorie',
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
            loadCategory();
            loadParentCategories();
        });

        return {
            category,
            parentCategories,
            submitted,
            saving,
            loading,
            iconFile,
            iconPreview,
            existingIcon,
            updateCategory,
            navigateBack,
            onIconSelect,
            onIconUpload,
            removeIcon,
            removeExistingIcon,
            regenerateSlug,
            onFilterParentCategories
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

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.icon-preview,
.existing-icon {
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

.slug-field small {
    margin-top: 0.25rem;
    display: block;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 992px) {
    .row {
        flex-direction: column;
    }

    .col-md-6 {
        width: 100%;
        margin-bottom: 1rem;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions button {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

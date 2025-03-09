<template>
    <AdminLayout>
        <div class="product-edit-page">
            <div class="card-header">
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
                <h1 class="card-title">Modifier le Produit</h1>

            </div>
            <div class="card">


                <div v-if="loading" class="loading-container">
                    <ProgressSpinner />
                    <p>Chargement du produit...</p>
                </div>

                <form v-else @submit.prevent="saveProduct" class="p-fluid">
                    <div class="form-grid">
                        <!-- Colonne principale -->
                        <div class="form-main-column">
                            <div class="field">
                                <label for="name">Nom du produit*</label>
                                <InputText id="name" v-model="product.name"
                                    :class="{ 'p-invalid': submitted && !product.name }" aria-required="true" />
                                <small v-if="submitted && !product.name" class="p-error">Le nom du produit est
                                    requis</small>
                            </div>

                            <div class="field">
                                <label for="sku">SKU</label>
                                <InputText id="sku" v-model="product.sku" />
                            </div>

                            <div class="field">
                                <label for="description">Description</label>
                                <Editor id="description" v-model="product.description" editorStyle="height: 250px" />
                            </div>

                            <div class="field-group">
                                <div class="field">
                                    <label for="price">Prix*</label>
                                    <InputNumber id="price" v-model="product.price" mode="currency" currency="EUR"
                                        locale="fr-FR" :minFractionDigits="2"
                                        :class="{ 'p-invalid': submitted && !product.price }" aria-required="true" />
                                    <small v-if="submitted && !product.price" class="p-error">Le prix est requis</small>
                                </div>

                                <div class="field">
                                    <label for="sale_price">Prix promotionnel</label>
                                    <InputNumber id="sale_price" v-model="product.sale_price" mode="currency"
                                        currency="EUR" locale="fr-FR" :minFractionDigits="2" />
                                </div>
                            </div>

                            <div class="field">
                                <label for="stock_quantity">Quantité en stock*</label>
                                <InputNumber id="stock_quantity" v-model="product.stock_quantity" showButtons :min="0"
                                    :step="1" :class="{ 'p-invalid': submitted && product.stock_quantity === null }"
                                    aria-required="true" />
                                <small v-if="submitted && product.stock_quantity === null" class="p-error">La quantité
                                    en stock est requise</small>
                            </div>
                        </div>

                        <!-- Colonne secondaire -->
                        <div class="form-side-column">
                            <div class="field">
                                <label for="category_id">Catégorie*</label>
                                <Dropdown id="category_id" v-model="product.category_id" :options="categories"
                                    optionLabel="name" optionValue="id" placeholder="Sélectionner une catégorie"
                                    :class="{ 'p-invalid': submitted && !product.category_id }" aria-required="true" />
                                <small v-if="submitted && !product.category_id" class="p-error">La catégorie est
                                    requise</small>
                            </div>

                            <div class="field">
                                <div class="flex align-items-center">
                                    <Checkbox id="is_active" v-model="product.is_active" :binary="true" />
                                    <label for="is_active" class="ml-2">Produit actif</label>
                                </div>
                            </div>

                            <div class="field">
                                <div class="flex align-items-center">
                                    <Checkbox id="is_featured" v-model="product.is_featured" :binary="true" />
                                    <label for="is_featured" class="ml-2">Mettre en avant</label>
                                </div>
                            </div>

                            <!-- Images existantes -->
                            <div v-if="existingImages.length > 0" class="field">
                                <label>Images existantes</label>
                                <div class="existing-images">
                                    <div v-for="(image, index) in existingImages" :key="'existing-' + image.id"
                                        class="image-preview-item">
                                        <img :src="image.url" alt="Image existante" />
                                        <div class="image-badge" v-if="image.is_thumbnail">
                                            <Badge value="Image principale" severity="info" />
                                        </div>
                                        <div class="image-actions">
                                            <Button v-if="!image.is_thumbnail" icon="pi pi-star"
                                                class="p-button-rounded p-button-warning p-button-sm"
                                                @click="setExistingAsThumbnail(image.id)"
                                                title="Définir comme principale" />
                                            <Button v-else icon="pi pi-star-fill"
                                                class="p-button-rounded p-button-warning p-button-sm" disabled
                                                title="Image principale" />
                                            <Button icon="pi pi-times"
                                                class="p-button-rounded p-button-danger p-button-sm"
                                                @click="removeExistingImage(image.id)" title="Supprimer" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload de nouvelles images -->
                            <div class="field">
                                <label>Ajouter des images</label>
                                <FileUpload mode="advanced" multiple accept="image/*" :maxFileSize="5000000"
                                    customUpload @uploader="onImageUpload" :auto="true" chooseLabel="Choisir"
                                    cancelLabel="Annuler">
                                    <template #empty>
                                        <p>Glissez-déposez les images ici pour les télécharger.</p>
                                    </template>
                                </FileUpload>
                            </div>

                            <!-- Aperçu des nouvelles images -->
                            <div v-if="previewImages.length > 0" class="preview-images">
                                <h3>Nouvelles images</h3>
                                <div class="image-previews">
                                    <div v-for="(image, index) in previewImages" :key="'new-' + index"
                                        class="image-preview-item">
                                        <img :src="image.preview" alt="Aperçu" />
                                        <div class="image-actions">
                                            <Button v-if="index !== thumbnailIndex" icon="pi pi-star"
                                                class="p-button-rounded p-button-warning p-button-sm"
                                                @click="setAsThumbnail(index)" title="Définir comme principale" />
                                            <Button v-else icon="pi pi-star-fill"
                                                class="p-button-rounded p-button-warning p-button-sm" disabled
                                                title="Image principale" />
                                            <Button icon="pi pi-times"
                                                class="p-button-rounded p-button-danger p-button-sm"
                                                @click="removeImage(index)" title="Supprimer" />
                                        </div>
                                    </div>
                                </div>
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
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Editor from 'primevue/editor';
import Checkbox from 'primevue/checkbox';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useCategoriesStore } from '../../store/categories';
import AdminLayout from '../../components/layout/AdminLayout.vue';

export default {
    name: 'ProductEdit',
    components: {
        InputText,
        InputNumber,
        Dropdown,
        Editor,
        Checkbox,
        FileUpload,
        Button,
        ProgressSpinner,
        Badge,
        AdminLayout
    },
    setup() {
        const router = useRouter();
        const route = useRoute();
        const toast = useToast();
        const authStore = useAuthStore();
        const categoriesStore = useCategoriesStore();

        const productId = route.params.id;
        const submitted = ref(false);
        const saving = ref(false);
        const loading = ref(true);
        const categories = ref([]);
        const previewImages = ref([]);
        const uploadedImages = ref([]);
        const existingImages = ref([]);
        const thumbnailIndex = ref(null);
        const imagesToDelete = ref([]);

        const product = reactive({
            id: null,
            name: '',
            sku: '',
            description: '',
            price: null,
            sale_price: null,
            stock_quantity: 0,
            category_id: null,
            is_active: true,
            is_featured: false
        });

        const loadProduct = async () => {
            try {
                const response = await axios.get(`/api/admin/products/${productId}`, {
                    params: {
                        with: ['category']
                    },
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                // Remplir les données du produit
                const productData = response.data.data;
                Object.keys(product).forEach(key => {
                    if (key in productData) {
                        product[key] = productData[key];
                    }
                });

                // Charger les images existantes
                if (productData.images && productData.images.length > 0) {
                    existingImages.value = productData.images.map(image => ({
                        id: image.id,
                        url: image.url,
                        is_thumbnail: image.thumb_url === productData.thumbnail_url
                    }));
                }
            } catch (error) {
                console.error('Erreur lors du chargement du produit:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger le produit',
                    life: 3000
                });
                router.push({ name: 'products' });
            } finally {
                loading.value = false;
            }
        };

        const loadCategories = async () => {
            try {
                await categoriesStore.fetchCategories({ is_active: true });
                categories.value = categoriesStore.categories;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger les catégories',
                    life: 3000
                });
            }
        };

        const saveProduct = async () => {
            submitted.value = true;

            // Validation
            if (!product.name || !product.price || !product.category_id || product.stock_quantity === null) {
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
                // Préparer les données du formulaire (pour pouvoir envoyer les fichiers)
                const formData = new FormData();

                // Utiliser la méthode PUT/PATCH
                formData.append('_method', 'PUT');

                // Ajouter les champs textuels
                Object.keys(product).forEach(key => {
                    if (product[key] !== null && product[key] !== undefined && key !== 'id') {
                        formData.append(key, product[key]);
                    }
                });

                // Ajouter les nouvelles images
                uploadedImages.value.forEach((image, index) => {
                    formData.append('images[]', image.file);
                });

                // Si de nouvelles images ont été sélectionnées et qu'une est marquée comme miniature
                if (uploadedImages.value.length > 0 && thumbnailIndex.value !== null) {
                    formData.append('thumbnail', uploadedImages.value[thumbnailIndex.value].file);
                }

                // Ajouter les ID des images à supprimer
                if (imagesToDelete.value.length > 0) {
                    formData.append('delete_images', JSON.stringify(imagesToDelete.value));
                }

                const response = await axios.post(`/api/admin/products/${product.id}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Produit mis à jour avec succès',
                    life: 3000
                });

                // Redirection vers la liste des produits
                router.push({ name: 'products' });
            } catch (error) {
                console.error('Erreur lors de la mise à jour du produit:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de mettre à jour le produit',
                    life: 3000
                });
            } finally {
                saving.value = false;
            }
        };

        const onImageUpload = (event) => {
            // Récupérer les fichiers
            const files = event.files;

            // Ajouter les fichiers à la liste des images téléchargées
            for (let file of files) {
                // Créer un URL pour la prévisualisation
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = e.target.result;
                    previewImages.value.push({
                        preview,
                        file
                    });
                    uploadedImages.value.push({
                        file
                    });
                };
                reader.readAsDataURL(file);
            }

            // Réinitialiser l'uploader
            event.options.clear();
        };

        const removeImage = (index) => {
            // Supprimer l'image de la prévisualisation et de la liste des images téléchargées
            previewImages.value.splice(index, 1);
            uploadedImages.value.splice(index, 1);

            // Ajuster l'index de la miniature si nécessaire
            if (thumbnailIndex.value === index) {
                thumbnailIndex.value = previewImages.value.length > 0 ? 0 : null;
            } else if (thumbnailIndex.value > index) {
                thumbnailIndex.value--;
            }
        };

        const setAsThumbnail = (index) => {
            thumbnailIndex.value = index;
        };

        const removeExistingImage = (imageId) => {
            // Ajouter l'ID de l'image à la liste des images à supprimer
            imagesToDelete.value.push(imageId);

            // Supprimer l'image de la liste des images existantes
            existingImages.value = existingImages.value.filter(image => image.id !== imageId);
        };

        const setExistingAsThumbnail = async (imageId) => {
            try {
                await axios.post(`/api/admin/products/${product.id}/thumbnail/${imageId}`, {}, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                // Mettre à jour les états des images existantes
                existingImages.value = existingImages.value.map(image => ({
                    ...image,
                    is_thumbnail: image.id === imageId
                }));

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Image principale définie avec succès',
                    life: 3000
                });
            } catch (error) {
                console.error('Erreur lors de la définition de l\'image principale:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de définir l\'image principale',
                    life: 3000
                });
            }
        };

        const navigateBack = () => {
            router.back();
        };

        onMounted(() => {
            loadCategories();
            loadProduct();
        });

        return {
            product,
            categories,
            submitted,
            saving,
            loading,
            previewImages,
            uploadedImages,
            existingImages,
            thumbnailIndex,
            saveProduct,
            onImageUpload,
            removeImage,
            setAsThumbnail,
            removeExistingImage,
            setExistingAsThumbnail,
            navigateBack
        };
    }
}
</script>

<style scoped>
.form-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.form-main-column,
.form-side-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.field-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

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

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.preview-images,
.existing-images {
    margin-top: 1rem;
}

.image-previews,
.existing-images {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.image-preview-item {
    position: relative;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.image-preview-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    display: block;
}

.image-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    z-index: 1;
}

.image-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.5);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.form-actions button {
    min-width: 120px;
}

@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .field-group {
        grid-template-columns: 1fr;
    }
}
</style>

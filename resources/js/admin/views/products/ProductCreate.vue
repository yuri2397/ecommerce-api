<template>
    <div class="product-create-page">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Créer un Nouveau Produit</h1>
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
            </div>

            <form @submit.prevent="saveProduct" class="p-fluid">
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
                            <InputText id="sku" v-model="product.sku"
                                placeholder="Sera généré automatiquement si laissé vide" />
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
                                <InputNumber id="sale_price" v-model="product.sale_price" mode="currency" currency="EUR"
                                    locale="fr-FR" :minFractionDigits="2" />
                            </div>
                        </div>

                        <div class="field">
                            <label for="stock_quantity">Quantité en stock*</label>
                            <InputNumber id="stock_quantity" v-model="product.stock_quantity" showButtons :min="0"
                                :step="1" :class="{ 'p-invalid': submitted && product.stock_quantity === null }"
                                aria-required="true" />
                            <small v-if="submitted && product.stock_quantity === null" class="p-error">La quantité en
                                stock est requise</small>
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

                        <div class="field">
                            <label>Images du produit</label>
                            <FileUpload mode="advanced" multiple accept="image/*" :maxFileSize="5000000" customUpload
                                @uploader="onImageUpload" :auto="true" chooseLabel="Choisir" cancelLabel="Annuler">
                                <template #empty>
                                    <p>Glissez-déposez les images ici pour les télécharger.</p>
                                </template>
                            </FileUpload>
                        </div>

                        <div v-if="previewImages.length > 0" class="preview-images">
                            <h3>Aperçu des images</h3>
                            <div class="image-previews">
                                <div v-for="(image, index) in previewImages" :key="index" class="image-preview-item">
                                    <img :src="image" alt="Aperçu" />
                                    <Button icon="pi pi-times"
                                        class="p-button-rounded p-button-danger p-button-sm remove-image"
                                        @click="removeImage(index)" />
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
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Editor from 'primevue/editor';
import Checkbox from 'primevue/checkbox';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

export default {
    name: 'ProductCreate',
    components: {
        InputText,
        InputNumber,
        Dropdown,
        Editor,
        Checkbox,
        FileUpload,
        Button
    },
    setup() {
        const router = useRouter();
        const toast = useToast();
        const authStore = useAuthStore();

        const submitted = ref(false);
        const saving = ref(false);
        const categories = ref([]);
        const previewImages = ref([]);
        const uploadedImages = ref([]);

        const product = reactive({
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

        const loadCategories = async () => {
            try {
                const response = await axios.get('/api/admin/categories', {
                    params: {
                        is_active: true
                    },
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

                // Ajouter les champs textuels
                Object.keys(product).forEach(key => {
                    if (product[key] !== null && product[key] !== undefined) {
                        formData.append(key, product[key]);
                    }
                });

                // Ajouter les images
                uploadedImages.value.forEach(image => {
                    formData.append('images[]', image);
                });

                // Si des images ont été sélectionnées, définir la première comme miniature
                if (uploadedImages.value.length > 0) {
                    formData.append('thumbnail', uploadedImages.value[0]);
                }

                const response = await axios.post('/api/admin/products', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Produit créé avec succès',
                    life: 3000
                });

                // Redirection vers la liste des produits
                router.push({ name: 'products-index' });
            } catch (error) {
                console.error('Erreur lors de la création du produit:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: error.response?.data?.message || 'Impossible de créer le produit',
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
                uploadedImages.value.push(file);

                // Créer un URL pour la prévisualisation
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImages.value.push(e.target.result);
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
        };

        const navigateBack = () => {
            router.push({ name: 'products-index' });
        };

        onMounted(() => {
            loadCategories();
        });

        return {
            product,
            categories,
            submitted,
            saving,
            previewImages,
            uploadedImages,
            saveProduct,
            onImageUpload,
            removeImage,
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

.preview-images {
    margin-top: 1.5rem;
}

.image-previews {
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

.remove-image {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 2rem;
    height: 2rem;
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

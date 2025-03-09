<template>
    <AdminLayout>
        <div class="product-create-page">
            <div class="card-header">
                <Button label="Retour à la liste" icon="pi pi-arrow-left" class="p-button-secondary"
                    @click="navigateBack" />
                <h1 class="card-title">Créer un Nouveau Produit</h1>

            </div>
            <div class="card">
                <form @submit.prevent="saveProduct" class="p-fluid">
                    <div class="form-grid">
                        <!-- Colonne principale -->
                        <div class="form-main-column">
                            <div class="row">
                                <div class="col">
                                    <label for="name">Nom du produit*</label>
                                    <InputText class="w-100" id="name" v-model="product.name"
                                        placeholder="Nom du produit"
                                        :class="{ 'p-invalid': submitted && !product.name }" aria-required="true" />
                                    <small v-if="submitted && !product.name" class="p-error">Le nom du produit est
                                        requis</small>
                                </div>

                                <div class="col">
                                    <label for="price">Prix*</label>
                                    <InputNumber class="w-100" id="price" v-model="product.price" mode="currency"
                                        currency="XOF" locale="fr-FR" :minFractionDigits="0" placeholder="Prix"
                                        :class="{ 'p-invalid': submitted && !product.price }" aria-required="true" />
                                    <small v-if="submitted && !product.price" class="p-error">Le prix est requis</small>
                                </div>
                            </div>
                            <br>
                            <div class="row">

                                <div class="col">
                                    <label for="sale_price">Pourcentage de réduction</label>
                                    <InputNumber class="w-100" id="sale_price" placeholder="Pourcentage de réduction"
                                        v-model="product.sale_price" locale="fr-FR" :min="0" :max="100" :step="1" />
                                </div>
                                <div class="col">
                                    <label for="stock_quantity">Quantité en stock*</label>
                                    <InputNumber class="w-100" id="stock_quantity" v-model="product.stock_quantity"
                                        showButtons :min="0" :step="1"
                                        :class="{ 'p-invalid': submitted && product.stock_quantity === null }"
                                        aria-required="true" />
                                    <small v-if="submitted && product.stock_quantity === null" class="p-error">La
                                        quantité
                                        en stock est requise</small>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col">
                                    <label for="category_id">Catégorie*</label>
                                    <Select v-model="product.category_id" :options="categories" optionLabel="name"
                                        optionValue="id" placeholder="Sélectionner une catégorie" class="w-100"
                                        :filter="true" :showClear="true" @filter="onFilterCategories"
                                        :loading="loading" />

                                    <small v-if="submitted && !product.category_id" class="p-error">La catégorie est
                                        requise</small>
                                </div>

                                <div class="col">
                                    <div class="d-flex gap-5">
                                        <div class="d-flex flex-column">
                                            <label for="is_active" class="ml-2">Produit actif</label>
                                            <Checkbox id="is_active" v-model="product.is_active" :binary="true" />
                                        </div>
                                        <div class="flex align-items-center">
                                            <label for="is_featured" class="ml-2">Mettre en avant</label>
                                            <Checkbox id="is_featured" v-model="product.is_featured" :binary="true" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="form-side-column">
                                <div class="field">
                                    <label>Images du produit</label>
                                    <FileUpload mode="advanced" multiple accept="image/*" :maxFileSize="5000000"
                                        customUpload @uploader="onImageUpload" :auto="true" chooseLabel="Choisir"
                                        cancelLabel="Annuler">
                                        <template #empty>
                                            <p>Glissez-déposez les images ici pour les télécharger.</p>
                                        </template>
                                    </FileUpload>
                                </div>
                            </div>
                            <br>
                            <div class="field">
                                <label for="description">Description</label>
                                <Editor id="description" v-model="product.description" editorStyle="height: 250px" />
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
import InputNumber from 'primevue/inputnumber';
import Editor from 'primevue/editor';
import Checkbox from 'primevue/checkbox';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import axios from 'axios';
import { useAuthStore } from '../../store/auth';
import { useCategoriesStore } from '../../store/categories';
import AdminLayout from '../../components/layout/AdminLayout.vue';
import Select from 'primevue/select';
import { useProductsStore } from '../../store/products';
export default {
    name: 'ProductCreate',
    components: {
        InputText,
        InputNumber,
        Editor,
        Checkbox,
        FileUpload,
        Button,
        AdminLayout,
        Select
    },
    setup() {
        const router = useRouter();
        const toast = useToast();
        const authStore = useAuthStore();
        const categoriesStore = useCategoriesStore();
        const productsStore = useProductsStore();
        const submitted = ref(false);
        const saving = ref(false);
        const categories = ref([]);
        const previewImages = ref([]);
        const uploadedImages = ref([]);
        const thumbnailIndex = ref(0);
        const loading = ref(false);
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

                // Ajouter les champs textuels et les booléens
                Object.keys(product).forEach(key => {
                    if (product[key] !== null && product[key] !== undefined) {
                        // Traitement spécial pour les booléens
                        if (typeof product[key] === 'boolean') {
                            // Convertir en 0/1 ou envoyer la valeur réelle en string
                            formData.append(key, product[key] ? '1' : '0');
                            // Alternativement: formData.append(key, product[key].toString());
                        } else {
                            formData.append(key, product[key]);
                        }
                    }
                });

                // Ajouter les images
                uploadedImages.value.forEach((image, index) => {
                    formData.append('images[]', image.file);
                });

                // Si des images ont été sélectionnées, définir la première comme miniature
                if (uploadedImages.value.length > 0 && thumbnailIndex.value !== null) {
                    formData.append('thumbnail', uploadedImages.value[thumbnailIndex.value].file);
                }

                // utiliser la fonction createProduct du store
                await productsStore.createProduct(formData);

                toast.add({
                    severity: 'success',
                    summary: 'Succès',
                    detail: 'Produit créé avec succès',
                    life: 3000
                });

                // Redirection vers la liste des produits
                router.push({ name: 'products.index' });
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

        const navigateBack = () => {
            router.push({ name: 'products.index' });
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
            thumbnailIndex,
            saveProduct,
            onImageUpload,
            removeImage,
            setAsThumbnail,
            navigateBack,
            onFilterCategories,
            loading
        };
    }
}
</script>

<style scoped>
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

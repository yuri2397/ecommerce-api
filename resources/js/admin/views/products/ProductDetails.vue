<template>
    <AdminLayout>
        <div class="product-details-page">
            <div class="card-header">
                <div class="header-buttons">
                    <Button icon="pi pi-arrow-left" label="Retour à la liste" class="p-button-secondary"
                        @click="navigateBack" />
                    <Button icon="pi pi-pencil" label="Modifier" class="p-button-success mr-2" @click="editProduct" />

                </div>
                <h1 class="card-title">Détails du Produit</h1>

            </div>
            <div class="card">
                <div v-if="loading" class="loading-container">
                    <ProgressSpinner />
                    <p>Chargement du produit...</p>
                </div>

                <div v-else class="product-content">
                    <div class="product-grid">
                        <!-- Colonne de gauche: Images du produit -->
                        <div class="product-images">
                            <div class="main-image">
                                <img :src="selectedImage || product.thumbnail_url || '/img/no-image.png'"
                                    alt="Image principale du produit" class="main-image-display" />
                            </div>

                            <!-- Section de galerie d'images complète à ajouter à ProductDetails.vue -->
                            <Galleria :value="images" :responsiveOptions="responsiveOptions" :numVisible="5"
                                containerStyle="max-width: 640px">
                                <template #item="slotProps">
                                    <img :src="slotProps.item.itemImageSrc" :alt="slotProps.item.alt"
                                        style="width: 100%" />
                                </template>
                                <template #thumbnail="slotProps">
                                    <img :src="slotProps.item.thumbnailImageSrc" :alt="slotProps.item.alt" />
                                </template>
                            </Galleria>


                            <div v-if="product.images && product.images.length > 0" class="image-gallery">
                                <div v-for="image in product.images" :key="image.id" class="gallery-item"
                                    :class="{ 'selected': selectedImage === image.url }"
                                    @click="selectedImage = image.url">
                                    <img :src="image.thumb_url || image.url" :alt="product.name" />
                                </div>
                            </div>
                        </div>

                        <!-- Colonne de droite: Informations du produit -->
                        <div class="product-info">
                            <div class="info-section">
                                <h2>Informations générales</h2>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-label">Nom:</div>
                                        <div class="info-value">{{ product.name }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">SKU:</div>
                                        <div class="info-value">{{ product.sku }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Prix:</div>
                                        <div class="info-value">{{ formatPrice(product.price) }}</div>
                                    </div>
                                    <div v-if="product.sale_price" class="info-item">
                                        <div class="info-label">Prix promotionnel:</div>
                                        <div class="info-value">{{ formatPrice(product.sale_price) }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Catégorie:</div>
                                        <div class="info-value">{{ product.category?.name || 'Non catégorisé' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Stock:</div>
                                        <div class="info-value">
                                            <Badge :severity="getStockSeverity(product.stock_quantity)"
                                                :value="getStockLabel(product.stock_quantity)" />
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Statut:</div>
                                        <div class="info-value">
                                            <Badge :severity="product.is_active ? 'success' : 'danger'"
                                                :value="product.is_active ? 'Actif' : 'Inactif'" />
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">En avant:</div>
                                        <div class="info-value">
                                            <Badge :severity="product.is_featured ? 'info' : 'secondary'"
                                                :value="product.is_featured ? 'Oui' : 'Non'" />
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Créé le:</div>
                                        <div class="info-value">{{ formatDate(product.created_at) }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Dernière mise à jour:</div>
                                        <div class="info-value">{{ formatDate(product.updated_at) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="info-section">
                                <h2>Description</h2>
                                <div class="product-description"
                                    v-html="product.description || 'Aucune description disponible.'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Section des commentaires et avis -->
                    <div v-if="product.comments && product.comments.length > 0" class="comments-section">
                        <h2>Commentaires & Avis ({{ product.comments.length }})</h2>

                        <div class="rating-summary" v-if="averageRating">
                            <span class="average-rating">{{ averageRating.toFixed(1) }}</span>
                            <Rating :modelValue="averageRating" readonly :cancel="false" />
                            <span class="rating-count">({{ product.comments.length }} avis)</span>
                        </div>

                        <div class="comments-list">
                            <div v-for="comment in product.comments" :key="comment.id" class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-user">
                                        <i class="pi pi-user"></i>
                                        <span>{{ comment.user?.name || 'Utilisateur anonyme' }}</span>
                                    </div>
                                    <div class="comment-rating" v-if="comment.rating">
                                        <Rating :modelValue="comment.rating" readonly :cancel="false" />
                                    </div>
                                    <div class="comment-date">{{ formatDate(comment.created_at) }}</div>
                                </div>
                                <div class="comment-content">{{ comment.content }}</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="comments-section empty">
                        <h2>Commentaires & Avis</h2>
                        <p>Aucun commentaire pour ce produit.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Rating from 'primevue/rating';
import ProgressSpinner from 'primevue/progressspinner';
import { useAuthStore } from '../../store/auth';
import AdminLayout from '../../components/layout/AdminLayout.vue';
import { useProductsStore } from '../../store/products';
import Galleria from 'primevue/galleria';
export default {
    name: 'ProductDetails',
    components: {
        Button,
        Badge,
        Rating,
        ProgressSpinner,
        AdminLayout,
        Galleria
    },
    setup() {
        const router = useRouter();
        const route = useRoute();
        const toast = useToast();
        const authStore = useAuthStore();
        const productsStore = useProductsStore();
        const productId = route.params.id;
        const loading = ref(true);
        const product = ref({});
        const selectedImage = ref('');

        const images = ref([]);
        const responsiveOptions = ref([
            {
                breakpoint: '1024px',
                numVisible: 3,
                numScroll: 3
            },
            {
                breakpoint: '768px',
                numVisible: 2,
                numScroll: 2
            }
        ]);

        // Calculer la note moyenne des commentaires
        const averageRating = computed(() => {
            if (!product.value.comments || product.value.comments.length === 0) {
                return 0;
            }

            const ratings = product.value.comments
                .filter(comment => comment.rating)
                .map(comment => comment.rating);

            if (ratings.length === 0) {
                return 0;
            }

            return ratings.reduce((sum, rating) => sum + rating, 0) / ratings.length;
        });

        const loadProduct = async () => {
            try {
                const response = await productsStore.fetchProductById(productId, true);
                console.log(response);
                product.value = response;

                // Définir l'image principale par défaut
                if (product.value.images && product.value.images.length > 0) {
                    selectedImage.value = product.value.thumbnail_url || product.value.images[0].url;
                }
            } catch (error) {
                console.error('Erreur lors du chargement du produit:', error);
                toast.add({
                    severity: 'error',
                    summary: 'Erreur',
                    detail: 'Impossible de charger les détails du produit',
                    life: 3000
                });
                navigateBack();
            } finally {
                loading.value = false;
            }
        };

        const formatPrice = (price) => {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'XOF'
            }).format(price);
        };

        const formatDate = (dateString) => {
            if (!dateString) return '';
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        };

        const getStockSeverity = (quantity) => {
            if (quantity <= 0) return 'danger';
            if (quantity <= 5) return 'warning';
            return 'success';
        };

        const getStockLabel = (quantity) => {
            if (quantity <= 0) return 'Épuisé';
            if (quantity === 1) return '1 unité';
            return `${quantity} unités`;
        };

        const editProduct = () => {
            router.push({ name: 'products.edit', params: { id: productId } });
        };

        const navigateBack = () => {
            router.push({ name: 'products.index' });
        };

        onMounted(() => {
            loadProduct();
        });

        return {
            product,
            loading,
            selectedImage,
            averageRating,
            formatPrice,
            formatDate,
            getStockSeverity,
            getStockLabel,
            editProduct,
            navigateBack,
            images,
            responsiveOptions
        };
    }
}
</script>

<style scoped>
.product-details-page {
    margin-bottom: 2rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.header-buttons {
    display: flex;
    gap: 0.5rem;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.product-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.product-images {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.main-image {
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.main-image-display {
    width: 100%;
    height: 400px;
    object-fit: cover;
    background-color: #f8f9fa;
    display: block;
}

.image-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.gallery-item {
    width: 60px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: border-color 0.2s ease;
}

.gallery-item.selected {
    border-color: #3f51b5;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.full-gallery-section {
    margin-top: 2rem;
    border-top: 1px solid #e9ecef;
    padding-top: 1.5rem;
}

.full-gallery-section h2 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
    color: #3f51b5;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.full-image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.full-gallery-item {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

.full-gallery-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.full-gallery-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

@media (max-width: 768px) {
    .full-image-gallery {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .full-gallery-item img {
        height: 150px;
    }
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-section {
    margin-bottom: 1.5rem;
}

.info-section h2 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
    color: #3f51b5;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.875rem;
}

.info-value {
    font-size: 1rem;
}

.product-description {
    line-height: 1.6;
}

.comments-section {
    margin-top: 2rem;
    border-top: 1px solid #e9ecef;
    padding-top: 1.5rem;
}

.comments-section h2 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
    color: #3f51b5;
}

.comments-section.empty {
    color: #6c757d;
}

.rating-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.average-rating {
    font-size: 2rem;
    font-weight: bold;
    color: #3f51b5;
}

.rating-count {
    color: #6c757d;
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.comment-item {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.comment-user {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.comment-date {
    color: #6c757d;
    font-size: 0.875rem;
}

.comment-content {
    line-height: 1.5;
}

@media (max-width: 992px) {
    .product-grid {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

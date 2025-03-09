import { defineStore } from 'pinia';
import axios from 'axios';
import { useAuthStore } from './auth';

export const useProductsStore = defineStore('products', {
    state: () => ({
        products: [],
        loading: false,
        error: null,
        totalProducts: 0
    }),

    getters: {
        getProductById: (state) => (id) => {
            return state.products.find(product => product.id === id);
        },

        getProductsForCategory: (state) => (categoryId) => {
            return state.products.filter(product => product.category_id === categoryId);
        },

        getFeaturedProducts: (state) => {
            return state.products.filter(product => product.is_featured);
        },

        getActiveProducts: (state) => {
            return state.products.filter(product => product.is_active);
        }
    },

    actions: {
        async fetchProducts(params = {}) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.get('/api/admin/products', {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                this.products = response.data.data;
                this.totalProducts = response.data.meta?.total || response.data.data.length;

                return this.products;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du chargement des produits';
                console.error('Erreur lors du chargement des produits:', error);
                return [];
            } finally {
                this.loading = false;
            }
        },

        async fetchProductById(id, loadRelations = true) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const params = loadRelations ? { with: ['category', 'comments'] } : {};

                const response = await axios.get(`/api/admin/products/${id}`, {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du chargement du produit';
                console.error('Erreur lors du chargement du produit:', error);
                return null;
            } finally {
                this.loading = false;
            }
        },

        async createProduct(productData) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.post('/api/admin/products', productData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la création du produit';
                console.error('Erreur lors de la création du produit:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateProduct(id, productData) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                // Ajouter la méthode PUT pour Laravel
                productData.append('_method', 'PUT');

                const response = await axios.post(`/api/admin/products/${id}`, productData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                await this.fetchProducts();

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du produit';
                console.error('Erreur lors de la mise à jour du produit:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteProduct(id) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                await axios.delete(`/api/admin/products/${id}`, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                await this.fetchProducts();

                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression du produit';
                console.error('Erreur lors de la suppression du produit:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async setProductThumbnail(productId, imageId) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.post(`/api/admin/products/${productId}/thumbnail/${imageId}`, {}, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la définition de l\'image principale';
                console.error('Erreur lors de la définition de l\'image principale:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteProductImage(productId, imageId) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.delete(`/api/admin/media/${imageId}`, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression de l\'image';
                console.error('Erreur lors de la suppression de l\'image:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});

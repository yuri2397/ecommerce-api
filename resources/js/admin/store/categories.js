import { defineStore } from 'pinia';
import axios from 'axios';
import { useAuthStore } from './auth';

export const useCategoriesStore = defineStore('categories', {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
        totalCategories: 0
    }),

    getters: {
        getCategoryById: (state) => (id) => {
            return state.categories.find(category => category.id === id);
        },

        getRootCategories: (state) => {
            return state.categories.filter(category => !category.parent_id);
        },

        getCategoriesForDropdown: (state) => {
            return state.categories.map(category => ({
                id: category.id,
                name: category.name,
                parent_id: category.parent_id
            }));
        }
    },

    actions: {
        async fetchCategories(params = {}) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.get('/api/admin/categories', {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });
                console.log(response);
                this.categories = response.data.data;
                this.totalCategories = response.data.meta?.total || response.data.data.length;

                return this.categories;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du chargement des catégories';
                console.error('Erreur lors du chargement des catégories:', error);
                return [];
            } finally {
                this.loading = false;
            }
        },

        async fetchCategoriesDropdown(params = {}) {
            const authStore = useAuthStore();

            try {
                const response = await axios.get('/api/admin/categories/dropdown', {
                    params,
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data.data;
            } catch (error) {
                console.error('Erreur lors du chargement des catégories pour dropdown:', error);
                return [];
            }
        },

        async fetchCategoryById(id) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.get(`/api/admin/categories/${id}`, {
                    params: {
                        with: ['parent', 'children']
                    },
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du chargement de la catégorie';
                console.error('Erreur lors du chargement de la catégorie:', error);
                return null;
            } finally {
                this.loading = false;
            }
        },

        async createCategory(categoryData) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.post('/api/admin/categories', categoryData, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                await this.fetchCategories();

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la création de la catégorie';
                console.error('Erreur lors de la création de la catégorie:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateCategory(id, categoryData) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                const response = await axios.put(`/api/admin/categories/${id}`, categoryData, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                await this.fetchCategories();

                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la mise à jour de la catégorie';
                console.error('Erreur lors de la mise à jour de la catégorie:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory(id) {
            this.loading = true;
            this.error = null;

            const authStore = useAuthStore();

            try {
                await axios.delete(`/api/admin/categories/${id}`, {
                    headers: {
                        Authorization: `Bearer ${authStore.token}`
                    }
                });

                await this.fetchCategories();

                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression de la catégorie';
                console.error('Erreur lors de la suppression de la catégorie:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});

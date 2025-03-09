import { defineStore } from 'pinia';

export const useCategoriesStore = defineStore('categories', {
    state: () => ({
        categories: []
    }),

    actions: {
        async fetchCategories() {
            const response = await axios.get('/api/categories');
            this.categories = response.data;
        }
    }
    
})

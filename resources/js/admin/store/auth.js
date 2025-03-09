import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: localStorage.getItem('user') ? JSON.parse(localStorage.getItem('user')) : null,
        token: localStorage.getItem('auth_token'),
        loading: false,
        error: null
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        userFullName: (state) => state.user ? `${state.user.name}` : '',
    },

    actions: {
        async login(email, password) {
            this.loading = true;
            this.error = null;

            try {
                // D'abord, obtenir le cookie CSRF
                await axios.get('/sanctum/csrf-cookie');

                // Tentative de connexion
                const response = await axios.post('/api/login', {
                    email,
                    password
                });

                // Si la connexion est réussie, récupérer les informations de l'utilisateur
                this.token = response.data.token;
                localStorage.setItem('auth_token', this.token);

                await this.fetchUser();

                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Une erreur est survenue lors de la connexion';
                console.error('Erreur de connexion:', error);
                return false;
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            this.loading = true;

            try {
                const response = await axios.get('/api/user', {
                    headers: {
                        Authorization: `Bearer ${this.token}`
                    }
                });

                this.user = response.data;
                localStorage.setItem('user', JSON.stringify(this.user));
                return this.user;
            } catch (error) {
                this.user = null;
                this.error = 'Erreur lors de la récupération des informations utilisateur';
                console.error('Erreur de récupération utilisateur:', error);
                return null;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;

            try {
                // Appel à l'API de déconnexion
                await axios.post('/api/logout', {}, {
                    headers: {
                        Authorization: `Bearer ${this.token}`
                    }
                });
            } catch (error) {
                console.error('Erreur lors de la déconnexion:', error);
            } finally {
                // Nettoyer l'état local quoi qu'il arrive
                this.user = null;
                this.token = null;
                localStorage.removeItem('auth_token');
                this.loading = false;
            }
        },

        async initialize() {
            // Si un token existe, essayer de récupérer l'utilisateur au chargement de l'app
            if (this.token) {
                return await this.fetchUser();
            }
            return null;
        }
    }
});

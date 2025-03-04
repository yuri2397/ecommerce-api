<template>
    <div class="login-container">
        <div class="login-panel">
            <Card>
                <template #title>
                    <h2 class="login-title">Administration</h2>
                    <p class="login-subtitle">Connectez-vous pour accéder à l'administration</p>
                </template>
                <template #content>
                    <form @submit.prevent="handleLogin" class="p-fluid">
                        <div class="field">
                            <label for="email">Email</label>
                            <InputText id="email" v-model="email" type="email" placeholder="Entrez votre email"
                                :class="{ 'p-invalid': submitted && !email }" aria-required="true" />
                            <small v-if="submitted && !email" class="p-error">Email requis</small>
                        </div>

                        <div class="field">
                            <label for="password">Mot de passe</label>
                            <Password id="password" v-model="password" placeholder="Entrez votre mot de passe"
                                :feedback="false" toggleMask :class="{ 'p-invalid': submitted && !password }"
                                aria-required="true" />
                            <small v-if="submitted && !password" class="p-error">Mot de passe requis</small>
                        </div>

                        <div class="field">
                            <Button type="submit" label="Se connecter" icon="pi pi-sign-in" :loading="loading" />
                        </div>

                        <div v-if="error" class="p-error mt-3">{{ error }}</div>
                    </form>
                </template>
            </Card>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../store/auth';
import Password from 'primevue/password';

export default {
    name: 'LoginView',
    components: {
        Password
    },
    setup() {
        const email = ref('');
        const password = ref('');
        const submitted = ref(false);
        const loading = ref(false);
        const error = ref('');
        const router = useRouter();
        const authStore = useAuthStore();

        const handleLogin = async () => {
            submitted.value = true;

            // Validation simple
            if (!email.value || !password.value) {
                return;
            }

            loading.value = true;
            error.value = '';

            try {
                const success = await authStore.login(email.value, password.value);

                if (success) {
                    router.push({ name: 'dashboard' });
                } else {
                    error.value = authStore.error || 'Identifiants invalides';
                }
            } catch (err) {
                error.value = 'Une erreur est survenue. Veuillez réessayer.';
                console.error('Erreur de connexion:', err);
            } finally {
                loading.value = false;
            }
        };

        return {
            email,
            password,
            submitted,
            loading,
            error,
            handleLogin
        };
    }
}
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-color: #f5f7f9;
}

.login-panel {
    width: 100%;
    max-width: 450px;
    padding: 0 1rem;

}

.login-title {
    margin-top: 0;
    text-align: center;
    color: #3f51b5;
}

.login-subtitle {
    text-align: center;
    font-weight: normal;
    font-size: 1.2rem;
    color: #fff;
}

.field {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.p-error {
    color: #f44336;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.mt-3 {
    margin-top: 1rem;
}
</style>

<script setup>
import { onMounted, ref } from 'vue';
import StaffMenu from './StaffMenu.vue';

import StaffInvitations from './StaffInvitations.vue';

import { readCsrfToken } from './csrf.js';

import StaffOpeningHours from './StaffOpeningHours.vue';


const user = ref(null);
const email = ref('');
const password = ref('');
const checkingSession = ref(true);
const busy = ref(false);
const error = ref('');

const errorMessages = {
    419: 'Sessione scaduta. Ricarica la pagina e riprova.',
    422: 'Le credenziali non sono corrette.',
    429: 'Troppi tentativi. Riprova tra un minuto.',
};

async function updateSession(method) {
    if (busy.value) return;

    busy.value = true;
    error.value = '';

    try {
        const headers = { Accept: 'application/json' };
        const options = { method, headers, credentials: 'same-origin' };

        if (method !== 'GET') {
            const token = readCsrfToken();


            if (!token) {
                error.value = 'Ricarica la pagina e riprova.';
                return;
            }

            headers['X-XSRF-TOKEN'] = token;
        }

        if (method === 'POST') {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify({
                email: email.value,
                password: password.value,
            });
        }

        const response = await fetch('/api/session', options);

        if (response.status === 401 || response.status === 204) {
            user.value = null;
            return;
        }

        if (!response.ok) {
            error.value = errorMessages[response.status]
                ?? 'Impossibile completare la richiesta. Riprova.';
            return;
        }

        const data = await response.json();
        user.value = data.user;
    } catch {
        error.value = 'Impossibile completare la richiesta. Riprova.';
    } finally {
        if (method === 'POST') password.value = '';

        checkingSession.value = false;
        busy.value = false;
    }
}

onMounted(() => updateSession('GET'));
</script>

<template>
    <main class="container py-5">
        <div class="col-md-7 col-lg-5 mx-auto">
            <h1 class="h3 mb-4">Area riservata</h1>

            <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
            <p v-if="checkingSession" role="status">Verifica della sessione…</p>

            <section v-else-if="user" class="card card-body">
                <h2 class="h5">{{ user.name }}</h2>
                <p>{{ user.role === 'owner' ? 'Titolare' : 'Collaboratore' }}</p>

                <button
                    type="button"
                    class="btn btn-outline-secondary align-self-start"
                    :disabled="busy"
                    @click="updateSession('DELETE')"
                >
                    {{ busy ? 'Disconnessione…' : 'Esci' }}
                </button>
            </section>

            <form v-else class="card card-body" @submit.prevent="updateSession('POST')">
                <fieldset :disabled="busy">
                    <div class="mb-3">
                        <label for="staff-email" class="form-label">Email</label>
                        <input
                            id="staff-email"
                            v-model.trim="email"
                            type="email"
                            class="form-control"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="staff-password" class="form-label">Password</label>
                        <input
                            id="staff-password"
                            v-model="password"
                            type="password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">
                        {{ busy ? 'Accesso in corso…' : 'Accedi' }}
                    </button>
                </fieldset>
            </form>

            <StaffMenu v-if="user" :can-manage-featured="user.role === 'owner'" />
            <StaffOpeningHours v-if="user" />
            <StaffInvitations v-if="user?.role === 'owner'" />

            <a href="/" class="d-inline-block mt-3">Torna al menù</a>
        </div>
    </main>
</template>
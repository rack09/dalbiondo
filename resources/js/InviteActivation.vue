<script setup>
import { ref } from 'vue';
import { postJson } from './postJson.js';

const token = window.location.hash.slice(1);
const hasToken = /^[0-9a-f]{64}$/.test(token);
const name = ref('');
const password = ref('');
const confirmation = ref('');
const busy = ref(false);
const complete = ref(false);
const error = ref('');

async function activate() {
    if (busy.value || !hasToken) return;

    busy.value = true;
    error.value = '';

    try {
        await postJson('/api/staff/registrations', {
            token,
            name: name.value,
            password: password.value,
            password_confirmation: confirmation.value,
        });

        complete.value = true;
        password.value = '';
        confirmation.value = '';
    } catch (exception) {
        error.value = exception.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <main class="container py-5">
        <section class="col-md-7 col-lg-5 mx-auto">
            <h1 class="h3 mb-4">Attiva account</h1>

            <p v-if="complete" class="alert alert-success" role="status">
                Account attivato. <a href="/area-riservata">Accedi</a>
            </p>
            <p v-else-if="!hasToken" class="alert alert-danger" role="alert">
                Il link di invito è incompleto o non valido.
            </p>

            <form v-else class="card card-body" @submit.prevent="activate">
                <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>

                <fieldset :disabled="busy">
                    <label class="form-label d-block mb-3">
                        Nome
                        <input v-model.trim="name" type="text" class="form-control"
                               autocomplete="name" maxlength="255" required>
                    </label>

                    <label class="form-label d-block mb-3">
                        Password
                        <input v-model="password" type="password" class="form-control"
                               autocomplete="new-password" minlength="12" required>
                    </label>

                    <label class="form-label d-block mb-3">
                        Conferma password
                        <input v-model="confirmation" type="password" class="form-control"
                               autocomplete="new-password" required>
                    </label>

                    <button type="submit" class="btn btn-primary">
                        {{ busy ? 'Attivazione in corso…' : 'Attiva account' }}
                    </button>
                </fieldset>
            </form>
        </section>
    </main>
</template>
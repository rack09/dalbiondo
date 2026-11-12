<script setup>
import { ref } from 'vue';
import { postJson } from './postJson.js';

const email = ref('');
const invitation = ref(null);
const busy = ref(false);
const error = ref('');

async function generateInvitation(address, replace = false) {
    if (busy.value) return;

    busy.value = true;
    error.value = '';
    invitation.value = null;

    try {
        invitation.value = await postJson('/api/staff/invitations', {
            email: address,
            replace,
        });
    } catch (exception) {
        error.value = exception.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <section class="mt-4">
        <h2 class="h4 mb-3">Collaboratori</h2>

        <form class="card card-body" @submit.prevent="generateInvitation(email)">
            <fieldset :disabled="busy">
                <label class="form-label d-block mb-3">
                    Email del collaboratore
                    <input v-model.trim="email" type="email" class="form-control"
                           maxlength="255" required>
                </label>

                <button type="submit" class="btn btn-primary">
                    {{ busy ? 'Generazione in corso…' : 'Genera invito' }}
                </button>
            </fieldset>

            <p v-if="error" class="alert alert-danger mt-3 mb-0" role="alert">
                {{ error }}
            </p>

            <label v-if="invitation?.invitation_url" class="form-label d-block mt-3 mb-0">
                Link di invito per {{ invitation.email }}
                <input :value="invitation.invitation_url" class="form-control"
                    readonly @focus="$event.target.select()">
            </label>

            <div v-else-if="invitation" class="alert alert-warning mt-3 mb-0">
                <p class="mb-2" role="status">
                    Esiste già un invito valido per {{ invitation.email }}.
                    Se lo sostituisci, il link precedente verrà annullato.
                </p>

                <button type="button" class="btn btn-outline-dark btn-sm"
                    :disabled="busy" @click="generateInvitation(invitation.email, true)">
                    Sostituisci link
                </button>
            </div>
        </form>
    </section>
</template>
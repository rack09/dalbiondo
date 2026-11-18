<script setup>
import { computed, ref } from 'vue';
import { sendJson } from './postJson.js';

const props = defineProps({
    user: { type: Object, required: true },
});

const emit = defineEmits(['profile-saved']);
const canEdit = computed(() => props.user.role === 'collaborator');
const name = ref(props.user.name);
const currentPassword = ref('');
const password = ref('');
const confirmation = ref('');
const saving = ref('');
const error = ref('');
const success = ref('');

async function saveName() {
    if (!canEdit.value || saving.value) return;

    saving.value = 'name';
    error.value = '';
    success.value = '';

    try {
        const { user } = await sendJson('/api/staff/profile', { name: name.value }, 'PATCH');

        name.value = user.name;
        emit('profile-saved', user);
        success.value = 'Nome aggiornato.';
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = '';
    }
}

async function savePassword() {
    if (!canEdit.value || saving.value) return;

    saving.value = 'password';
    error.value = '';
    success.value = '';

    try {
        const result = await sendJson('/api/staff/profile/password', {
            current_password: currentPassword.value,
            password: password.value,
            password_confirmation: confirmation.value,
        }, 'PUT');

        currentPassword.value = '';
        password.value = '';
        confirmation.value = '';
        success.value = result.message;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = '';
    }
}
</script>

<template>
    <div>
        <header class="staff-page-heading">
            <div>
                <h1>Profilo</h1>
                <p>{{ user.role === 'owner' ? 'Titolare' : 'Collaboratore' }}</p>
            </div>
        </header>

        <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
        <p v-if="success" class="alert alert-success" role="status">{{ success }}</p>

        <div v-if="canEdit" class="staff-section-grid">
            <form class="staff-panel" aria-labelledby="profile-details-title" @submit.prevent="saveName">
                <h2 id="profile-details-title">Dati del profilo</h2>
                <p class="small text-body-secondary text-break">{{ user.email }}</p>
                <fieldset :disabled="Boolean(saving)">
                    <div class="mb-3">
                        <label for="profile-name" class="form-label">Nome</label>
                        <input
                            id="profile-name" v-model.trim="name" type="text" class="form-control"
                            autocomplete="name" maxlength="255" required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        {{ saving === 'name' ? 'Salvataggio…' : 'Salva nome' }}
                    </button>
                </fieldset>
            </form>

            <form class="staff-panel" aria-labelledby="profile-password-title" @submit.prevent="savePassword">
                <h2 id="profile-password-title">Cambia password</h2>
                <fieldset :disabled="Boolean(saving)">
                    <div class="mb-3">
                        <label for="profile-current-password" class="form-label">Password attuale</label>
                        <input
                            id="profile-current-password" v-model="currentPassword" name="current_password" type="password"
                            class="form-control" autocomplete="current-password" required
                        >
                    </div>
                    <div class="mb-3">
                        <label for="profile-new-password" class="form-label">Nuova password</label>
                        <input
                            id="profile-new-password" v-model="password" name="password" type="password" class="form-control"
                            autocomplete="new-password" minlength="12" aria-describedby="profile-password-help" required
                        >
                        <div id="profile-password-help" class="form-text">Almeno 12 caratteri.</div>
                    </div>
                    <div class="mb-3">
                        <label for="profile-password-confirmation" class="form-label">Conferma la nuova password</label>
                        <input
                            id="profile-password-confirmation" v-model="confirmation" name="password_confirmation" type="password"
                            class="form-control" autocomplete="new-password" required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        {{ saving === 'password' ? 'Salvataggio…' : 'Aggiorna password' }}
                    </button>
                </fieldset>
            </form>
        </div>

        <div v-else class="staff-panel">
            <h2>Dati del profilo</h2>
            <p class="fw-semibold text-break mb-1">{{ user.name }}</p>
            <p class="small text-body-secondary text-break">{{ user.email }}</p>
            <p v-if="user.role === 'owner'" class="mb-0">Per cambiare la password del titolare, contatta l’assistenza tecnica.</p>
        </div>
    </div>
</template>

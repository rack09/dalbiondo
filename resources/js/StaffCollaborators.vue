<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import { sendJson } from './postJson.js';

const endpoint = '/api/staff/collaborators';
const collaborators = ref([]);
const selectedId = ref(null);
const editor = ref(null);
const name = ref('');
const email = ref('');
const password = ref('');
const confirmation = ref('');
const loading = ref(true);
const saving = ref('');
const loadError = ref('');
const saveError = ref('');
const notice = ref('');

const selected = computed(() => collaborators.value.find(user => user.id === selectedId.value));
const orderedCollaborators = computed(() => [...collaborators.value].sort((first, second) =>
    Number(second.is_active) - Number(first.is_active) || first.name.localeCompare(second.name, 'it'),
));
const busy = computed(() => loading.value || Boolean(saving.value));

async function loadCollaborators() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch(endpoint, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error();

        const users = await response.json();
        if (!Array.isArray(users)) throw new Error();
        collaborators.value = users;
    } catch {
        loadError.value = 'Impossibile caricare i collaboratori.';
    } finally {
        loading.value = false;
    }
}

function clearEditorFeedback() {
    password.value = '';
    confirmation.value = '';
    saveError.value = '';
    notice.value = '';
}

async function openEditor(user) {
    clearEditorFeedback();
    selectedId.value = user.id;
    name.value = user.name;
    email.value = user.email;

    await nextTick();
    editor.value?.focus({ preventScroll: true });
    editor.value?.scrollIntoView({ block: 'nearest' });
}

async function closeEditor() {
    const id = selectedId.value;
    selectedId.value = null;
    clearEditorFeedback();

    await nextTick();
    document.getElementById('manage-collaborator-' + id)?.focus();
}

async function save(kind, suffix, method, values) {
    if (!selected.value || busy.value) return null;

    const id = selected.value.id;
    saving.value = kind;
    saveError.value = '';
    notice.value = '';

    try {
        const updated = await sendJson(endpoint + '/' + id + suffix, values, method);
        collaborators.value = collaborators.value.map(user => user.id === id ? updated : user);

        return updated;
    } catch (exception) {
        saveError.value = exception.message;
        return null;
    } finally {
        saving.value = '';
    }
}

async function saveDetails() {
    const saved = await save('details', '', 'PATCH', { name: name.value, email: email.value });
    if (!saved) return;

    name.value = saved.name;
    email.value = saved.email;
    notice.value = 'Dati del collaboratore aggiornati.';
}

async function savePassword() {
    const saved = await save('password', '/password', 'PUT', {
        password: password.value,
        password_confirmation: confirmation.value,
    });
    if (!saved) return;

    password.value = '';
    confirmation.value = '';
    notice.value = 'Password aggiornata.';
}

async function toggleStatus() {
    const saved = await save('status', '/status', 'PATCH', { is_active: !selected.value.is_active });
    if (!saved) return;

    notice.value = saved.is_active ? 'Account riattivato.' : 'Account disattivato.';
}

onMounted(loadCollaborators);
</script>

<template>
    <div :class="{ 'staff-section-grid': selected }">
        <section class="staff-panel" aria-labelledby="staff-accounts-title">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <h2 id="staff-accounts-title" class="mb-0">Account dei collaboratori</h2>
                <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy" @click="loadCollaborators">
                    Aggiorna elenco
                </button>
            </div>

            <p v-if="loading" class="mb-0" role="status">Caricamento dei collaboratori…</p>
            <p v-else-if="loadError" class="alert alert-danger mb-0" role="alert">{{ loadError }}</p>
            <p v-else-if="collaborators.length === 0" class="mb-0">Nessun collaboratore registrato.</p>
            <ul v-else class="list-unstyled mb-0">
                <li
                    v-for="person in orderedCollaborators" :key="person.id"
                    class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-3"
                >
                    <div class="text-break">
                        <h3 class="mb-1">{{ person.name }}</h3>
                        <p class="small text-body-secondary mb-1">{{ person.email }}</p>
                        <span class="badge" :class="person.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                            {{ person.is_active ? 'Attivo' : 'Disattivato' }}
                        </span>
                    </div>
                    <button
                        :id="'manage-collaborator-' + person.id"
                        class="btn btn-outline-secondary btn-sm" type="button" :disabled="busy"
                        :aria-label="'Gestisci ' + person.name" :aria-expanded="selectedId === person.id"
                        @click="openEditor(person)"
                    >Gestisci</button>
                </li>
            </ul>
        </section>

        <section v-if="selected" ref="editor" class="staff-panel" tabindex="-1" aria-labelledby="staff-account-editor-title">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 id="staff-account-editor-title" class="text-break mb-0">{{ selected.name }}</h2>
                <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="busy" @click="closeEditor">
                    Chiudi
                </button>
            </div>

            <p v-if="saveError" class="alert alert-danger" role="alert">{{ saveError }}</p>
            <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>

            <form aria-labelledby="staff-account-details-title" @submit.prevent="saveDetails">
                <h3 id="staff-account-details-title">Nome ed email di accesso</h3>
                <fieldset :disabled="busy">
                    <label class="form-label d-block mb-2">
                        Nome
                        <input v-model.trim="name" type="text" class="form-control" maxlength="255" required>
                    </label>
                    <label class="form-label d-block mb-3">
                        Email di accesso
                        <input v-model.trim="email" type="email" class="form-control" maxlength="255" required>
                    </label>
                    <button type="submit" class="btn btn-primary">
                        {{ saving === 'details' ? 'Salvataggio…' : 'Salva dati' }}
                    </button>
                </fieldset>
            </form>

            <form
                class="border-top mt-3 pt-3" aria-labelledby="staff-account-password-title"
                @submit.prevent="savePassword"
            >
                <h3 id="staff-account-password-title">Cambia password</h3>
                <fieldset :disabled="busy">
                    <label class="form-label d-block mb-2">
                        Nuova password
                        <input
                            v-model="password" name="password" type="password" class="form-control"
                            autocomplete="new-password" minlength="12" aria-describedby="staff-account-password-help" required
                        >
                    </label>
                    <p id="staff-account-password-help" class="form-text">Almeno 12 caratteri.</p>
                    <label class="form-label d-block mb-3">
                        Conferma la nuova password
                        <input
                            v-model="confirmation" name="password_confirmation" type="password" class="form-control"
                            autocomplete="new-password" required
                        >
                    </label>
                    <button type="submit" class="btn btn-primary">
                        {{ saving === 'password' ? 'Salvataggio…' : 'Aggiorna password' }}
                    </button>
                </fieldset>
            </form>

            <div class="border-top mt-3 pt-3">
                <h3>Stato dell’account</h3>
                <p class="small text-body-secondary">
                    {{ selected.is_active
                        ? 'Disattivare l’account interrompe le sue sessioni e sospende l’accesso.'
                        : 'Riattivare l’account consente al collaboratore di accedere di nuovo.' }}
                </p>
                <button
                    type="button" class="btn" :class="selected.is_active ? 'btn-outline-danger' : 'btn-outline-primary'"
                    :disabled="busy" @click="toggleStatus"
                >
                    {{ saving === 'status' ? 'Salvataggio…' : selected.is_active ? 'Disattiva account' : 'Riattiva account' }}
                </button>
            </div>
        </section>
    </div>
</template>

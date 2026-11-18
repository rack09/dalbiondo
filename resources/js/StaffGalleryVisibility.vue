<script setup>
import { onMounted, ref } from 'vue';
import { sendJson } from './postJson.js';

const props = defineProps({
    canManage: { type: Boolean, default: false },
});

const visible = ref(null);
const saving = ref(false);
const error = ref('');

async function loadVisibility() {
    error.value = '';

    try {
        const response = await fetch('/api/staff-gallery', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();

        const data = await response.json();
        if (typeof data?.is_visible !== 'boolean') throw new Error();

        visible.value = data.is_visible;
    } catch {
        error.value = 'Impossibile caricare la visibilità della sezione.';
    }
}

async function toggleVisibility() {
    if (!props.canManage || visible.value === null || saving.value) return;

    saving.value = true;
    error.value = '';

    try {
        const gallery = await sendJson('/api/staff/gallery', { is_visible: !visible.value }, 'PUT');
        visible.value = gallery.is_visible;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        saving.value = false;
    }
}

onMounted(loadVisibility);
</script>

<template>
    <div v-if="canManage" class="staff-panel mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">Sezione dello staff sulla homepage</h2>
                <p v-if="visible === null && !error" class="mb-0" role="status">Caricamento…</p>
                <p v-else-if="visible !== null" class="small text-body-secondary mb-0" role="status">
                    {{ visible ? 'La sezione «La nostra storia» è visibile.' : 'La sezione «La nostra storia» è nascosta.' }}
                </p>
            </div>
            <button
                v-if="visible !== null" type="button" class="btn btn-outline-secondary"
                :disabled="saving" @click="toggleVisibility"
            >
                {{ saving ? 'Salvataggio…' : visible ? 'Nascondi sezione' : 'Mostra sezione' }}
            </button>
            <button v-else-if="error" type="button" class="btn btn-outline-secondary" @click="loadVisibility">
                Riprova
            </button>
        </div>
        <p v-if="error" class="alert alert-danger mt-3 mb-0" role="alert">{{ error }}</p>
    </div>
    <p v-else-if="visible === false" class="staff-panel mb-3">
        La sezione dello staff è nascosta nella homepage.
    </p>
</template>

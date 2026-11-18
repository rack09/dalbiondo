<script setup>
import { onMounted, ref } from 'vue';
import { sendJson } from './postJson.js';

const phone = ref('');
const whatsapp = ref('');
const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const success = ref('');

function fillForm(contact) {
    phone.value = contact.phone ?? '';
    whatsapp.value = contact.whatsapp ?? '';
}

async function loadContact() {
    loading.value = true;
    loadError.value = '';

    try {
        const response = await fetch('/api/contact', {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error();

        fillForm(await response.json());
    } catch {
        loadError.value = 'Impossibile caricare i contatti.';
    } finally {
        loading.value = false;
    }
}

async function saveContact() {
    if (saving.value) return;

    saving.value = true;
    saveError.value = '';
    success.value = '';

    try {
        const contact = await sendJson('/api/staff/contact', {
            phone: phone.value || null,
            whatsapp: whatsapp.value || null,
        }, 'PUT');

        fillForm(contact);
        success.value = 'Contatti aggiornati sul sito.';
    } catch (exception) {
        saveError.value = exception.message;
    } finally {
        saving.value = false;
    }
}

onMounted(loadContact);
</script>

<template>
    <div>
        <header class="staff-page-heading">
            <div>
                <h1>Contatti</h1>
                <p>Modifica i numeri visibili ai clienti sul sito.</p>
            </div>
        </header>

        <p v-if="loading" role="status">Caricamento dei contatti…</p>
        <div v-else-if="loadError" class="staff-panel">
            <p class="alert alert-danger" role="alert">{{ loadError }}</p>
            <button type="button" class="btn btn-outline-secondary" @click="loadContact">Riprova</button>
        </div>
        <form v-else class="staff-panel" aria-label="Contatti sul sito" @submit.prevent="saveContact">
            <fieldset :disabled="saving">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="contact-phone" class="form-label">Telefono</label>
                        <input
                            id="contact-phone" v-model.trim="phone" class="form-control"
                            type="tel" maxlength="40" placeholder="02 1234 5678"
                            aria-describedby="contact-number-help"
                        >
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="contact-whatsapp" class="form-label">WhatsApp</label>
                        <input
                            id="contact-whatsapp" v-model.trim="whatsapp" class="form-control"
                            type="tel" maxlength="40" placeholder="+39 333 123 4567"
                            aria-describedby="contact-number-help"
                        >
                    </div>
                </div>

                <p id="contact-number-help" class="small text-body-secondary">
                    Lascia vuoto un campo per nascondere il relativo pulsante sul sito.
                    Per numeri esteri, indica il prefisso internazionale.
                </p>
                <button type="submit" class="btn btn-primary">{{ saving ? 'Salvataggio…' : 'Salva contatti' }}</button>
            </fieldset>

            <p v-if="saveError" class="alert alert-danger mt-3 mb-0" role="alert">{{ saveError }}</p>
            <p v-if="success" class="alert alert-success mt-3 mb-0" role="status">{{ success }}</p>
        </form>
    </div>
</template>

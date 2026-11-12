<script setup>
import { onMounted, ref } from 'vue';

import { readCsrfToken } from './csrf.js';

import PizzaForm from './PizzaForm.vue';

const pizzas = ref([]);
const loading = ref(true);
const error = ref('');

const savingId = ref(null);
const updateError = ref('');
const editingId = ref(null);
const creating = ref(false);

const euro = new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
});

onMounted(async () => {
    try {
        const response = await fetch('/api/staff/pizzas', {
            headers: { Accept: 'application/json' },
        });

        if (response.status === 401) {
            error.value = 'Sessione scaduta. Ricarica la pagina e accedi di nuovo.';
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        pizzas.value = await response.json();
    } catch {
        error.value = 'Impossibile caricare le pizze. Ricarica la pagina e riprova.';
    } finally {
        loading.value = false;
    }
});


async function savePizza(pizza, changes) {
    if (savingId.value !== null) return false;

    savingId.value =  pizza?.id ?? 'new';
   
    updateError.value = '';

    try {
        const token = readCsrfToken();

        if (!token) {
            updateError.value = 'Ricarica la pagina e riprova.';
            return false;
        }

        const baseUrl = '/api/staff/pizzas';
        const url = pizza ? `${baseUrl}/${pizza.id}` : baseUrl;

        const response = await fetch(url, {
            method: pizza ? 'PATCH' : 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify(changes),
        });

        if (response.status === 401 || response.status === 419) {
            updateError.value = 'Sessione scaduta. Ricarica la pagina.';
            return false;
        }

        if (response.status === 422) {
            const { errors = {} } = await response.json();
            const field = Object.keys(errors)[0];
            const priceMessage = 'Inserisci un prezzo tra 0 e 9999,99 € con al massimo due decimali.';

            const messages = {
                name: 'Inserisci un nome di massimo 255 caratteri.',
                ingredients: 'Inserisci gli ingredienti o accorcia il testo.',
                price_trancio: priceMessage,
                price_teglia: priceMessage,
            };

            updateError.value = messages[field] ?? 'Controlla i dati inseriti.';
            return false;
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const saved = await response.json();

        if (pizza) {
            Object.assign(pizza, saved);
        } else {
            pizzas.value.push(saved);
        }

        return true;
    } catch {
        updateError.value = 'Impossibile salvare le modifiche. Riprova.';
        return false;
    } finally {
        savingId.value = null;
    }
}

function changeVisibility(pizza) {
    return savePizza(pizza, { is_visible: !pizza.is_visible });
}


function startCreating() {
    updateError.value = '';
    creating.value = true;
}

function editPizza(pizza) {
    updateError.value = '';
    editingId.value = pizza.id;
}

function cancelEditing() {
    creating.value = false;
    editingId.value = null;
    updateError.value = '';
}

async function createPizza(values) {
    if (await savePizza(null, values)) {
        cancelEditing();
    }
}

async function saveEdits(pizza, values) {
    const changes = {};

    for (const [key, value] of Object.entries(values)) {
        if (value !== String(pizza[key])) {
            changes[key] = value;
        }
    }

    if (Object.keys(changes).length === 0) {
        cancelEditing();
        return;
    }

    if (await savePizza(pizza, changes)) {
        cancelEditing();
    }
}





</script>

<template>
    <section class="mt-4">
        <h2 class="h4 mb-3">Gestione del menù</h2>

        <p v-if="updateError" class="alert alert-danger" role="alert">
            {{ updateError }}
        </p>

        <button
            v-if="!loading && !error && !creating"
            type="button"
            class="btn btn-primary btn-sm mb-3"
            :disabled="savingId !== null || editingId !== null"
            @click="startCreating"
        >
            Nuova pizza
        </button>

        <PizzaForm
            v-if="creating"
            class="border rounded p-3 mb-3"
            :busy="savingId !== null"
            submit-label="Aggiungi pizza"
            @save="createPizza"
            @cancel="cancelEditing"
        />

        <p v-if="loading" role="status">Caricamento delle pizze…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
        <p v-else-if="pizzas.length === 0">Nessuna pizza nel menù.</p>

        <ul v-else class="list-group">
            <li v-for="pizza in pizzas" :key="pizza.id" class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <h3 class="h6">{{ pizza.name }}</h3>
                    <span
                        class="badge"
                        :class="pizza.is_visible ? 'text-bg-success' : 'text-bg-secondary'"
                    >
                        {{ pizza.is_visible ? 'Visibile' : 'Nascosta' }}
                    </span>
                </div>

                <p class="mb-2">{{ pizza.ingredients }}</p>
                <p class="mb-0">
                    Trancio {{ euro.format(Number(pizza.price_trancio)) }}
                    · Teglia {{ euro.format(Number(pizza.price_teglia)) }}
                </p>

                <div v-if="editingId !== pizza.id" class="d-flex flex-wrap gap-2 mt-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm"
                        :disabled="savingId !== null"
                        @click="changeVisibility(pizza)"
                    >
                    <span v-if="savingId === pizza.id">Salvataggio…</span>
                    <span v-else>{{ pizza.is_visible ? 'Nascondi' : 'Mostra' }}</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm"
                        :disabled="savingId !== null || editingId !== null || creating"
                        @click="editPizza(pizza)"
                    >
                        Mododifica pizza
                    </button>
                </div>

            <PizzaForm
                v-else
                :pizza="pizza"
                :busy="savingId !== null"
                submit-label="Salva modifiche"
                @save="saveEdits(pizza, $event)"
                @cancel="cancelEditing"
            />

            </li>
        </ul>
    </section>
</template>
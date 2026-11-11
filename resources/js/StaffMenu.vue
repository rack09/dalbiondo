<script setup>
import { onMounted, ref } from 'vue';

import { readCsrfToken } from './csrf.js';

const pizzas = ref([]);
const loading = ref(true);
const error = ref('');

const savingId = ref(null);
const updateError = ref('');

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


async function changeVisibility(pizza) {
    if (savingId.value !== null) return;

    savingId.value = pizza.id;
    updateError.value = '';

    try {
        const token = readCsrfToken();

        if (!token) {
            updateError.value = 'Ricarica la pagina e riprova.';
            return;
        }

        const response = await fetch(`/api/staff/pizzas/${pizza.id}`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({ is_visible: !pizza.is_visible }),
        });

        if (response.status === 401 || response.status === 419) {
            updateError.value = 'Sessione scaduta. Ricarica la pagina.';
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        pizza.is_visible = data.is_visible;
    } catch {
        updateError.value = 'Impossibile modificare la visibilità. Riprova.';
    } finally {
        savingId.value = null;
    }
}

</script>

<template>
    <section class="mt-4">
        <h2 class="h4 mb-3">Gestione del menù</h2>

        <p v-if="updateError" class="alert alert-danger" role="alert">
            {{ updateError }}
        </p>

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

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm mt-2"
                    :disabled="savingId !== null"
                    @click="changeVisibility(pizza)"
                >
                    <span v-if="savingId === pizza.id">Salvataggio…</span>
                     <span v-else>{{ pizza.is_visible ? 'Nascondi' : 'Mostra' }}</span>
                </button>

            </li>
        </ul>
    </section>
</template>
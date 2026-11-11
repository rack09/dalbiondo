<script setup>
import { onMounted, ref } from 'vue';

const pizzas = ref([]);
const loading = ref(true);
const error = ref('');
const euro = new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
});

onMounted(async () => {
    try {
        const response = await fetch('/api/pizzas', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        pizzas.value = await response.json();
    } catch {
        error.value = 'Impossibile caricare il menù. Ricarica la pagina e riprova.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="container py-5">
        <h1 class="mb-4">Il menù di Dal Biondo</h1>

        <p v-if="loading" role="status">Caricamento del menù…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
        <p v-else-if="pizzas.length === 0">Il menù è in aggiornamento.</p>

        <ul v-else class="list-group">
            <li v-for="pizza in pizzas" :key="pizza.id" class="list-group-item py-3">
                <h2 class="h5">{{ pizza.name }}</h2>
                <p>{{ pizza.ingredients }}</p>
                <p class="mb-0">
                    Trancio {{ euro.format(Number(pizza.price_trancio)) }}
                    · Teglia {{ euro.format(Number(pizza.price_teglia)) }}
                </p>
            </li>
        </ul>
    </main>
</template>
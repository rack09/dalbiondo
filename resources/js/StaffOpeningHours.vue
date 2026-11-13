<script setup>
import { onMounted, ref } from 'vue';
import { sendJson } from './postJson.js';

const dayNames = [
    'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì',
    'Venerdì', 'Sabato', 'Domenica',
];

const week = ref([]);
const weekday = ref(null);
const draft = ref([]);
const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const success = ref('');

function selectDay(value) {
    const day = week.value.find(item => item.weekday === value);

    if (!day) return;

    weekday.value = day.weekday;
    draft.value = day.periods.map(period => ({ ...period }));
    saveError.value = '';
    success.value = '';
}

onMounted(async () => {
    try {
        const response = await fetch('/api/opening-hours', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        week.value = await response.json();

        if (week.value.length > 0) {
            selectDay(week.value[0].weekday);
        }
    } catch {
        loadError.value = 'Impossibile caricare gli orari. Ricarica la pagina.';
    } finally {
        loading.value = false;
    }
});

function addPeriod() {
    draft.value.push({ opens_at: '', closes_at: '' });
    success.value = '';
}

function removePeriod(index) {
    draft.value.splice(index, 1);
    success.value = '';
}

async function saveDay() {
    if (saving.value || weekday.value === null) return;

    saving.value = true;
    saveError.value = '';
    success.value = '';

    try {
        const saved = await sendJson(
            `/api/staff/opening-hours/${weekday.value}`,
            { periods: draft.value },
            'PUT',
        );

        week.value = week.value.map(day => day.weekday === saved.weekday ? saved : day);
        selectDay(saved.weekday);
        success.value = 'Orari salvati.';
    } catch (exception) {
        saveError.value = exception.message;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <section class="mt-4">
        <h2 class="h4 mb-3">Orari</h2>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="loadError" class="alert alert-danger" role="alert">{{ loadError }}</p>
        <p v-else-if="week.length === 0">Nessun orario configurato.</p>

        <form v-else class="card card-body" @submit.prevent="saveDay" @input="success = ''">
            <p v-if="saveError" class="alert alert-danger" role="alert">{{ saveError }}</p>
            <p v-if="success" class="alert alert-success" role="status">{{ success }}</p>

            <fieldset :disabled="saving">
                <label for="opening-weekday" class="form-label">Giorno</label>
                <select
                    id="opening-weekday"
                    class="form-select mb-3"
                    :value="weekday"
                    @change="selectDay(Number($event.target.value))"
                >
                    <option v-for="day in week" :key="day.weekday" :value="day.weekday">
                        {{ dayNames[day.weekday - 1] }}
                    </option>
                </select>

                <p v-if="draft.length === 0">Chiuso</p>

                <div v-for="(period, index) in draft" :key="index" class="border rounded p-2 mb-2">
                    <div class="row g-2">
                        <label class="col-6 form-label">
                            Apertura
                            <input v-model="period.opens_at" type="time" class="form-control" required>
                        </label>
                        <label class="col-6 form-label">
                            Chiusura
                            <input v-model="period.closes_at" type="time" class="form-control" required>
                        </label>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            @click="removePeriod(index)">
                        Rimuovi fascia
                    </button>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addPeriod">
                        Aggiungi fascia
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        {{ saving ? 'Salvataggio…' : 'Salva orari' }}
                    </button>
                </div>
            </fieldset>
        </form>
    </section>
</template>
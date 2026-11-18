<script setup>
import { computed, onMounted, ref } from 'vue';
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
const orderedWeek = computed(() => [...week.value].sort((first, second) => first.weekday - second.weekday));

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
            selectDay(orderedWeek.value[0].weekday);
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
    <section>
        <header class="staff-page-heading">
            <div>
                <h1>Orari di apertura</h1>
                <p>Imposta le fasce di apertura per ogni giorno.</p>
            </div>
        </header>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="loadError" class="alert alert-danger" role="alert">{{ loadError }}</p>
        <p v-else-if="week.length === 0">Nessun orario configurato.</p>

        <template v-else>
            <p v-if="saveError" class="alert alert-danger" role="alert">{{ saveError }}</p>
            <p v-if="success" class="alert alert-success" role="status">{{ success }}</p>
            <div class="staff-section-grid">
                <div class="staff-panel">
                    <h2>Settimana</h2>
                    <ul class="staff-week list-unstyled mb-0">
                        <li v-for="day in orderedWeek" :key="day.weekday" class="staff-day">
                            <strong>{{ dayNames[day.weekday - 1] }}</strong>
                            <div class="staff-day-hours">
                                <span v-if="!day.periods.length">Chiuso</span>
                                <span v-for="period in day.periods" :key="period.opens_at">
                                    {{ period.opens_at }}–{{ period.closes_at }}
                                </span>
                            </div>
                            <button
                                type="button" class="btn btn-outline-secondary btn-sm"
                                :aria-label="'Modifica ' + dayNames[day.weekday - 1]"
                                :aria-pressed="weekday === day.weekday"
                                :disabled="saving" @click="selectDay(day.weekday)"
                            >Modifica</button>
                        </li>
                    </ul>
                </div>

                <form class="staff-panel" @submit.prevent="saveDay" @input="success = ''">
                    <h2>{{ dayNames[weekday - 1] }}</h2>
                    <fieldset :disabled="saving">
                        <p v-if="draft.length === 0" class="text-body-secondary">Chiuso</p>

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

                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="removePeriod(index)">
                                Rimuovi fascia
                            </button>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="addPeriod">
                                Aggiungi fascia
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ saving ? 'Salvataggio…' : 'Salva orari' }}
                            </button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </template>
    </section>
</template>

<style scoped>
.staff-day {
    display: grid;
    grid-template-columns: minmax(4.7rem, auto) minmax(0, 1fr) auto;
    align-items: center;
    gap: .45rem;
    padding: .45rem 0;
    border-top: 1px solid var(--staff-line);
    font-size: .86rem;
}
.staff-day-hours {
    display: flex;
    flex-wrap: wrap;
    gap: .2rem .6rem;
    color: var(--staff-muted);
    font-size: .78rem;
    font-variant-numeric: tabular-nums;
}
.staff-day-hours span { white-space: nowrap; }
.staff-day [aria-pressed='true'] { color: var(--staff-gold); background: var(--staff-gold-tint); }
</style>

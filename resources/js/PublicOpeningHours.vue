<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const dayNames = [
    'lunedì', 'martedì', 'mercoledì', 'giovedì',
    'venerdì', 'sabato', 'domenica',
];

const week = ref([]);
const now = ref(new Date());
const loading = ref(true);
const error = ref('');
let timerId;

const romeClock = new Intl.DateTimeFormat('it-IT', {
    timeZone: 'Europe/Rome',
    weekday: 'long',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

const isOpen = computed(() => {
    const parts = romeClock.formatToParts(now.value);
    const { weekday, hour, minute } = Object.fromEntries(
        parts.map(({ type, value }) => [type, value]),
    );

    const dayNumber = dayNames.indexOf(weekday) + 1;
    const today = week.value.find(day => day.weekday === dayNumber);
    const time = `${hour}:${minute}`;

    return today?.periods.some(period =>
        period.opens_at <= time && time < period.closes_at,
    ) ?? false;
});

onMounted(async () => {
    timerId = window.setInterval(() => {
        now.value = new Date();
    }, 15_000);

    try {
        const response = await fetch('/api/opening-hours', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        week.value = await response.json();
        now.value = new Date();
    } catch {
        error.value = 'Impossibile caricare gli orari. Ricarica la pagina.';
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => window.clearInterval(timerId));
</script>

<template>
    <section class="mt-5" aria-labelledby="public-hours-title">
        <h2 id="public-hours-title" class="h4 mb-3">Orari di apertura</h2>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
        <p v-else-if="week.length === 0">Orari in aggiornamento.</p>

        <template v-else>
            <p>
                <span class="badge" :class="isOpen ? 'text-bg-success' : 'text-bg-secondary'">
                    {{ isOpen ? 'Aperto ora' : 'Chiuso ora' }}
                </span>
            </p>

            <dl class="list-group mb-0">
                <div v-for="day in week" :key="day.weekday" class="list-group-item">
                    <dt class="text-capitalize">{{ dayNames[day.weekday - 1] }}</dt>
                    <dd class="mb-0">
                        <template v-if="day.periods.length">
                            {{ day.periods.map(period =>
                                `${period.opens_at}–${period.closes_at}`,
                            ).join(' / ') }}
                        </template>
                        <template v-else>Chiuso</template>
                    </dd>
                </div>
            </dl>
        </template>
    </section>
</template>
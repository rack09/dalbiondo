<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const emit = defineEmits(['status-change']);

const dayNames = ['lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'];
const displayOrder = [1, 2, 3, 4, 5, 6, 7];
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

const groupedHours = computed(() => {
    const groups = [];
    let previousGroup = null;

    for (const weekday of displayOrder) {
        const day = week.value.find(day => day.weekday === weekday);

        if (!day) {
            previousGroup = null;
            continue;
        }

        const schedule = day.periods
            .map(({ opens_at, closes_at }) => `${opens_at}-${closes_at}`)
            .sort()
            .join(',');

        if (previousGroup?.schedule === schedule) {
            previousGroup.days.push(weekday);
            continue;
        }

        previousGroup = {
            days: [weekday],
            periods: day.periods,
            schedule,
        };
        groups.push(previousGroup);
    }

    return groups;
});

function formatDays(days) {
    if (days.length === dayNames.length) {
        return 'Tutti i giorni';
    }

    const firstDay = dayNames[days[0] - 1];

    if (days.length === 1) {
        return firstDay;
    }

    const lastDay = dayNames[days[days.length - 1] - 1];

    return `${firstDay} – ${lastDay}`;
}

const openingState = computed(() => {
    if (loading.value || error.value) {
        return 'unknown';
    }

    const parts = romeClock.formatToParts(now.value);
    const { weekday, hour, minute } = Object.fromEntries(
        parts.map(({ type, value }) => [type, value]),
    );

    const dayNumber = dayNames.indexOf(weekday) + 1;
    const today = week.value.find(day => day.weekday === dayNumber);

    if (!today) {
        return 'unknown';
    }

    const time = `${hour}:${minute}`;
    const isOpen = today.periods.some(period =>
        period.opens_at <= time && time < period.closes_at,
    );

    return isOpen ? 'open' : 'closed';
});

watch(openingState, state => {
    emit('status-change', state);
}, { immediate: true });

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
    <section class="hours-column" aria-labelledby="public-hours-title" tabindex="-1">
        <h3 id="public-hours-title">Orari di apertura</h3>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
        <p v-else-if="groupedHours.length === 0">Orari in aggiornamento.</p>

        <dl v-else class="hours-list">
            <div
                v-for="group in groupedHours"
                :key="group.days[0]"
                class="d-flex align-items-baseline justify-content-between gap-3 border-bottom"
            >
                <dt class="fw-normal">{{ formatDays(group.days) }}</dt>
                <dd class="mb-0 text-end">
                    <template v-if="group.periods.length">
                        <div v-for="period in group.periods" :key="period.opens_at">
                            <time :datetime="period.opens_at">{{ period.opens_at }}</time>
                            –
                            <time :datetime="period.closes_at">{{ period.closes_at }}</time>
                        </div>
                    </template>
                    <span v-else class="text-primary">Chiuso</span>
                </dd>
            </div>
        </dl>
    </section>
</template>

<style scoped>
.hours-list dt::first-letter {
    text-transform: uppercase;
}
</style>

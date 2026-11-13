<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const emit = defineEmits(["status-change"]);

const dayNames = [
    "lunedì",
    "martedì",
    "mercoledì",
    "giovedì",
    "venerdì",
    "sabato",
    "domenica",
];

const week = ref([]);
const now = ref(new Date());
const loading = ref(true);
const error = ref("");
let timerId;

const romeClock = new Intl.DateTimeFormat("it-IT", {
    timeZone: "Europe/Rome",
    weekday: "long",
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
});

const openingState = computed(() => {
    if (loading.value || error.value) {
        return "unknown";
    }

    const parts = romeClock.formatToParts(now.value);
    const { weekday, hour, minute } = Object.fromEntries(
        parts.map(({ type, value }) => [type, value]),
    );

    const dayNumber = dayNames.indexOf(weekday) + 1;
    const today = week.value.find((day) => day.weekday === dayNumber);

    if (!today) {
        return "unknown";
    }

    const time = `${hour}:${minute}`;
    const isOpen = today.periods.some(
        (period) => period.opens_at <= time && time < period.closes_at,
    );

    return isOpen ? "open" : "closed";
});

watch(
    openingState,
    (state) => {
        emit("status-change", state);
    },
    { immediate: true },
);

onMounted(async () => {
    timerId = window.setInterval(() => {
        now.value = new Date();
    }, 15_000);

    try {
        const response = await fetch("/api/opening-hours", {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        week.value = await response.json();
        now.value = new Date();
    } catch {
        error.value = "Impossibile caricare gli orari. Ricarica la pagina.";
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => window.clearInterval(timerId));
</script>

<template>
    <section class="hours-column" aria-labelledby="public-hours-title">
        <h3 id="public-hours-title">Orari di apertura</h3>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
        </p>
        <p v-else-if="week.length === 0">Orari in aggiornamento.</p>

        <dl v-else class="hours-list">
            <div
                v-for="day in week"
                :key="day.weekday"
                class="d-flex align-items-baseline justify-content-between gap-3 border-bottom"
            >
                <dt class="fw-normal text-capitalize">
                    {{ dayNames[day.weekday - 1] }}
                </dt>
                <dd class="mb-0 text-end">
                    <template v-if="day.periods.length">
                        <div
                            v-for="period in day.periods"
                            :key="period.opens_at"
                        >
                            <time :datetime="period.opens_at">{{
                                period.opens_at
                            }}</time>
                            –
                            <time :datetime="period.closes_at">{{
                                period.closes_at
                            }}</time>
                        </div>
                    </template>
                    <span v-else class="text-primary">Chiuso</span>
                </dd>
            </div>
        </dl>
    </section>
</template>

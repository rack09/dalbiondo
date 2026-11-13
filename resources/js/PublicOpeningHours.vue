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
    <section class="mt-5" aria-labelledby="public-hours-title">
        <h2 id="public-hours-title" class="h4 mb-3">Orari di apertura</h2>

        <p v-if="loading" role="status">Caricamento degli orari…</p>
        <p v-else-if="error" class="alert alert-danger" role="alert">
            {{ error }}
        </p>
        <p v-else-if="week.length === 0">Orari in aggiornamento.</p>

        <template v-else>
            <dl class="list-group mb-0">
                <div
                    v-for="day in week"
                    :key="day.weekday"
                    class="list-group-item"
                >
                    <dt class="text-capitalize">
                        {{ dayNames[day.weekday - 1] }}
                    </dt>
                    <dd class="mb-0">
                        <template v-if="day.periods.length">
                            {{
                                day.periods
                                    .map(
                                        (period) =>
                                            `${period.opens_at}–${period.closes_at}`,
                                    )
                                    .join(" / ")
                            }}
                        </template>
                        <template v-else>Chiuso</template>
                    </dd>
                </div>
            </dl>
        </template>
    </section>
</template>

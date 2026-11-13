<script setup>
import { computed, onMounted, ref, watch } from "vue";
import StaffMenu from "./StaffMenu.vue";

import StaffInvitations from "./StaffInvitations.vue";

import { readCsrfToken } from "./csrf.js";

import StaffOpeningHours from "./StaffOpeningHours.vue";

const user = ref(null);
const activeSection = ref("menu");
const isOwner = computed(() => user.value?.role === "owner");

const sections = [
    { id: "menu", label: "Menù" },
    { id: "orari", label: "Orari" },
    { id: "collaboratori", label: "Collaboratori", ownerOnly: true },
];

const visibleSections = computed(() =>
    sections.filter((section) => !section.ownerOnly || isOwner.value),
);

watch(user, () => {
    activeSection.value = "menu";
});

const email = ref("");
const password = ref("");
const checkingSession = ref(true);
const busy = ref(false);
const error = ref("");

const errorMessages = {
    419: "Sessione scaduta. Ricarica la pagina e riprova.",
    422: "Le credenziali non sono corrette.",
    429: "Troppi tentativi. Riprova tra un minuto.",
};

async function updateSession(method) {
    if (busy.value) return;

    busy.value = true;
    error.value = "";

    try {
        const headers = { Accept: "application/json" };
        const options = { method, headers, credentials: "same-origin" };

        if (method !== "GET") {
            const token = readCsrfToken();

            if (!token) {
                error.value = "Ricarica la pagina e riprova.";
                return;
            }

            headers["X-XSRF-TOKEN"] = token;
        }

        if (method === "POST") {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify({
                email: email.value,
                password: password.value,
            });
        }

        const response = await fetch("/api/session", options);

        if (response.status === 401 || response.status === 204) {
            user.value = null;
            return;
        }

        if (!response.ok) {
            error.value =
                errorMessages[response.status] ??
                "Impossibile completare la richiesta. Riprova.";
            return;
        }

        const data = await response.json();
        user.value = data.user;
    } catch {
        error.value = "Impossibile completare la richiesta. Riprova.";
    } finally {
        if (method === "POST") password.value = "";

        checkingSession.value = false;
        busy.value = false;
    }
}

onMounted(() => updateSession("GET"));
</script>

<template>
    <main class="min-vh-100 bg-body-tertiary py-4 py-md-5">
        <div class="container" :class="{ 'staff-login-width': !user }">
            <header
                class="staff-header d-flex align-items-center gap-3 rounded-3 p-3 px-sm-4 mb-4"
            >
                <img
                    src="/dal-biondo/slice.svg"
                    width="40"
                    height="44"
                    alt=""
                    class="flex-shrink-0"
                />
                <div>
                    <p class="small mb-1">Dal Biondo</p>
                    <h1 class="h4 mb-0">Area riservata</h1>
                </div>
            </header>

            <p v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
            </p>
            <p v-if="checkingSession" role="status">Verifica della sessione…</p>

            <section
                v-else-if="user"
                class="card card-body border-0 shadow-sm flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4"
            >
                <div>
                    <h2 class="h5 text-break mb-1">{{ user.name }}</h2>
                    <p class="small text-body-secondary mb-0">
                        {{ isOwner ? "Titolare" : "Collaboratore" }}
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-outline-secondary align-self-start align-self-sm-center flex-shrink-0"
                    :disabled="busy"
                    @click="updateSession('DELETE')"
                >
                    {{ busy ? "Disconnessione…" : "Esci" }}
                </button>
            </section>

            <form
                v-else
                class="card card-body border-0 shadow-sm p-3 p-sm-4"
                @submit.prevent="updateSession('POST')"
            >
                <fieldset :disabled="busy">
                    <div class="mb-3">
                        <label for="staff-email" class="form-label"
                            >Email</label
                        >
                        <input
                            id="staff-email"
                            v-model.trim="email"
                            type="email"
                            class="form-control"
                            autocomplete="username"
                            required
                        />
                    </div>

                    <div class="mb-3">
                        <label for="staff-password" class="form-label"
                            >Password</label
                        >
                        <input
                            id="staff-password"
                            v-model="password"
                            type="password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <button type="submit" class="btn btn-primary">
                        {{ busy ? "Accesso in corso…" : "Accedi" }}
                    </button>
                </fieldset>
            </form>

            <template v-if="user">
                <div
                    class="nav nav-pills gap-2 border-bottom pb-3 mb-4"
                    role="group"
                    aria-label="Sezioni dell’area riservata"
                >
                    <button
                        v-for="section in visibleSections"
                        :key="section.id"
                        class="nav-link"
                        type="button"
                        :class="{ active: activeSection === section.id }"
                        :aria-pressed="activeSection === section.id"
                        :aria-controls="`staff-${section.id}`"
                        @click="activeSection = section.id"
                    >
                        {{ section.label }}
                    </button>
                </div>

                <section
                    id="staff-menu"
                    v-show="activeSection === 'menu'"
                    aria-label="Menù"
                >
                    <StaffMenu :can-manage-featured="isOwner" />
                </section>

                <section
                    id="staff-orari"
                    v-show="activeSection === 'orari'"
                    aria-label="Orari"
                >
                    <StaffOpeningHours />
                </section>

                <section
                    v-if="isOwner"
                    v-show="activeSection === 'collaboratori'"
                    id="staff-collaboratori"
                    aria-label="Collaboratori"
                >
                    <StaffInvitations />
                </section>
            </template>
            <a href="/" class="d-inline-block mt-3">Torna al menù</a>
        </div>
    </main>
</template>

<style scoped>
.staff-login-width {
    max-width: 35rem;
}

.staff-header {
    background-color: #151513;
    color: #f5efe3;
}

.staff-header p {
    color: #fac564;
}
</style>

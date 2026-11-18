<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import StaffMenu from "./StaffMenu.vue";

import StaffInvitations from "./StaffInvitations.vue";

import { readCsrfToken } from "./csrf.js";

import StaffOpeningHours from "./StaffOpeningHours.vue";
import StaffPhotos from "./StaffPhotos.vue";
import StaffContactSettings from "./StaffContactSettings.vue";
import StaffProfile from "./StaffProfile.vue";

const user = ref(null);
const activeSection = ref("menu");
const isOwner = computed(() => user.value?.role === "owner");
const initials = computed(() => user.value?.name?.trim().split(/\s+/).slice(0, 2)
    .map(part => part[0]).join("").toUpperCase() || "DB");

const isMobile = ref(false);
const sidebarCompact = ref(false);
const drawerOpen = ref(false);
const sidebarToggle = ref(null);
const sidebarClose = ref(null);
const mobileSidebarOpen = computed(() => isMobile.value && drawerOpen.value);
const sidebarExpanded = computed(() => isMobile.value ? drawerOpen.value : !sidebarCompact.value);
const sidebarLabel = computed(() => {
    if (isMobile.value) return "Apri il menu laterale";
    return sidebarExpanded.value ? "Riduci il menu laterale" : "Espandi il menu laterale";
});
let viewport;

const sections = [
    { id: "menu", label: "Menù" },
    { id: "orari", label: "Orari" },
    { id: "fotos", label: "Foto dello staff" },
    { id: "contatti", label: "Contatti" },
    { id: "collaboratori", label: "Collaboratori", ownerOnly: true },
    { id: "profilo", label: "Profilo" },
];

const visibleSections = computed(() =>
    sections.filter((section) => !section.ownerOnly || isOwner.value),
);

watch(() => user.value?.id, () => {
    activeSection.value = "menu";
    drawerOpen.value = false;
});

function updateProfile(updated) {
    if (user.value?.id === updated.id) user.value = updated;
}

const currentSection = computed(() => visibleSections.value.find(section => section.id === activeSection.value));

function syncViewport(query) {
    isMobile.value = query.matches;
    drawerOpen.value = false;
}

function closeDrawer(restoreFocus = false) {
    if (!drawerOpen.value) return;
    drawerOpen.value = false;
    if (restoreFocus) void nextTick(() => sidebarToggle.value?.focus());
}

function toggleSidebar() {
    if (!isMobile.value) {
        sidebarCompact.value = !sidebarCompact.value;
        return;
    }
    drawerOpen.value = true;
    void nextTick(() => sidebarClose.value?.focus());
}

async function selectSection(id) {
    activeSection.value = id;
    closeDrawer(id === "orari");
    await nextTick();
    window.scrollTo(0, 0);
    if (id !== "orari") {
        document.getElementById(`staff-${id}`)?.focus({ preventScroll: true });
    }
}

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

onMounted(() => {
    viewport = window.matchMedia("(max-width: 900px)");
    syncViewport(viewport);
    viewport.addEventListener("change", syncViewport);
    void updateSession("GET");
});
onBeforeUnmount(() => viewport?.removeEventListener("change", syncViewport));
</script>

<template>
    <main v-if="!user" class="min-vh-100 bg-body-tertiary py-4 py-md-5">
        <div class="container staff-login-width">
            <header class="staff-login-header d-flex align-items-center gap-3 rounded-3 p-3 px-sm-4 mb-4">
                <img src="/dal-biondo/slice.svg" width="40" height="44" alt="" class="flex-shrink-0">
                <div>
                    <p class="small mb-1">Dal Biondo</p>
                    <h1 class="h4 mb-0">Area riservata</h1>
                </div>
            </header>

            <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
            <p v-if="checkingSession" role="status">Verifica della sessione…</p>

            <form v-else class="card card-body border-0 shadow-sm p-3 p-sm-4" @submit.prevent="updateSession('POST')">
                <fieldset :disabled="busy">
                    <div class="mb-3">
                        <label for="staff-email" class="form-label">Email</label>
                        <input
                            id="staff-email" v-model.trim="email" type="email"
                            class="form-control" autocomplete="username" required
                        >
                    </div>
                    <div class="mb-3">
                        <label for="staff-password" class="form-label">Password</label>
                        <input
                            id="staff-password" v-model="password" type="password"
                            class="form-control" autocomplete="current-password" required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        {{ busy ? 'Accesso in corso…' : 'Accedi' }}
                    </button>
                </fieldset>
            </form>
            <a href="/" class="d-inline-block mt-3">Torna al menù</a>
        </div>
    </main>

    <div
        v-else
        class="staff-dashboard"
        data-bs-theme="light"
        :class="{ 'staff-dashboard--compact': sidebarCompact, 'staff-dashboard--drawer-open': mobileSidebarOpen }"
        @keydown.esc="closeDrawer(true)"
    >
        <aside id="staff-sidebar" class="staff-sidebar" aria-label="Menu laterale">
            <div class="staff-brand">
                <span class="staff-brand-mark" aria-hidden="true">DB</span>
                <span class="staff-brand-name">Dal Biondo<small>Pizzeria</small></span>
                <button
                    ref="sidebarClose" type="button" class="staff-sidebar-close"
                    aria-label="Chiudi il menu laterale" @click="closeDrawer(true)"
                >×</button>
            </div>

            <nav class="staff-side-nav" aria-label="Sezioni dell’area riservata">
                <p class="staff-nav-label">Gestione</p>
                <button
                    v-for="section in visibleSections"
                    :key="section.id"
                    type="button"
                    class="staff-nav-item"
                    :aria-label="section.label"
                    :title="section.label"
                    :aria-current="activeSection === section.id ? 'page' : undefined"
                    :aria-controls="'staff-' + section.id"
                    @click="selectSection(section.id)"
                >
                    <span :class="['staff-nav-icon', 'staff-icon--' + section.id]" aria-hidden="true"></span>
                    <span class="staff-nav-text">{{ section.label }}</span>
                </button>
            </nav>
            <a class="staff-site-link" href="/" aria-label="Torna al sito" title="Torna al sito">
                <span aria-hidden="true">←</span><span class="staff-nav-text">Torna al sito</span>
            </a>
        </aside>
        <button
            v-if="mobileSidebarOpen" type="button" class="staff-veil"
            aria-label="Chiudi il menu laterale" tabindex="-1" @click="closeDrawer(true)"
        ></button>

        <div class="staff-shell" :inert="mobileSidebarOpen ? '' : null">
            <header class="staff-topbar">
                <div class="staff-topbar-start">
                    <button
                        ref="sidebarToggle"
                        type="button"
                        class="btn btn-outline-secondary staff-sidebar-toggle"
                        aria-controls="staff-sidebar"
                        :aria-expanded="sidebarExpanded"
                        :aria-label="sidebarLabel"
                        @click="toggleSidebar"
                    >
                        <span class="staff-nav-icon staff-icon--toggle" aria-hidden="true"></span>
                    </button>
                    <div class="staff-breadcrumb">
                        <span class="staff-breadcrumb-base">Area riservata</span>
                        <span aria-hidden="true">›</span>
                        <strong>{{ currentSection?.label }}</strong>
                    </div>
                </div>
                <div class="staff-profile">
                    <button
                        type="button"
                        class="staff-profile-trigger d-flex align-items-center gap-2 rounded-2 border-0 bg-transparent p-0 text-start"
                        aria-label="Apri il profilo" aria-controls="staff-profilo" @click="selectSection('profilo')"
                    >
                        <span class="staff-avatar" aria-hidden="true">{{ initials }}</span>
                        <span class="staff-profile-copy">
                            <strong>{{ user.name }}</strong>
                            <small>{{ isOwner ? 'Titolare' : 'Collaboratore' }}</small>
                        </span>
                    </button>
                    <button
                        type="button" class="btn btn-outline-secondary btn-sm staff-logout"
                        :disabled="busy" @click="updateSession('DELETE')"
                    >
                        {{ busy ? 'Disconnessione…' : 'Esci' }}
                    </button>
                </div>
            </header>

            <main class="staff-workspace">
                <p v-if="error" class="alert alert-danger" role="alert">{{ error }}</p>
                <section id="staff-menu" v-show="activeSection === 'menu'" aria-label="Menù" tabindex="-1">
                    <StaffMenu :can-manage-featured="isOwner" />
                </section>
                <section id="staff-orari" v-show="activeSection === 'orari'" aria-label="Orari">
                    <StaffOpeningHours />
                </section>
                <section id="staff-fotos" v-show="activeSection === 'fotos'" aria-label="Foto dello staff" tabindex="-1">
                    <StaffPhotos :can-manage-gallery="isOwner" />
                </section>
                <section id="staff-contatti" v-show="activeSection === 'contatti'" aria-label="Contatti" tabindex="-1">
                    <StaffContactSettings v-if="activeSection === 'contatti'" />
                </section>
                <section
                    v-if="isOwner"
                    v-show="activeSection === 'collaboratori'"
                    id="staff-collaboratori" aria-label="Collaboratori" tabindex="-1"
                >
                    <StaffInvitations />
                </section>
                <section id="staff-profilo" v-show="activeSection === 'profilo'" aria-label="Profilo" tabindex="-1">
                    <StaffProfile
                        v-if="activeSection === 'profilo'"
                        :user="user"
                        @profile-saved="updateProfile"
                    />
                </section>
            </main>
        </div>
    </div>
</template>

<style src="../css/staff-dashboard.css"></style>

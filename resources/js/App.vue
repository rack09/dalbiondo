<script setup>
import { onMounted, ref, computed } from "vue";

import PublicQuickInfo from "./PublicQuickInfo.vue";
import PublicMenu from "./PublicMenu.vue";
import PublicContact from "./PublicContact.vue";

import "bootstrap/js/dist/collapse";
import "../css/dal-biondo.css";

import "bootstrap/js/dist/modal";

import PublicStory from "./PublicStory.vue";
import PublicReviews from "./PublicReviews.vue";

const currentYear = new Date().getFullYear();

const openingState = ref("unknown");

const openingLabels = {
    unknown: "Consulta gli orari",
    open: "Aperto ora",
    closed: "Chiuso ora",
};

const pizzas = ref([]);
const menuItems = ref([]);
const contact = ref({});
const staffGalleryVisible = ref(false);

const previewPizzas = computed(() => {
    const house = pizzas.value.filter((pizza) => pizza.is_house_pizza);
    const featured = pizzas.value.filter(
        (pizza) => !pizza.is_house_pizza && pizza.is_featured,
    );
    const others = pizzas.value.filter(
        (pizza) => !pizza.is_house_pizza && !pizza.is_featured,
    );

    return [...house, ...featured, ...others].slice(0, 6);
});
const loading = ref(true);
const error = ref("");

async function fetchMenu(path) {
    const response = await fetch(path, { headers: { Accept: "application/json" } });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    if (!Array.isArray(data)) throw new Error("Risposta del menù non valida.");
    return data;
}

async function loadContact() {
    const response = await fetch("/api/contact", { headers: { Accept: "application/json" } });
    if (response.ok) contact.value = await response.json() ?? {};
}

async function loadStaffGallery() {
    const response = await fetch("/api/staff-gallery", { headers: { Accept: "application/json" } });
    if (response.ok) staffGalleryVisible.value = (await response.json())?.is_visible === true;
}

onMounted(async () => {
    void loadContact().catch(() => {});
    void loadStaffGallery().catch(() => {});

    const [slices, extras] = await Promise.allSettled([
        fetchMenu("/api/pizzas"),
        fetchMenu("/api/menu-items"),
    ]);

    if (slices.status === "fulfilled") pizzas.value = slices.value;
    if (extras.status === "fulfilled") menuItems.value = extras.value;

    if (slices.status === "rejected" && extras.status === "rejected") {
        error.value = "Impossibile caricare il menù. Ricarica la pagina e riprova.";
    } else if (slices.status === "rejected" || extras.status === "rejected") {
        error.value = "Una parte del menù non è disponibile. Ricarica la pagina e riprova.";
    }
    loading.value = false;
});
</script>

<template>
    <a class="skip-link" href="#contenuto">Salta al contenuto</a>

    <header id="inizio" class="site-header">
        <nav
            class="navbar navbar-expand-md navbar-dark container"
            aria-label="Navigazione principale"
        >
            <a
                class="navbar-brand brand d-flex align-items-center"
                href="#inizio"
            >
                <img
                    src="/dal-biondo/slice.svg"
                    width="40"
                    height="44"
                    alt=""
                />
                <span>Dal Biondo<small>PIZZA AL TRANCIO</small></span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navigazione"
                aria-controls="navigazione"
                aria-expanded="false"
                aria-label="Apri il menù di navigazione"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div id="navigazione" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-md-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#menu">Il menù</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#ordinare">Come ordinare</a>
                    </li>
                    <li v-if="staffGalleryVisible" class="nav-item">
                        <a class="nav-link" href="#storia">La nostra storia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#orari">Orari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contatti">Dove siamo</a>
                    </li>
                    <li v-if="contact.phone_href" class="nav-item ms-md-3">
                        <a class="nav-link phone-nav" :href="contact.phone_href">{{ contact.phone }}</a>
                    </li>
                    <li v-if="contact.whatsapp_url" class="nav-item">
                        <a class="nav-link" :href="contact.whatsapp_url">WhatsApp</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="contenuto">
        <section
            class="hero position-relative overflow-hidden"
            aria-labelledby="hero-title"
        >
            <div class="hero-photo position-absolute top-0 end-0 h-100">
                <img
                    src="/dal-biondo/trancio.webp"
                    width="1122"
                    height="1402"
                    fetchpriority="high"
                    class="w-100 h-100 object-fit-cover"
                    alt="Un trancio alto e dorato con pomodoro e mozzarella, su una pala da pizza."
                />
            </div>
            <div
                class="hero-shade position-absolute top-0 start-0 w-100 h-100"
                aria-hidden="true"
            ></div>

            <div class="container position-relative">
                <div class="row">
                    <div class="col-md-7 col-lg-6 hero-copy">
                        <p class="hero-script">Dal 1998, a Cinisello.</p>
                        <h1 id="hero-title">
                            La passione<br />si serve<br /><span
                                class="text-primary"
                                >al trancio.</span
                            >
                        </h1>
                        <p class="hero-description">
                            Pizza al trancio, teglie da condividere e sfizi da
                            asporto. Ti aspettiamo da Dal Biondo.
                        </p>

                        <a
                            class="opening-status d-inline-flex align-items-center text-decoration-none"
                            href="#orari"
                            :data-state="openingState"
                        >
                            <span
                                class="opening-dot flex-shrink-0 rounded-circle"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="opening-copy"
                                aria-live="polite"
                                aria-atomic="true"
                            >
                                <strong>{{
                                    openingLabels[openingState]
                                }}</strong>
                            </span>
                            <svg
                                class="opening-chevron flex-shrink-0"
                                aria-hidden="true"
                                viewBox="0 0 16 16"
                            >
                                <path d="m4 6 4 4 4-4" />
                            </svg>
                        </a>
                        <div class="hero-actions d-flex flex-wrap gap-3">
                            <a class="btn btn-primary" href="#menu"
                                >Scopri il menù</a
                            >
                            <a
                                class="btn btn-outline-light"
                                href="https://www.justeat.it/restaurants-pizzeriaaltranciodalbiondo/menu"
                            >
                                Ordina su Just Eat
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <span class="photo-note position-absolute"
                >Immagine illustrativa</span
            >
        </section>

        <PublicQuickInfo :contact="contact" />

        <PublicMenu
            :pizzas="pizzas"
            :preview-pizzas="previewPizzas"
            :menu-items="menuItems"
            :contact="contact"
            :loading="loading"
            :error="error"
        />

      <section
            id="ordinare"
            class="order-section"
            aria-labelledby="order-title"
        >
            <div
                class="container order-strip d-flex flex-column flex-lg-row align-items-center justify-content-between gap-3 gap-lg-4 text-center text-lg-start"
            >
                <h2 id="order-title" class="mb-0">Hai scelto la tua pizza?</h2>

                <div
                    class="order-actions d-flex flex-column flex-sm-row flex-wrap justify-content-center justify-content-lg-end gap-2 gap-sm-3"
                >
                    <a v-if="contact.phone_href" class="btn btn-primary" :href="contact.phone_href">
                        Chiama per ordinare
                        <span class="arrow" aria-hidden="true">
                            <svg viewBox="0 0 16 16">
                                <path d="M3 13 13 3M4 3h9v9" />
                            </svg>
                        </span>
                    </a>

                    <a
                        v-if="contact.whatsapp_url"
                        class="btn"
                        :class="contact.phone_href ? 'btn-outline-primary' : 'btn-primary'"
                        :href="contact.whatsapp_url"
                    >
                        Scrivici su WhatsApp
                    </a>

                    <a
                        class="btn"
                        :class="contact.phone_href || contact.whatsapp_url ? 'btn-outline-primary' : 'btn-primary'"
                        href="https://www.justeat.it/restaurants-pizzeriaaltranciodalbiondo/menu"
                    >
                        Ordina su Just Eat
                        <span class="arrow" aria-hidden="true">
                            <svg viewBox="0 0 16 16">
                                <path d="M3 13 13 3M4 3h9v9" />
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <PublicStory v-if="staffGalleryVisible" />

  

        <PublicReviews />

        <PublicContact :contact="contact" @status-change="openingState = $event" />

        
    </main>

    <footer class="site-footer">
        <div
            class="container d-flex flex-wrap align-items-center justify-content-between gap-3"
        >
            <div>
                <a class="footer-brand text-decoration-none" href="#inizio">
                    Dal Biondo <span>· Pizza al trancio dal 1998</span>
                </a>
                <small class="d-block mt-1"
                    >© 2007–{{ currentYear }} Dal Biondo</small
                >
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <a class="text-link" href="/area-riservata"> Area riservata </a>

                <span class="small text-body-secondary">
                    Sviluppato da
                    <a class="text-link" href="https://melvinrosales.com/"
                        >Melvin Rosales</a
                    >
                </span>
            </div>
        </div>
    </footer>
</template>

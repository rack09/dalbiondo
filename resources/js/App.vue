<script setup>
import { onMounted, ref, computed } from "vue";

import PublicOpeningHours from "./PublicOpeningHours.vue";

import "bootstrap/js/dist/collapse";
import "../css/dal-biondo.css";

import "bootstrap/js/dist/modal";
import PublicPizzaList from "./PublicPizzaList.vue";

const openingState = ref("unknown");

const openingLabels = {
    unknown: "Consulta gli orari",
    open: "Aperto ora",
    closed: "Chiuso ora",
};

const pizzas = ref([]);

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

onMounted(async () => {
    try {
        const response = await fetch("/api/pizzas", {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        pizzas.value = await response.json();
    } catch {
        error.value =
            "Impossibile caricare il menù. Ricarica la pagina e riprova.";
    } finally {
        loading.value = false;
    }
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
                        <a class="nav-link" href="#orari">Orari</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contatti">Dove siamo</a>
                    </li>
                    <li class="nav-item ms-md-3">
                        <a class="nav-link phone-nav" href="tel:+390266040662"
                            >02 6604 0662</a
                        >
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

        <section
            id="menu"
            class="menu-section section-pad"
            aria-labelledby="menu-title"
        >
            <div class="container">
                <div class="section-heading text-center mx-auto mb-4">
                    <h2 id="menu-title">Il menù di Dal Biondo</h2>
                    <div
                        class="ornament d-flex align-items-center justify-content-center text-primary"
                        aria-hidden="true"
                    >
                        <span class="d-block"></span>◆<span
                            class="d-block"
                        ></span>
                    </div>
                    <p>Le nostre pizze al trancio e in teglia.</p>
                </div>

                <div class="menu-content mx-auto">
                    <p v-if="loading" class="text-center" role="status">
                        Caricamento del menù…
                    </p>
                    <p
                        v-else-if="error"
                        class="alert alert-danger"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                    <p v-else-if="pizzas.length === 0" class="text-center">
                        Il menù è in aggiornamento.
                    </p>

                    <template v-else>
                        <PublicPizzaList :pizzas="previewPizzas" />

                        <div class="menu-complete-action text-center">
                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#menu-completo"
                                aria-haspopup="dialog"
                                aria-controls="menu-completo"
                            >
                                Vedi la carta completa
                            </button>
                        </div>

                        <Teleport to="body">
                            <div
                                id="menu-completo"
                                class="modal fade menu-modal"
                                tabindex="-1"
                                aria-labelledby="menu-completo-title"
                                aria-hidden="true"
                            >
                                <div
                                    class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down"
                                >
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2
                                                id="menu-completo-title"
                                                class="modal-title"
                                            >
                                                La carta completa
                                            </h2>
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary menu-modal-close"
                                                data-bs-dismiss="modal"
                                            >
                                                Chiudi
                                            </button>
                                        </div>

                                        <div class="modal-body">
                                            <PublicPizzaList :pizzas="pizzas" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Teleport>
                    </template>
                </div>
            </div>
        </section>

        <section
            id="contatti"
            class="contact-section section-pad"
            aria-labelledby="contact-title"
        >
            <div class="container">
                <div class="row gy-5 gx-lg-5">
                    <div class="col-lg-5">
                        <h2 id="contact-title">Dove siamo</h2>

                        <address>
                            Via Sant’Antonio 13<br />
                            20092 Cinisello Balsamo (MI)
                        </address>

                        <a
                            class="text-link"
                            href="https://www.google.com/maps/place/Pizza+Al+Trancio+Dal+Biondo/@45.55222,9.2225842,17z/data=!3m1!4b1!4m6!3m5!1s0x4786b8c867cb1fab:0xa2150dd7150b54c1!8m2!3d45.55222!4d9.2225842!16s%2Fg%2F11ddwxwltz?entry=ttu"
                        >
                            Indicazioni su Google Maps
                        </a>
                    </div>

                    <div class="col-lg-6 offset-lg-1">
                        <PublicOpeningHours
                            id="orari"
                            @status-change="openingState = $event"
                        />

                        <a
                            class="contact-phone d-inline-block text-decoration-none"
                            href="tel:+390266040662"
                        >
                            02 6604 0662
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, ref } from 'vue';
import 'bootstrap/js/dist/modal';
import PublicPizzaList from './PublicPizzaList.vue';

const props = defineProps({
    pizzas: { type: Array, required: true },
    previewPizzas: { type: Array, required: true },
    menuItems: { type: Array, required: true },
    contact: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const euro = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });
const justEatUrl = 'https://www.justeat.it/restaurants-pizzeriaaltranciodalbiondo/menu';
const tabList = ref(null);
const selectedTab = ref(null);

const contactHref = computed(() => props.contact.phone_href ?? props.contact.whatsapp_url ?? '#contatti');
const contactLabel = computed(() => {
    if (props.contact.phone_href) return 'chiamaci';
    if (props.contact.whatsapp_url) return 'scrivici su WhatsApp';
    return 'chiedi in pizzeria';
});

const categories = computed(() => {
    const roundPizzas = props.menuItems.filter(item => item.category === 'rotonde');
    const snacks = props.menuItems.filter(item => item.category === 'sfizi');
    const drinks = props.menuItems.filter(item => item.category === 'bibite');
    const tabs = [];

    if (props.pizzas.length) tabs.push({ id: 'tranci', label: 'Tranci e teglie' });
    if (roundPizzas.length) {
        tabs.push({
            id: 'rotonde',
            label: 'Pizze rotonde',
            groups: [{ id: 'rotonde', label: 'Pizze rotonde', items: roundPizzas }],
        });
    }

    const extras = [
        { id: 'sfizi', label: 'Sfizi', items: snacks },
        { id: 'bibite', label: 'Bibite e birre', items: drinks },
    ].filter(group => group.items.length);

    if (extras.length) {
        tabs.push({
            id: 'sfizi',
            label: extras.length === 2 ? 'Sfizi e bibite' : extras[0].label,
            groups: extras,
        });
    }
    return tabs;
});

const activeTab = computed(() => categories.value.find(category => category.id === selectedTab.value)?.id
    ?? categories.value[0]?.id,
);

function moveTab(event, index) {
    const count = categories.value.length;
    let target = index;

    switch (event.key) {
    case 'ArrowRight': target = (index + 1) % count; break;
    case 'ArrowLeft': target = (index + count - 1) % count; break;
    case 'Home': target = 0; break;
    case 'End': target = count - 1; break;
    default: return;
    }

    event.preventDefault();
    selectedTab.value = categories.value[target].id;
    tabList.value?.querySelectorAll('[role="tab"]')[target]?.focus();
}
</script>

<template>
    <section id="menu" class="menu-section section-pad" aria-labelledby="menu-title">
        <div class="container">
            <div class="section-heading text-center mx-auto">
                <p class="eyebrow text-primary">SCEGLI IL TUO GUSTO</p>
                <h2 id="menu-title">Il menù di Dal Biondo</h2>
                <div class="ornament d-flex align-items-center justify-content-center text-primary" aria-hidden="true">
                    <span class="d-block"></span>◆<span class="d-block"></span>
                </div>
                <p>I classici di sempre, le nostre specialità e qualcosa da stuzzicare.</p>
            </div>

            <div v-if="!loading && categories.length" class="menu-navigation">
                <ul
                    ref="tabList"
                    class="nav nav-pills menu-tabs justify-content-center gap-1 gap-sm-2 m-0"
                    role="tablist"
                    aria-label="Categorie del menù"
                >
                    <li v-for="(category, index) in categories" :key="category.id" class="nav-item" role="presentation">
                        <button
                            :id="category.id + '-tab'"
                            type="button"
                            class="nav-link"
                            :class="{ active: category.id === activeTab }"
                            role="tab"
                            :aria-controls="category.id"
                            :aria-selected="category.id === activeTab"
                            :tabindex="category.id === activeTab ? 0 : -1"
                            @click="selectedTab = category.id"
                            @keydown="moveTab($event, index)"
                        >
                            {{ category.label }}
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content menu-content mx-auto">
                <p v-if="loading" class="text-center" role="status">Caricamento del menù…</p>
                <template v-else>
                    <p v-if="error" class="alert alert-warning" role="alert">{{ error }}</p>
                    <p v-else-if="categories.length === 0" class="text-center">Il menù è in aggiornamento.</p>

                    <div
                        v-for="category in categories"
                        :id="category.id"
                        :key="category.id"
                        class="tab-pane"
                        :class="{ active: category.id === activeTab }"
                        role="tabpanel"
                        :aria-labelledby="category.id + '-tab'"
                        :hidden="category.id !== activeTab"
                        tabindex="0"
                    >
                        <template v-if="category.id === 'tranci'">
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
                                    {{ pizzas.length === 1 ? 'Vedi la pizza in carta' : 'Vedi tutte le ' + pizzas.length + ' pizze' }}
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
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 id="menu-completo-title" class="modal-title">La carta completa</h2>
                                                <button type="button" class="btn btn-outline-primary menu-modal-close" data-bs-dismiss="modal">
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

                        <template v-else>
                            <template v-for="group in category.groups" :key="group.id">
                                <h3
                                    v-if="category.groups.length > 1"
                                    :id="'menu-group-' + group.id"
                                    class="menu-group-title"
                                >{{ group.label }}</h3>
                                <ul
                                    class="priced-menu-list list-unstyled row row-cols-1 row-cols-md-2 gx-md-5 gy-0 mb-0"
                                    :aria-labelledby="category.groups.length > 1 ? 'menu-group-' + group.id : undefined"
                                >
                                    <li v-for="item in group.items" :key="item.id" class="col">
                                        <article class="dish drink-dish border-bottom">
                                            <div class="dish-heading d-flex align-items-center justify-content-between gap-3">
                                                <component
                                                    :is="category.groups.length > 1 ? 'h4' : 'h3'"
                                                    class="mb-0"
                                                >{{ item.name }}</component>
                                                <strong v-if="item.price != null" class="dish-price text-primary text-nowrap flex-shrink-0 mb-0">
                                                    {{ euro.format(Number(item.price)) }}
                                                </strong>
                                            </div>
                                            <p v-if="item.description" class="text-body-secondary mb-0">{{ item.description }}</p>
                                        </article>
                                    </li>
                                </ul>
                            </template>
                            <p v-if="category.id === 'rotonde'" class="availability-note">
                                Per conoscere la disponibilità delle pizze rotonde,
                                <a :href="contactHref">{{ contactLabel }}</a>.
                            </p>
                        </template>
                    </div>
                </template>
            </div>

            <div class="menu-footnote text-center">
                <p class="text-body-secondary">
                    Prezzi riferiti al menù online. Per il listino al banco, disponibilità e allergeni,
                    <a :href="contactHref">{{ contactLabel }}</a>.
                </p>
                <a class="text-link" :href="justEatUrl">
                    Tutto il menù e i prezzi su Just Eat
                    <span class="arrow" aria-hidden="true">
                        <svg viewBox="0 0 16 16"><path d="M3 13 13 3M4 3h9v9" /></svg>
                    </span>
                </a>
            </div>
        </div>
    </section>
</template>

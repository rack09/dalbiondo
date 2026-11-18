<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';

import { readCsrfToken } from './csrf.js';
import PizzaForm from './PizzaForm.vue';

const props = defineProps({
    canManageFeatured: { type: Boolean, default: false },
});

const pageSize = 6;
const featureLimit = 6;
const euro = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });
const alphabetical = new Intl.Collator('it', { numeric: true, sensitivity: 'base' });
const sessionError = 'Sessione scaduta. Ricarica la pagina e accedi di nuovo.';
const priceError = 'Inserisci un prezzo tra 0 e 9999,99 € con al massimo due decimali.';
const fieldErrors = {
    name: 'Inserisci un nome di massimo 255 caratteri.',
    ingredients: 'Inserisci gli ingredienti o accorcia il testo.',
    description: 'Usa al massimo 1000 caratteri per la descrizione.',
    price: priceError,
    price_trancio: priceError,
    price_teglia: priceError,
};
const filters = [
    { id: 'all', label: 'Tutte', matches: () => true },
    { id: 'visible', label: 'Visibili', matches: pizza => pizza.is_visible },
    { id: 'hidden', label: 'Nascoste', matches: pizza => !pizza.is_visible },
    { id: 'featured', label: 'In evidenza', matches: pizza => pizza.is_featured },
];

const categories = [
    { id: 'tranci', label: 'Tranci e teglie', newLabel: 'Nuova pizza', singular: 'pizza', plural: 'pizze', description: 'Ingredienti' },
    { id: 'rotonde', label: 'Pizze rotonde', newLabel: 'Nuova pizza rotonda', singular: 'pizza', plural: 'pizze', description: 'Ingredienti' },
    { id: 'bibite', label: 'Bibite e birre', newLabel: 'Nuova bibita', singular: 'bibita', plural: 'bibite', description: 'Formato' },
    { id: 'sfizi', label: 'Sfizi', newLabel: 'Nuovo sfizio', singular: 'sfizio', plural: 'sfizi', description: 'Descrizione' },
];

const catalog = ref(null);
const products = ref([]);
const category = ref(categories[0]);
const loading = ref(true);
const saving = ref(false);
const loadError = ref('');
const actionError = ref('');
const notice = ref('');
const search = ref('');
const filter = ref(filters[0]);
const page = ref(1);
const editor = ref(null);

const isTranci = computed(() => category.value.id === 'tranci');
const canManageHomepage = computed(() => isTranci.value && props.canManageFeatured);
const endpoint = computed(() => isTranci.value ? '/api/staff/pizzas' : '/api/staff/menu-items');
const categoryFilters = computed(() => isTranci.value ? filters : filters.slice(0, 3));
const searchLabel = computed(() => {
    const criteria = { bibite: 'formato', sfizi: 'descrizione' };
    return `Cerca per nome o ${criteria[category.value.id] ?? 'ingrediente'}`;
});

function priority(pizza) {
    if (!pizza.is_visible) return 3;
    if (pizza.is_house_pizza) return 0;
    return pizza.is_featured ? 1 : 2;
}

const matches = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('it');

    return products.value
        .filter(pizza =>
            filter.value.matches(pizza)
            && `${pizza.name} ${pizza.ingredients ?? pizza.description ?? ''}`.toLocaleLowerCase('it').includes(term),
        )
        .sort((left, right) =>
            priority(left) - priority(right) || alphabetical.compare(left.name, right.name),
        );
});

const pageCount = computed(() => Math.max(1, Math.ceil(matches.value.length / pageSize)));
const pageStart = computed(() => (page.value - 1) * pageSize);
const pagePizzas = computed(() => matches.value.slice(pageStart.value, pageStart.value + pageSize));
const featuredPizzas = computed(() => products.value.filter(pizza => pizza.is_featured));
const hasVisibleProducts = computed(() => products.value.some(pizza => pizza.is_visible));
const filterLabels = computed(() => {
    const snackLabels = { all: 'Tutti', hidden: 'Nascosti' };
    return Object.fromEntries(categoryFilters.value.map(option => {
        const label = category.value.id === 'sfizi' ? snackLabels[option.id] ?? option.label : option.label;
        const text = option.id === 'all' ? label : `${label} (${products.value.filter(option.matches).length})`;
        return [option.id, text];
    }));
});
const resultsLabel = computed(() => {
    const total = matches.value.length;
    if (pageCount.value > 1) {
        return `${pageStart.value + 1}–${pageStart.value + pagePizzas.value.length} di ${total} ${category.value.plural}`;
    }
    return `${total} ${total === 1 ? category.value.singular : category.value.plural}`;
});
const cards = computed(() => {
    if (!editor.value) return pagePizzas.value;
    return editor.value.original ? editor.value.cards : [null, ...editor.value.cards];
});
const controlsDisabled = computed(() => saving.value || editor.value !== null);
const featuredFull = computed(() => {
    const currentId = editor.value?.saved?.id;
    return featuredPizzas.value.filter(pizza => pizza.id !== currentId).length >= featureLimit;
});

async function focusControl(selector) {
    await nextTick();
    const target = catalog.value?.querySelector(selector)
        ?? catalog.value?.querySelector('[data-results]');
    if (!target) return;

    target.focus({ preventScroll: true });
    target.scrollIntoView({ block: 'nearest' });
}

function selectFilter(selection) {
    if (selection.id !== filter.value.id) {
        search.value = '';
    }

    filter.value = selection;
    page.value = 1;
    notice.value = '';
}

async function selectCategory(selection) {
    if (selection.id === category.value.id || controlsDisabled.value || loading.value) return;

    category.value = selection;
    products.value = [];
    search.value = '';
    actionError.value = '';
    selectFilter(filters[0]);
    await loadCatalog();
}

function showPage(number) {
    page.value = number;
    void focusControl('[data-results]');
}

function focusPizza(pizza, action = '.staff-edit-button') {
    const index = matches.value.findIndex(item => item.id === pizza.id);
    page.value = index < 0
        ? Math.min(page.value, pageCount.value)
        : Math.floor(index / pageSize) + 1;

    return focusControl(`[data-pizza-id="${pizza.id}"] ${action}`);
}

function errorMessage(status, body, featured) {
    if (status === 401 || status === 419) return sessionError;
    if (status === 403) return 'Non hai il permesso di eseguire questa operazione.';
    if (status === 404) return 'Il prodotto non è più disponibile.';
    if (status === 422) {
        const field = Object.keys(body?.errors ?? {})[0];
        if (fieldErrors[field]) return fieldErrors[field];
        if (featured && typeof body?.errors?.[field]?.[0] === 'string') {
            return body.errors[field][0];
        }
        return 'Controlla i dati inseriti.';
    }
    return 'Impossibile completare la richiesta. Riprova.';
}

async function request(path, method = 'GET', values) {
    const headers = { Accept: 'application/json' };
    const options = { method, headers, credentials: 'same-origin' };

    if (method !== 'GET') {
        const token = readCsrfToken();
        if (!token) return { error: sessionError };

        headers['X-XSRF-TOKEN'] = token;
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(values);
    }

    try {
        const response = await fetch(path, options);
        if (response.ok) return { data: await response.json() };

        const body = await response.json().catch(() => null);
        return {
            error: errorMessage(response.status, body, path.endsWith('/featured')),
            uncertain: response.status >= 500,
        };
    } catch {
        return { error: 'La richiesta non è stata confermata. Controlla la connessione.', uncertain: true };
    }
}

async function refreshCatalog() {
    const path = isTranci.value ? endpoint.value : `${endpoint.value}?category=${category.value.id}`;
    const response = await request(path);
    if (!Array.isArray(response.data)) {
        return response.error ?? 'Impossibile caricare i prodotti. Riprova.';
    }

    products.value = response.data;
    return '';
}

async function loadCatalog() {
    loading.value = true;
    loadError.value = '';
    try {
        loadError.value = await refreshCatalog();
    } finally {
        loading.value = false;
    }
}

function applySavedPizza(changes) {
    const existing = products.value.find(item => item.id === changes.id);
    const pizza = { ...existing, ...changes };
    if (isTranci.value && !pizza.is_visible) {
        pizza.is_featured = false;
        pizza.is_house_pizza = false;
    }

    products.value = products.value.map(item => {
        if (item.id === pizza.id) return pizza;
        if (pizza.is_house_pizza && item.is_house_pizza) {
            return { ...item, is_house_pizza: false };
        }
        return item;
    });
    if (!existing) products.value.push(pizza);
    return pizza;
}

function openEditor(pizza = null) {
    if (controlsDisabled.value) return;

    actionError.value = '';
    notice.value = '';
    editor.value = {
        original: pizza,
        saved: pizza,
        cards: pagePizzas.value,
        visible: pizza?.is_visible ?? true,
        featured: Boolean(pizza?.is_featured),
        house: Boolean(pizza?.is_house_pizza),
        detailsSaved: false,
        reviewCreation: false,
        error: '',
    };
    void focusControl('[data-editor-title]');
}

function resetHomepageWhenHidden() {
    if (!editor.value.visible) {
        editor.value.featured = false;
        editor.value.house = false;
    }
}

function closeEditor() {
    if (saving.value || !editor.value) return;

    const draft = editor.value;
    editor.value = null;
    notice.value = draft.detailsSaved ? 'I dati del prodotto sono stati salvati.' : '';

    if (draft.saved) {
        void focusPizza(draft.saved);
    } else {
        page.value = Math.min(page.value, pageCount.value);
        void focusControl('[data-add-pizza]');
    }
}

function changedDetails(values, draft) {
    const details = { ...values, is_visible: draft.visible };
    if (!draft.saved) {
        return isTranci.value ? details : { ...details, category: category.value.id };
    }

    return Object.fromEntries(Object.entries(details).filter(([field, value]) => {
        const previous = draft.saved[field];
        if (field.startsWith('price') && value !== null && previous !== null) {
            return Number(value) !== Number(previous);
        }
        return value !== previous;
    }));
}

async function saveEditor(values) {
    const draft = editor.value;
    if (saving.value || !draft || draft.reviewCreation) return;

    draft.error = '';
    saving.value = true;
    const changes = changedDetails(values, draft);
    const homepageChanged = canManageHomepage.value && draft.visible && (
        draft.featured !== Boolean(draft.original?.is_featured)
        || draft.house !== Boolean(draft.original?.is_house_pizza)
    );

    try {
        if (Object.keys(changes).length) {
            const creating = draft.saved === null;
            const url = creating ? endpoint.value : `${endpoint.value}/${draft.saved.id}`;
            const result = await request(url, creating ? 'POST' : 'PATCH', changes);

            if (!result.data?.id) {
                if (result.uncertain || result.data) {
                    await refreshCatalog();
                    if (creating) draft.reviewCreation = true;
                }
                draft.error = draft.reviewCreation
                    ? 'Creazione non confermata. Chiudi la scheda e controlla se il prodotto è già nel menù.'
                    : result.error ?? 'Impossibile confermare il salvataggio. Riprova.';
                return;
            }

            draft.saved = applySavedPizza(result.data);
            draft.detailsSaved = true;
        }

        const saved = products.value.find(pizza => pizza.id === draft.saved.id) ?? draft.saved;
        if (homepageChanged && (
            Boolean(saved.is_featured) !== draft.featured
            || Boolean(saved.is_house_pizza) !== draft.house
        )) {
            const result = await request(`${endpoint.value}/${saved.id}/featured`, 'PATCH', {
                is_featured: draft.featured,
                is_house_pizza: draft.house,
            });

            if (!result.data) {
                await refreshCatalog();
                const detailsStatus = draft.detailsSaved ? 'I dati della pizza sono stati salvati. ' : '';
                draft.error = `${detailsStatus}Salvataggio delle opzioni della homepage non confermato. ${result.error}`;
                return;
            }

            applySavedPizza({ id: saved.id, ...result.data });
        }

        if (!draft.original) {
            search.value = '';
            selectFilter(filters[0]);
        }
        editor.value = null;
        notice.value = `${draft.saved.name} · modifiche salvate.`;
    } finally {
        saving.value = false;
    }
    await focusPizza(draft.saved);
}

function menuVisibilityLabel(pizza) {
    return pizza.is_visible ? 'Nascondi dal menù' : 'Mostra nel menù';
}

async function changeVisibility(pizza) {
    if (controlsDisabled.value) return;

    actionError.value = '';
    notice.value = '';
    saving.value = true;

    try {
        const result = await request(`${endpoint.value}/${pizza.id}`, 'PATCH', {
            is_visible: !pizza.is_visible,
        });
        if (!result.data?.id) {
            if (result.uncertain || result.data) await refreshCatalog();
            actionError.value = result.error ?? 'Impossibile confermare la modifica.';
        } else {
            applySavedPizza(result.data);
            notice.value = `${result.data.name} · visibilità aggiornata.`;
        }
    } finally {
        saving.value = false;
        await focusPizza(pizza, '.staff-visibility-button');
    }
}

onMounted(loadCatalog);
</script>

<template>
    <section
        ref="catalog"
        class="staff-catalog"
        aria-labelledby="staff-catalog-title"
    >
        <header class="staff-page-heading">
            <div>
                <h1 id="staff-catalog-title">Gestione menù</h1>
                <p>Prodotti, prezzi e visibilità sul sito.</p>
            </div>
            <button
                v-if="!loading && !loadError"
                class="btn btn-primary"
                type="button"
                data-add-pizza
                :disabled="controlsDisabled"
                @click="openEditor()"
            >
                <span aria-hidden="true">+</span> {{ category.newLabel }}
            </button>
        </header>

        <div class="staff-categories" role="group" aria-label="Categorie del catalogo">
            <button
                v-for="option in categories"
                :key="option.id"
                type="button"
                class="btn staff-category"
                :aria-pressed="category.id === option.id"
                :disabled="controlsDisabled || loading"
                @click="selectCategory(option)"
            >
                {{ option.label }}
            </button>
        </div>

        <p v-if="loading" class="staff-muted mb-0" role="status">Caricamento del catalogo…</p>
        <div v-else-if="loadError" class="alert alert-danger mb-0" role="alert">
            <p class="mb-2">{{ loadError }}</p>
            <button type="button" class="btn btn-outline-primary" @click="loadCatalog">Riprova</button>
        </div>

        <template v-else>
            <div class="staff-toolbar d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <label class="staff-search">
                    <span class="visually-hidden">{{ searchLabel }}</span>
                    <input
                        v-model="search"
                        type="search"
                        class="form-control"
                        :placeholder="searchLabel"
                        autocomplete="off"
                        :disabled="controlsDisabled"
                        @input="page = 1"
                    >
                </label>
                <div class="staff-filters" role="group" aria-label="Filtra i prodotti">
                    <button
                        v-for="option in categoryFilters"
                        :key="option.id"
                        class="btn staff-filter"
                        type="button"
                        :aria-pressed="filter.id === option.id"
                        :disabled="controlsDisabled"
                        @click="selectFilter(option)"
                    >
                        {{ filterLabels[option.id] }}
                    </button>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between gap-2 py-3 staff-meta">
                <p class="staff-muted mb-0" data-results tabindex="-1" role="status">{{ resultsLabel }}</p>
                <p v-if="isTranci" class="staff-gold mb-0">
                    <span aria-hidden="true">★</span> {{ featuredPizzas.length }}/{{ featureLimit }} in homepage
                </p>
            </div>
            <p v-if="!hasVisibleProducts" class="staff-muted mb-3" role="status">
                Nessun prodotto di questa categoria è visibile sul sito.
            </p>
            <p v-if="actionError" class="alert alert-danger py-2" role="alert">{{ actionError }}</p>
            <p v-if="notice" class="alert alert-success py-2" role="status">{{ notice }}</p>

            <div
                v-if="cards.length"
                class="staff-pizza-grid"
                :class="{ 'has-editor': editor }"
            >
                <article
                    v-for="pizza in cards"
                    :key="pizza?.id ?? 'new'"
                    class="staff-pizza-card"
                    :data-pizza-id="pizza?.id"
                    :class="{
                        'is-house': pizza?.is_house_pizza,
                        'is-hidden': pizza && !pizza.is_visible,
                        'is-new': !pizza,
                        'is-editing': editor && (!pizza || editor.original?.id === pizza.id),
                    }"
                >
                    <template v-if="editor && (!pizza || editor.original?.id === pizza.id)">
                        <h3 class="staff-pizza-title mb-2" data-editor-title tabindex="-1">
                            {{ pizza ? `Modifica ${pizza.name}` : category.newLabel }}
                        </h3>
                        <p v-if="editor.error" class="alert alert-danger py-2 mb-2" role="alert">
                            {{ editor.error }}
                        </p>
                        <PizzaForm
                            :pizza="editor.original"
                            :single-price="!isTranci"
                            :description-label="category.description"
                            :busy="saving"
                            :submit-label="pizza || editor.saved ? 'Salva modifiche' : `Aggiungi ${category.singular}`"
                            @save="saveEditor"
                            @cancel="closeEditor"
                        >
                            <template #appearance>
                                <fieldset class="staff-appearance mb-2">
                                    <legend>{{ canManageHomepage ? 'Visibilità e homepage' : 'Visibilità' }}</legend>
                                    <label class="staff-switch-row form-check form-switch">
                                        <span>Visibile nel menù</span>
                                        <input
                                            v-model="editor.visible"
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            @change="resetHomepageWhenHidden"
                                        >
                                    </label>
                                    <template v-if="canManageHomepage">
                                        <label class="staff-switch-row form-check form-switch">
                                            <span>In evidenza</span>
                                            <input
                                                v-model="editor.featured"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                :disabled="!editor.visible || (featuredFull && !editor.featured)"
                                                @change="!editor.featured && (editor.house = false)"
                                            >
                                        </label>
                                        <label class="staff-switch-row form-check form-switch">
                                            <span>Pizza della casa</span>
                                            <input
                                                v-model="editor.house"
                                                class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                :disabled="!editor.visible || (featuredFull && !editor.house)"
                                                @change="editor.house && (editor.featured = true)"
                                            >
                                        </label>
                                        <p v-if="!editor.visible" class="staff-gold mt-2 mb-0">
                                            Rendi visibile la pizza per metterla in evidenza.
                                        </p>
                                        <p v-else-if="featuredFull" class="staff-gold mt-2 mb-0">
                                            Hai già sei pizze in evidenza. Libera un posto per aggiungerne un’altra.
                                        </p>
                                    </template>
                                </fieldset>
                            </template>
                        </PizzaForm>
                    </template>

                    <template v-else>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="staff-state" :class="{ 'state-hidden': !pizza.is_visible }">
                                <span class="staff-state-dot" aria-hidden="true"></span>
                                {{ pizza.is_visible ? 'Visibile' : category.id === 'sfizi' ? 'Nascosto' : 'Nascosta' }}
                            </span>
                            <span v-if="pizza.is_featured" class="staff-state state-featured">
                                <span v-if="pizza.is_house_pizza" class="me-1" aria-hidden="true">★</span>
                                {{ pizza.is_house_pizza ? 'Pizza della casa' : 'In evidenza' }}
                            </span>
                        </div>
                        <h3 class="staff-pizza-title">{{ pizza.name }}</h3>
                        <p v-if="pizza.ingredients || pizza.description" class="staff-ingredients staff-muted">
                            {{ pizza.ingredients ?? pizza.description }}
                        </p>
                        <dl v-if="isTranci || pizza.price != null" class="staff-prices">
                            <template v-if="isTranci">
                                <div>
                                    <dt>Trancio</dt>
                                    <dd>{{ euro.format(pizza.price_trancio) }}</dd>
                                </div>
                                <div>
                                    <dt>Teglia</dt>
                                    <dd>{{ euro.format(pizza.price_teglia) }}</dd>
                                </div>
                            </template>
                            <div v-else>
                                <dt>Prezzo</dt>
                                <dd>{{ euro.format(pizza.price) }}</dd>
                            </div>
                        </dl>
                        <div class="staff-card-actions d-flex flex-wrap gap-2 justify-content-between">
                            <button
                                type="button"
                                class="btn staff-quiet staff-visibility-button"
                                :disabled="controlsDisabled"
                                :aria-label="`${menuVisibilityLabel(pizza)} · ${pizza.name}`"
                                @click="changeVisibility(pizza)"
                            >
                                {{ menuVisibilityLabel(pizza) }}
                            </button>
                            <button
                                class="btn btn-outline-secondary staff-edit-button"
                                type="button"
                                :disabled="controlsDisabled"
                                :aria-label="`Modifica ${pizza.name}`"
                                @click="openEditor(pizza)"
                            >
                                Modifica
                            </button>
                        </div>
                    </template>
                </article>
            </div>
            <div v-else class="staff-toolbar p-4 text-center">
                <p class="mb-2">{{ products.length ? 'Nessun prodotto corrisponde ai filtri.' : 'Nessun prodotto in questa categoria.' }}</p>
                <button
                    v-if="search || filter.id !== 'all'"
                    class="btn btn-outline-primary"
                    type="button"
                    @click="search = ''; selectFilter(filters[0])"
                >
                    Azzera ricerca e filtri
                </button>
            </div>

            <nav v-if="pageCount > 1" class="d-flex flex-wrap gap-2 justify-content-center mt-4" aria-label="Pagine del menù">
                <button
                    class="btn staff-page"
                    type="button"
                    aria-label="Pagina precedente"
                    :disabled="page === 1 || controlsDisabled"
                    @click="showPage(page - 1)"
                >‹</button>
                <button
                    v-for="number in pageCount"
                    :key="number"
                    class="btn staff-page"
                    type="button"
                    :aria-label="`Pagina ${number}`"
                    :aria-current="page === number ? 'page' : undefined"
                    :disabled="controlsDisabled"
                    @click="showPage(number)"
                >{{ number }}</button>
                <button
                    class="btn staff-page"
                    type="button"
                    aria-label="Pagina successiva"
                    :disabled="page === pageCount || controlsDisabled"
                    @click="showPage(page + 1)"
                >›</button>
            </nav>
        </template>
    </section>
</template>

<style scoped>
.staff-catalog {
    container-type: inline-size;
    font-size: .88rem;
}

.staff-categories {
    display: flex;
    flex-wrap: wrap;
    gap: .25rem .35rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--staff-line);
}

.staff-category {
    --bs-btn-color: var(--staff-muted);
    --bs-btn-hover-color: var(--staff-gold);
    margin-bottom: -1px;
    padding-inline: .85rem;
    border-radius: .45rem .45rem 0 0;
}
.staff-category[aria-pressed='true'] {
    border-bottom: 2px solid #af762b;
    background: #fbf1df;
    color: var(--staff-gold);
}

.staff-muted { color: var(--staff-muted); }
.staff-gold { color: var(--staff-gold); }
.staff-meta { font-size: .8rem; font-weight: 550; }
.staff-toolbar, .staff-pizza-card {
    border: 1px solid var(--staff-line);
    border-radius: .7rem;
    background: var(--staff-surface);
    box-shadow: var(--staff-shadow);
}
.staff-toolbar { padding: .6rem; }

.staff-search { flex: 1 1 15rem; min-width: 0; max-width: 22rem; }
.staff-search input { background: #f9f7f3; }
.staff-filters { display: flex; flex-wrap: wrap; gap: .15rem; }
.staff-quiet, .staff-filter { --bs-btn-color: var(--staff-muted); --bs-btn-hover-color: var(--staff-ink); --bs-btn-hover-bg: #f6f3ed; }
.staff-filter[aria-pressed='true'], .staff-page[aria-current='page'] {
    color: var(--staff-gold);
    background: #f9ecd5;
    border-color: #efdbb8;
}
.staff-page {
    min-width: 42px;
    --bs-btn-color: var(--staff-muted);
    --bs-btn-border-color: var(--staff-line);
    --bs-btn-hover-color: var(--staff-gold);
    --bs-btn-hover-bg: var(--staff-gold-tint);
}

.staff-pizza-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: .85rem; }
.staff-pizza-grid.has-editor { align-items: start; }
.staff-pizza-card { min-width: 0; min-height: 202px; padding: .9rem; display: flex; flex-direction: column; }
.staff-pizza-card.is-hidden { background: #fbfaf8; }
.staff-pizza-card.is-house { border-color: #deb986; background: linear-gradient(180deg, #fff9ee 0, #fff 170px); }
.staff-pizza-card.is-editing { border-color: #cd9849; background: #fffcf6; }
.staff-pizza-card.is-new { grid-column: 1 / -1; width: min(100%, 26rem); }
.staff-pizza-title { margin: 0; font-size: 1.17rem; font-weight: 700; letter-spacing: -.025em; overflow-wrap: anywhere; }
.staff-prices { display: flex; gap: 1.4rem; margin: .4rem 0 .65rem; white-space: nowrap; }
.staff-prices dt { color: var(--staff-muted); font-size: .72rem; font-weight: 500; }
.staff-prices dd { margin: .1rem 0 0; color: #3d2b16; font-weight: 700; font-variant-numeric: tabular-nums; }
.staff-ingredients { flex: 1; margin: .45rem 0 .6rem; font-size: .82rem; overflow-wrap: anywhere; }
.staff-card-actions { margin-top: auto; border-top: 1px solid var(--staff-line); padding-top: .55rem; }
.staff-state { display: inline-flex; gap: .28rem; align-items: center; padding: .18rem .43rem; border-radius: .3rem; background: #e6f2e9; color: #23583e; font-size: .72rem; font-weight: 650; }
.staff-state-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.state-hidden { color: #5d554b; background: #eeebe6; }
.state-featured { color: var(--staff-gold); background: var(--staff-gold-tint); }

.staff-appearance { min-width: 0; }
.staff-appearance legend { font-size: .9rem; font-weight: 650; }
.staff-switch-row {
    display: flex;
    gap: .5rem;
    align-items: center;
    justify-content: space-between;
    min-height: 40px;
    margin: 0;
    padding: .4rem 0;
    border-bottom: 1px solid var(--staff-line);
    cursor: pointer;
}
.staff-switch-row .form-check-input { flex-shrink: 0; float: none; margin: 0; }

@container (min-width: 610px) {
    .staff-pizza-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@container (min-width: 900px) {
    .staff-pizza-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@container (max-width: 560px) {
    .staff-search { max-width: none; }
    .staff-filters, .staff-categories { display: grid; width: 100%; grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

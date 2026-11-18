<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    pizza: { type: Object, default: null },
    singlePrice: Boolean,
    descriptionLabel: { type: String, default: 'Ingredienti' },
    busy: Boolean,
    submitLabel: { type: String, required: true },
});

const emit = defineEmits(['save', 'cancel']);

const draft = reactive({
    name: props.pizza?.name ?? '',
    ingredients: props.pizza?.ingredients ?? '',
    price_trancio: String(props.pizza?.price_trancio ?? ''),
    price_teglia: String(props.pizza?.price_teglia ?? ''),
    description: props.pizza?.description ?? '',
    price: String(props.pizza?.price ?? ''),
});

const priceFields = computed(() => props.singlePrice
    ? [{ key: 'price', label: 'Prezzo (€) · facoltativo' }]
    : [
        { key: 'price_trancio', label: 'Prezzo trancio (€)' },
        { key: 'price_teglia', label: 'Prezzo teglia (€)' },
    ],
);

function submit() {
    if (props.singlePrice) {
        emit('save', {
            name: draft.name,
            description: draft.description || null,
            price: draft.price === '' ? null : draft.price.replace(',', '.'),
        });
        return;
    }

    emit('save', {
        name: draft.name,
        ingredients: draft.ingredients,
        price_trancio: draft.price_trancio.replace(',', '.'),
        price_teglia: draft.price_teglia.replace(',', '.'),
    });
}
</script>

<template>
    <form class="mt-3" @submit.prevent="submit">
        <fieldset :disabled="busy">
            <label class="form-label d-block mb-3">
                Nome
                <input v-model.trim="draft.name" type="text"
                       class="form-control" maxlength="255" required>
            </label>

            <label v-if="!singlePrice" class="form-label d-block mb-3">
                Ingredienti
                <textarea v-model.trim="draft.ingredients" class="form-control"
                          rows="3" required></textarea>
            </label>
            <label v-else class="form-label d-block mb-3">
                {{ descriptionLabel }}
                <textarea v-model.trim="draft.description" class="form-control"
                          rows="2" maxlength="1000"></textarea>
            </label>

            <div class="row g-2 mb-2">
                <label v-for="field in priceFields" :key="field.key" class="form-label"
                       :class="singlePrice ? 'col-12' : 'col-sm-6'">
                    {{ field.label }}
                    <input v-model.trim="draft[field.key]" type="text"
                           inputmode="decimal" class="form-control" :required="!singlePrice">
                </label>
            </div>

            <slot name="appearance" />

            <button type="submit" class="btn btn-primary btn-sm me-2">
                {{ busy ? 'Salvataggio…' : submitLabel }}
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    @click="emit('cancel')">
                Annulla
            </button>
        </fieldset>
    </form>
</template>

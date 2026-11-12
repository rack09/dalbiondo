<script setup>
import { reactive } from 'vue';

const props = defineProps({
    pizza: { type: Object, default: null },
    busy: Boolean,
    submitLabel: { type: String, required: true },
});

const emit = defineEmits(['save', 'cancel']);

const draft = reactive({
    name: props.pizza?.name ?? '',
    ingredients: props.pizza?.ingredients ?? '',
    price_trancio: String(props.pizza?.price_trancio ?? ''),
    price_teglia: String(props.pizza?.price_teglia ?? ''),
});

const priceFields = [
    { key: 'price_trancio', label: 'Prezzo trancio (€)' },
    { key: 'price_teglia', label: 'Prezzo teglia (€)' },
];

function submit() {
    emit('save', {
        ...draft,
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

            <label class="form-label d-block mb-3">
                Ingredienti
                <textarea v-model.trim="draft.ingredients" class="form-control"
                          rows="3" required></textarea>
            </label>

            <div class="row g-2 mb-2">
                <label v-for="field in priceFields" :key="field.key" class="col-sm-6 form-label">
                    {{ field.label }}
                    <input v-model.trim="draft[field.key]" type="text"
                           inputmode="decimal" class="form-control" required>
                </label>
            </div>

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
<script setup>
defineProps({
    pizzas: { type: Array, required: true },
});

const euro = new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
});
</script>

<template>
    <ul class="priced-menu-list list-unstyled row row-cols-1 row-cols-md-2 gx-md-5 mb-0">
        <li v-for="pizza in pizzas" :key="pizza.id" class="col">
            <article class="dish priced-dish border-bottom">
                <div class="dish-heading d-flex align-items-center justify-content-between gap-3">
                    <h3 class="mb-0">{{ pizza.name }}</h3>

                    <dl class="dish-prices d-flex flex-shrink-0 gap-3 text-end mb-0">
                        <div class="w-50">
                            <dt class="fw-normal">Trancio</dt>
                            <dd class="dish-price text-primary text-nowrap mb-0">
                                {{ euro.format(Number(pizza.price_trancio)) }}
                            </dd>
                        </div>
                        <div class="w-50">
                            <dt class="fw-normal">Teglia</dt>
                            <dd class="dish-price text-primary text-nowrap mb-0">
                                {{ euro.format(Number(pizza.price_teglia)) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <p class="text-body-secondary mb-0">{{ pizza.ingredients }}</p>
            </article>
        </li>
    </ul>
</template>
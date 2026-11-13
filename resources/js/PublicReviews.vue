<script setup>
const reviews = [
    {
        author: 'Christian Lione',
        quote: 'migliori pizza al trancio mai mangiata in vita mia, stra consiglio',
    },
    {
        author: "alessandro zaccaro (L'Americano)",
        quote: 'Trancio TOP. […] Puntualità e cortesia 10 e lode.',
    },
];
</script>

<template>
    <section id="recensioni" class="reviews-section section-pad" aria-labelledby="reviews-title">
        <div class="container">
            <div class="section-heading text-center mx-auto">
                <h2 id="reviews-title">Chi ci conosce,<br>ci racconta così.</h2>
                <div
                    class="ornament d-flex align-items-center justify-content-center text-primary"
                    aria-hidden="true"
                >
                    <span class="d-block"></span>◆<span class="d-block"></span>
                </div>
                <p>Una selezione di recensioni lasciate su Google.</p>
            </div>

            <div class="reviews-grid">
                <div class="row row-cols-1 row-cols-md-2 gx-4 gy-3">
                    <div v-for="review in reviews" :key="review.author" class="col">
                        <figure class="review-card d-flex flex-column h-100 mb-0">
                            <div class="review-card-top d-flex align-items-center justify-content-between gap-3">
                                <span class="review-stars text-primary text-nowrap" role="img" aria-label="5 stelle su 5">
                                    ★★★★★
                                </span>
                                <span class="review-source text-body-secondary">Google · estratto</span>
                            </div>

                            <blockquote>
                                <p>«{{ review.quote }}»</p>
                            </blockquote>

                            <figcaption class="mt-auto text-primary">{{ review.author }}</figcaption>
                        </figure>
                    </div>
                </div>
            </div>

            <div class="reviews-footer text-center">
                <a
                    class="text-link"
                    href="https://www.google.com/maps/place/Pizza+Al+Trancio+Dal+Biondo/@45.55222,9.2225842,17z/data=!4m8!3m7!1s0x4786b8c867cb1fab:0xa2150dd7150b54c1!8m2!3d45.55222!4d9.2225842!9m1!1b1!16s%2Fg%2F11ddwxwltz"
                >
                    Leggi tutte le recensioni su Google
                    <span class="arrow" aria-hidden="true">
                        <svg viewBox="0 0 16 16"><path d="M3 13 13 3M4 3h9v9" /></svg>
                    </span>
                </a>
                <p class="text-body-secondary mb-0">Estratti selezionati, consultati l’8 novembre 2026.</p>
            </div>
        </div>
    </section>
</template>
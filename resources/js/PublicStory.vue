<script setup>
import { computed, ref } from "vue";

const photos = [
    {
        file: "staff-storico.webp",
        width: 1350,
        height: 1080,
        caption: "Foto storica dello staff",
        alt: "Tre persone dietro il banco, davanti al forno della pizzeria.",
    },
    {
        file: "staff-archivio-2.webp",
        width: 1024,
        height: 768,
        caption: "Ricordi dietro il banco",
        alt: "Quattro persone dietro il banco, con arancini e panzerotti in vetrina.",
    },
    {
        file: "staff-archivio-3.webp",
        width: 768,
        height: 1024,
        caption: "Volti di Dal Biondo",
        alt: "Due persone dietro il banco, con le scatole della pizza sullo sfondo.",
    },
];

const photoIndex = ref(0);
const currentPhoto = computed(() => photos[photoIndex.value]);

function changePhoto(step) {
    photoIndex.value =
        (photoIndex.value + step + photos.length) % photos.length;
}
</script>

<template>
    <section
        id="storia"
        class="story-section section-pad"
        aria-labelledby="story-title"
    >
        <div class="container">
            <div class="row gy-4 align-items-center">
                <div class="col-lg-6">
                    <figure
                        class="story-photo staff-carousel carousel is-ready"
                    >
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img
                                    :src="`/dal-biondo/${currentPhoto.file}`"
                                    :alt="currentPhoto.alt"
                                    :width="currentPhoto.width"
                                    :height="currentPhoto.height"
                                    class="d-block w-100 h-100 object-fit-contain"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </div>
                        </div>

                        <figcaption>
                            <div
                                class="staff-caption d-flex align-items-center justify-content-between gap-2"
                                aria-live="polite"
                                aria-atomic="true"
                            >
                                <div class="staff-caption-copy">
                                    <span class="d-block text-primary"
                                        >Dal nostro album</span
                                    >
                                    <small class="d-block">{{
                                        currentPhoto.caption
                                    }}</small>
                                </div>
                                <span class="staff-counter text-nowrap">
                                    Foto {{ photoIndex + 1 }} di
                                    {{ photos.length }}
                                </span>
                            </div>

                            <div
                                class="staff-carousel-controls align-items-center justify-content-between gap-2"
                            >
                                <button
                                    class="staff-button d-inline-flex align-items-center justify-content-center p-0 bg-transparent text-primary"
                                    type="button"
                                    aria-label="Foto precedente"
                                    @click="changePhoto(-1)"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 20 20">
                                        <path d="m12 4-6 6 6 6" />
                                    </svg>
                                </button>

                                <div
                                    class="staff-indicators d-flex"
                                    role="group"
                                    aria-label="Scegli una foto"
                                >
                                    <button
                                        v-for="(photo, index) in photos"
                                        :key="photo.file"
                                        class="border-0 bg-transparent p-0 text-primary"
                                        type="button"
                                        :aria-label="`Foto ${index + 1}`"
                                        :aria-current="
                                            index === photoIndex
                                                ? 'true'
                                                : undefined
                                        "
                                        @click="photoIndex = index"
                                    ></button>
                                </div>

                                <button
                                    class="staff-button d-inline-flex align-items-center justify-content-center p-0 bg-transparent text-primary"
                                    type="button"
                                    aria-label="Foto successiva"
                                    @click="changePhoto(1)"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 20 20">
                                        <path d="m8 4 6 6-6 6" />
                                    </svg>
                                </button>
                            </div>
                        </figcaption>
                    </figure>
                </div>

                <div class="col-lg-5 offset-lg-1 story-copy">
                    <p class="eyebrow text-primary">LE PERSONE DI DAL BIONDO</p>
                    <h2 id="story-title">
                        Dietro ogni trancio,<br />ci siamo noi.
                    </h2>
                    <p class="story-intro">
                        Il nostro banco. Il nostro forno.<br />
                        Un pezzo della nostra storia.
                    </p>
                    <p class="story-text">
                        Fotografie del nostro staff, dall’album della pizzeria.
                        Volti, ricordi e momenti della nostra storia a
                        Cinisello, dal 1998.
                    </p>
                    <p
                        class="story-signature border-start border-2 border-primary text-primary mb-0"
                    >
                        Dal Biondo, a Cinisello Balsamo.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>

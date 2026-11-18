<script setup>
import { computed, onMounted, ref } from "vue";

const photos = ref([]);
const photoIndex = ref(0);
const currentPhoto = computed(() => photos.value[photoIndex.value]);

onMounted(async () => {
    try {
        const response = await fetch("/api/staff-photos", {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) return;

        const data = await response.json();
        if (Array.isArray(data)) photos.value = data;
    } catch {
        // La storia resta leggibile anche quando il catalogo delle foto non risponde.
    }
});

function changePhoto(step) {
    const count = photos.value.length;
    if (count < 2) return;
    photoIndex.value = (photoIndex.value + step + count) % count;
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
                <div v-if="currentPhoto" class="col-lg-6">
                    <figure
                        class="story-photo staff-carousel carousel is-ready"
                    >
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img
                                    :src="currentPhoto.url"
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
                                    <span class="d-block text-primary">
                                        <template v-if="currentPhoto.is_featured">
                                            <span aria-hidden="true">★</span> Foto in evidenza
                                        </template>
                                        <template v-else>Dal nostro album</template>
                                    </span>
                                    <small class="d-block">{{
                                        currentPhoto.caption
                                    }}</small>
                                </div>
                                <span v-if="photos.length > 1" class="staff-counter text-nowrap">
                                    Foto {{ photoIndex + 1 }} di
                                    {{ photos.length }}
                                </span>
                            </div>

                            <div
                                v-if="photos.length > 1"
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
                                        :key="photo.id"
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

                <div class="story-copy" :class="currentPhoto ? 'col-lg-5 offset-lg-1' : 'col-12'">
                    <p class="eyebrow text-primary">LE PERSONE DI DAL BIONDO</p>
                    <h2 id="story-title">
                        Dietro ogni trancio,<br />ci siamo noi.
                    </h2>
                    <p class="story-intro">
                        Il nostro banco. Il nostro forno.<br />
                        Un pezzo della nostra storia.
                    </p>
                    <p class="story-text">
                        <template v-if="currentPhoto">Fotografie del nostro staff, dall’album della pizzeria.</template>
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

<script setup>
import PublicOpeningHours from './PublicOpeningHours.vue';

defineProps({
    contact: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['status-change']);
</script>

<template>
    <section id="contatti" class="contact-section section-pad" aria-labelledby="contact-title">
        <div class="container">
            <div class="row gy-5 gx-lg-5">
                <div class="col-lg-5">
                    <p class="eyebrow text-primary">CI VEDIAMO DAL BIONDO</p>
                    <h2 id="contact-title">Siamo qui.<br>Da un bel po’.</h2>
                    <p class="contact-intro">
                        A Cinisello Balsamo, dal 1998.<br>
                        Ti aspettiamo per il tuo prossimo trancio.
                    </p>
                    <address v-if="contact.address?.street">
                        {{ contact.address.street }}<br>
                        {{ contact.address.postal_code }} {{ contact.address.locality }}
                    </address>
                    <a
                        class="text-link"
                        href="https://www.google.com/maps/place/Pizza+Al+Trancio+Dal+Biondo/@45.55222,9.2225842,17z/data=!3m1!4b1!4m6!3m5!1s0x4786b8c867cb1fab:0xa2150dd7150b54c1!8m2!3d45.55222!4d9.2225842!16s%2Fg%2F11ddwxwltz?entry=ttu"
                    >
                        Indicazioni su Google Maps
                        <span class="arrow" aria-hidden="true">
                            <svg viewBox="0 0 16 16"><path d="M3 13 13 3M4 3h9v9" /></svg>
                        </span>
                    </a>
                </div>

                <div class="col-lg-6 offset-lg-1">
                    <PublicOpeningHours id="orari" @status-change="emit('status-change', $event)" />

                    <p class="delivery-note">
                        Gli orari delle consegne possono variare. Consulta la disponibilità su
                        <a href="https://www.justeat.it/restaurants-pizzeriaaltranciodalbiondo/menu">Just Eat</a>.
                    </p>
                    <div
                        v-if="contact.phone_href || contact.whatsapp_url"
                        class="d-flex flex-wrap align-items-center gap-3 mt-3"
                    >
                        <a v-if="contact.phone_href" class="contact-phone text-decoration-none mt-0" :href="contact.phone_href">
                            {{ contact.phone }}
                            <span class="arrow" aria-hidden="true">
                                <svg viewBox="0 0 16 16"><path d="M3 13 13 3M4 3h9v9" /></svg>
                            </span>
                        </a>
                        <a v-if="contact.whatsapp_url" class="text-link" :href="contact.whatsapp_url">
                            Scrivici su WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

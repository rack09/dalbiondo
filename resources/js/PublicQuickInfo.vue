<script setup>
defineProps({
    contact: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="quick-info">
        <div class="container">
            <div
                class="row row-cols-1 gy-4 align-items-start"
                :class="contact.phone_href || contact.whatsapp_url ? 'row-cols-md-3' : 'row-cols-md-2'"
            >
                <div v-if="contact.phone_href || contact.whatsapp_url" class="col info-item d-flex">
                    <svg class="line-icon text-primary flex-shrink-0" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="m8 3 2 5-2 2c1.2 2.5 3.5 4.8 6 6l2-2 5 2v3a2 2 0 0 1-2 2C10.2 21 3 13.8 3 5a2 2 0 0 1 2-2h3Z" />
                    </svg>
                    <div>
                        <template v-if="contact.phone_href">
                            <a :href="contact.phone_href">{{ contact.phone }}</a>
                            <p>Chiamaci per l’asporto</p>
                        </template>
                        <a
                            v-if="contact.whatsapp_url"
                            class="d-inline-block"
                            :class="{ 'mt-2': contact.phone_href }"
                            :href="contact.whatsapp_url"
                        >Scrivici su WhatsApp</a>
                    </div>
                </div>

                <div class="col info-item d-flex">
                    <svg class="line-icon text-primary flex-shrink-0" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z" />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>
                    <div>
                        <a href="#contatti">{{ contact.address?.street || 'Dove siamo' }}</a>
                        <p v-if="contact.address?.locality">{{ contact.address.locality }}</p>
                    </div>
                </div>

                <div class="col info-item d-flex">
                    <svg class="line-icon text-primary flex-shrink-0" aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    <div>
                        <a href="#orari">Il tuo prossimo trancio?</a>
                        <p>Consulta gli orari di apertura</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

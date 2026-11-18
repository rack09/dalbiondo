<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import { readCsrfToken } from './csrf.js';
import { sendJson } from './postJson.js';
import StaffGalleryVisibility from './StaffGalleryVisibility.vue';

defineProps({
    canManageGallery: { type: Boolean, default: false },
});

const photos = ref([]);
const loading = ref(true);
const loadError = ref('');
const actionError = ref('');
const notice = ref('');
const formOpen = ref(false);
const uploading = ref(false);
const updating = ref(false);
const needsReview = ref(false);
const uploadButton = ref(null);
const fileInput = ref(null);
const selectedFile = ref(null);
const previewUrl = ref('');
const caption = ref('');
const alt = ref('');
const visible = ref(false);
const uploadError = ref('');

const endpoint = '/api/staff/photos';
const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
const maxFileSize = 5 * 1024 * 1024;
const busy = computed(() => uploading.value || updating.value);

async function readPhotos() {
    const response = await fetch(endpoint, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (response.status === 401) throw new Error('Sessione scaduta. Accedi di nuovo.');
    if (!response.ok) throw new Error('Impossibile caricare le foto. Riprova.');

    const data = await response.json();
    if (!Array.isArray(data)) throw new Error('Impossibile caricare le foto. Riprova.');
    photos.value = data;
}

async function loadPhotos() {
    loading.value = true;
    loadError.value = '';

    try {
        await readPhotos();
    } catch (error) {
        loadError.value = error.message === 'Sessione scaduta. Accedi di nuovo.'
            ? error.message
            : 'Impossibile caricare le foto. Riprova.';
    } finally {
        loading.value = false;
    }
}

function clearPreview() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = '';
    selectedFile.value = null;
}

function chooseFile(event) {
    clearPreview();
    uploadError.value = '';
    const file = event.target.files?.[0];
    if (!file) return;

    if ((file.type && !allowedTypes.includes(file.type)) || file.size > maxFileSize) {
        event.target.value = '';
        uploadError.value = 'Seleziona una foto JPG, PNG o WebP da massimo 5 MB.';
        return;
    }

    selectedFile.value = file;
    previewUrl.value = URL.createObjectURL(file);
}

async function openForm() {
    notice.value = '';
    actionError.value = '';
    formOpen.value = true;
    await nextTick();
    fileInput.value?.focus();
}

function closeForm() {
    formOpen.value = false;
    clearPreview();
    caption.value = '';
    alt.value = '';
    visible.value = false;
    uploadError.value = '';
    needsReview.value = false;
    void nextTick(() => uploadButton.value?.focus());
}

function putPhoto(photo) {
    const index = photos.value.findIndex(item => item.id === photo.id);
    if (index < 0) {
        photos.value.push(photo);
    } else {
        photos.value[index] = photo;
    }
}

async function reviewUpload() {
    needsReview.value = true;
    uploadError.value = 'Caricamento non confermato. Controlla l’elenco prima di riprovare.';
    await readPhotos().catch(() => {});
}

function uploadFailure(status, data) {
    if (status === 401 || status === 419) return 'Sessione scaduta. Ricarica la pagina e accedi.';
    if (status === 413) return 'Il server non accetta file di queste dimensioni.';
    if (status === 422) {
        const errors = data?.errors ?? {};
        if (errors.photo) return 'Seleziona una foto JPG, PNG o WebP da massimo 5 MB e 6000 × 6000 pixel.';
        if (errors.caption) return 'Inserisci una didascalia di massimo 160 caratteri.';
        if (errors.alt) return 'Inserisci un testo alternativo di massimo 200 caratteri.';
    }
    return 'Impossibile caricare la foto. Riprova.';
}

async function uploadPhoto() {
    if (busy.value || needsReview.value || !selectedFile.value) return;

    const csrf = readCsrfToken();
    if (!csrf) {
        uploadError.value = uploadFailure(419);
        return;
    }

    uploading.value = true;
    uploadError.value = '';
    notice.value = '';

    try {
        const values = new FormData();
        values.append('photo', selectedFile.value);
        values.append('caption', caption.value);
        values.append('alt', alt.value);
        values.append('is_visible', visible.value ? '1' : '0');

        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': csrf },
            body: values,
        }).catch(() => null);
        if (!response || response.status >= 500) {
            await reviewUpload();
            return;
        }

        const saved = await response.json().catch(() => null);
        if (!response.ok) {
            uploadError.value = uploadFailure(response.status, saved);
            return;
        }
        if (!saved?.id || !saved.url) {
            await reviewUpload();
            return;
        }

        putPhoto(saved);
        closeForm();
        notice.value = saved.is_visible ? 'Foto caricata e impostata come visibile.' : 'Foto caricata come nascosta.';
    } finally {
        uploading.value = false;
    }
}

async function savePhotoChange(path, values, onSave, method = 'PATCH') {
    if (busy.value) return;

    updating.value = true;
    actionError.value = '';
    notice.value = '';

    try {
        const saved = await sendJson(endpoint + path, values, method);
        onSave(saved);
    } catch (error) {
        await readPhotos().catch(() => {});
        actionError.value = error.message;
    } finally {
        updating.value = false;
    }
}

function changeVisibility(photo) {
    return savePhotoChange('/' + photo.id, { is_visible: !photo.is_visible }, saved => {
        putPhoto(saved);
        notice.value = saved.is_visible ? 'Foto impostata come visibile.' : 'Foto nascosta dal sito.';
    });
}

function changeFeatured(photo) {
    return savePhotoChange('/' + photo.id + '/featured', { is_featured: !photo.is_featured }, saved => {
        photos.value = photos.value.map(item => ({
            ...item,
            is_featured: item.is_visible && item.id === saved.featured_photo_id,
        }));
        notice.value = saved.featured_photo_id === photo.id
            ? 'Foto in evidenza aggiornata.'
            : 'Foto rimossa dall’evidenza.';
    });
}

function deletePhoto(photo) {
    if (busy.value || !window.confirm('Eliminare «' + photo.caption + '» dall’album?')) return;

    return savePhotoChange('/' + photo.id, {}, deleted => {
        photos.value = photos.value.filter(item => item.id !== deleted.id);
        notice.value = 'Foto eliminata dall’album.';
    }, 'DELETE');
}

onMounted(loadPhotos);
onBeforeUnmount(clearPreview);
</script>

<template>
    <section aria-labelledby="staff-photos-title">
        <header class="staff-page-heading">
            <div>
                <h1 id="staff-photos-title">Foto dello staff</h1>
                <p>La foto in evidenza apre il carosello sulla pagina pubblica.</p>
            </div>
            <button
                ref="uploadButton" class="btn btn-primary" type="button"
                :disabled="busy || formOpen || loading || Boolean(loadError)"
                aria-controls="staff-photo-upload" :aria-expanded="formOpen"
                @click="openForm"
            ><span aria-hidden="true">+</span> Carica foto</button>
        </header>

        <StaffGalleryVisibility :can-manage="canManageGallery" />

        <form
            v-if="formOpen"
            id="staff-photo-upload"
            class="staff-panel mb-3"
            enctype="multipart/form-data"
            @submit.prevent="uploadPhoto"
        >
            <h2>Nuova foto</h2>
            <p v-if="uploadError" class="alert alert-danger" role="alert">{{ uploadError }}</p>
            <fieldset :disabled="busy">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label d-block">
                            Scegli una foto
                            <input
                                ref="fileInput" class="form-control mt-1" type="file" name="photo"
                                accept="image/jpeg,image/png,image/webp" required aria-describedby="staff-photo-formats"
                                @change="chooseFile"
                            >
                        </label>
                        <p id="staff-photo-formats" class="form-text mb-2">JPG, PNG o WebP · max 5 MB.</p>
                        <img
                            v-if="previewUrl" :src="previewUrl" class="staff-photo-preview"
                            alt="Anteprima della foto selezionata"
                        >
                    </div>
                    <div class="col-md-7">
                        <label class="form-label d-block">
                            Didascalia
                            <input v-model.trim="caption" class="form-control mt-1" type="text" maxlength="160" required>
                        </label>
                        <label class="form-label d-block">
                            Testo alternativo <span class="fw-normal text-body-secondary">· facoltativo</span>
                            <input v-model.trim="alt" class="form-control mt-1" type="text" maxlength="200">
                        </label>
                        <label class="form-check form-switch mt-3">
                            <input v-model="visible" class="form-check-input" type="checkbox" role="switch">
                            <span class="form-check-label">Visibile sul sito</span>
                        </label>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button class="btn btn-primary" type="submit" :disabled="needsReview">
                        {{ uploading ? 'Caricamento…' : 'Salva foto' }}
                    </button>
                    <button class="btn btn-outline-secondary" type="button" @click="closeForm">
                        {{ needsReview ? 'Chiudi' : 'Annulla' }}
                    </button>
                </div>
            </fieldset>
        </form>

        <p v-if="actionError" class="alert alert-danger" role="alert">{{ actionError }}</p>
        <p v-if="notice" class="alert alert-success" role="status">{{ notice }}</p>

        <p v-if="loading" role="status">Caricamento delle foto…</p>
        <div v-else-if="loadError" class="alert alert-danger" role="alert">
            <p class="mb-2">{{ loadError }}</p>
            <button class="btn btn-outline-secondary" type="button" @click="loadPhotos">Riprova</button>
        </div>
        <p v-else-if="photos.length === 0" class="staff-panel mb-0">Nessuna foto caricata.</p>
        <div v-else class="staff-photo-grid">
            <article v-for="photo in photos" :key="photo.id" class="staff-panel staff-photo-card">
                <img class="staff-photo-thumbnail" :src="photo.url" :alt="photo.alt" loading="lazy" decoding="async">
                <h2 class="mt-3 mb-2">{{ photo.caption }}</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="staff-photo-status" :class="{ 'is-hidden': !photo.is_visible }">
                        {{ photo.is_visible ? 'Visibile' : 'Nascosta' }}
                    </span>
                    <span v-if="photo.is_featured" class="staff-photo-status is-featured">
                        <span aria-hidden="true">★</span> In evidenza
                    </span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-auto">
                    <button
                        v-if="photo.is_visible" class="btn btn-outline-primary" type="button" :disabled="busy"
                        :aria-label="(photo.is_featured ? 'Rimuovi dall’evidenza ' : 'Metti in evidenza ') + photo.caption"
                        @click="changeFeatured(photo)"
                    >{{ photo.is_featured ? 'Rimuovi evidenza' : 'Metti in evidenza' }}</button>
                    <button
                        class="btn btn-outline-secondary" type="button" :disabled="busy"
                        :aria-label="(photo.is_visible ? 'Nascondi ' : 'Mostra ') + photo.caption"
                        @click="changeVisibility(photo)"
                    >{{ photo.is_visible ? 'Nascondi' : 'Mostra' }}</button>
                    <button
                        class="btn btn-outline-danger" type="button" :disabled="busy"
                        :aria-label="'Elimina ' + photo.caption"
                        @click="deletePhoto(photo)"
                    >Elimina</button>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.staff-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 225px), 1fr));
    gap: 1rem;
}
.staff-photo-card {
    display: flex;
    flex-direction: column;
}
.staff-photo-card h2 { overflow-wrap: anywhere; }
.staff-photo-thumbnail,
.staff-photo-preview {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
    border: 1px solid var(--staff-line);
    border-radius: .5rem;
    background: var(--staff-paper);
    object-fit: contain;
}
.staff-photo-preview { max-width: 23rem; }
.staff-photo-status {
    padding: .2rem .45rem;
    border-radius: .35rem;
    background: #e6f2e9;
    color: #23583e;
    font-size: .76rem;
    font-weight: 650;
}
.staff-photo-status.is-hidden { background: #eeebe6; color: #5d554b; }
.staff-photo-status.is-featured { background: var(--staff-gold-tint); color: var(--staff-gold); }
</style>

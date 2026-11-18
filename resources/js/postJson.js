import { readCsrfToken } from './csrf.js';

const messages = {
    401: 'Sessione scaduta. Accedi di nuovo.',
    403: 'Non hai il permesso per questa operazione.',
    409: 'I dati sono cambiati. Riprova.',
    419: 'Sessione scaduta. Ricarica la pagina.',
    429: 'Troppi tentativi. Riprova tra un minuto.',
};

export async function sendJson(url, values, method = 'POST') {
    const csrf = readCsrfToken();

    if (!csrf) {
        throw new Error(messages[419]);
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': csrf,
        },
        body: JSON.stringify(values),
    }).catch(() => null);

    if (!response) {
        throw new Error('Impossibile contattare il server. Riprova.');
    }

    const data = await response.json().catch(() => null);

    if (response.status === 422) {
        const firstError = Object.values(data?.errors ?? {}).flat()[0];
        throw new Error(firstError ?? 'Controlla i dati inseriti.');
    }

    if (!response.ok || data === null) {
        throw new Error(messages[response.status] ?? 'Impossibile completare la richiesta.');
    }

    return data;
}

export { sendJson as postJson };

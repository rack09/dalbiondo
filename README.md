# 🍕 Dal Biondo

**Menù digitale e area riservata per una pizzeria al trancio di Cinisello Balsamo (MI).**

Il personale aggiorna menù, orari, contatti e fotografie dal pannello. I clienti consultano le informazioni aggiornate sul sito, anche da smartphone.

![PHP 8.4.1+](https://img.shields.io/badge/PHP-8.4.1%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![Vue 3](https://img.shields.io/badge/Vue-3-42B883?style=flat-square&logo=vuedotjs&logoColor=white)
![Bootstrap 5](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)

[Demo live](https://dalbiondo.melvinrosales.com/) · [Area riservata](https://dalbiondo.melvinrosales.com/area-riservata)

> Per accedere all'area riservata della demo, richiedi la password di prova allo sviluppatore

Progetto finale del Master in Full Stack Development di **start2impact**.

## 🌐 Sito pubblico

- **Menù** con pizze al trancio e in teglia, pizze rotonde, sfizi e bibite. Le categorie mostrano i prodotti visibili.
- **Pizze in homepage** con una selezione di massimo sei proposte. La pizza della casa apre la selezione, seguita dalle pizze in evidenza e dalle altre visibili.
- **Orari settimanali** con indicatore di apertura calcolato nel fuso `Europe/Rome`.
- **Contatti** con collegamenti a telefono, WhatsApp quando configurato, Google Maps e Just Eat.
- **Galleria dello staff** con foto, didascalie e una foto in evidenza come prima immagine del carosello.

## 🔐 Area riservata

| Ruolo | Funzioni |
| --- | --- |
| **Titolare** | Gestisce menù, prezzi, orari, contatti e foto. Seleziona le pizze in evidenza e la pizza della casa, controlla la visibilità della galleria e amministra i collaboratori. |
| **Collaboratore** | Aggiorna prodotti, prezzi, visibilità, orari, numeri di contatto e fotografie. Può modificare il proprio profilo e la propria password. |

Il titolare crea gli inviti dal pannello. Ogni link di attivazione vale **48 ore** e si usa una sola volta. Il titolare può sospendere o riattivare un collaboratore e impostargli una nuova password.

## 🧩 Architettura e API

**Laravel 13** gestisce la logica applicativa, la validazione, i permessi e la persistenza con Eloquent. Una vista **Blade** carica l'interfaccia **Vue 3**, sviluppata con **Bootstrap 5** e compilata con **Vite**. Il frontend legge il menù e gli orari tramite API JSON.

| Metodo e percorso | Dati pubblici |
| --- | --- |
| `GET /api/pizzas` | Pizze al trancio e in teglia visibili, con prezzi e stato in evidenza. |
| `GET /api/menu-items` | Pizze rotonde, sfizi e bibite visibili. |
| `GET /api/opening-hours` | Giorni e fasce orarie di apertura. |
| `GET /api/contact` | Indirizzo e numeri di contatto della pizzeria. |
| `GET /api/staff-photos` | Foto visibili della galleria dello staff. |
| `GET /api/staff-gallery` | Visibilità della galleria sul sito pubblico. |

Il pannello usa la sessione di Laravel. Le richieste di modifica passano attraverso la protezione CSRF e i controlli di ruolo previsti per l'operazione. Un esempio è `PATCH /api/staff/pizzas/{pizza}/featured`, riservato al titolare.

| Nel repository | Contenuto |
| --- | --- |
| [resources/js](resources/js) | Componenti Vue del sito pubblico e del pannello. |
| [routes/api.php](routes/api.php) e [routes/web.php](routes/web.php) | API pubbliche, pagine, sessione e operazioni del pannello. |
| [database](database) | Migrazioni, factory e dati iniziali. |
| [tests](tests) | Test di funzionalità e unitari. |

## ⚙️ Avvio in locale

**Requisiti** · PHP **8.4.1 o superiore** per le dipendenze bloccate in `composer.lock`, Composer 2, Node.js **22.12 o superiore**, npm e MySQL con l'estensione `pdo_mysql`. Per usare SQLite in locale o eseguire i test, abilita `pdo_sqlite`.

### 1. Installa le dipendenze

Dalla cartella che contiene `artisan` esegui questi comandi.

```bash
composer install
npm ci
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

### 2. Configura il database

Crea un database MySQL vuoto chiamato `dal_biondo` oppure usa il nome scelto da te. Aggiorna il file `.env` con i valori del tuo ambiente.

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dal_biondo
DB_USERNAME=utente_mysql
DB_PASSWORD="password_mysql"
```

Per una prova con SQLite, mantieni `DB_CONNECTION=sqlite` nel file `.env` appena copiato, imposta lo stesso `APP_URL` e crea il database locale.

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Il repository usa `.env.example` come modello di configurazione. I file dell'ambiente, i database SQLite locali, le dipendenze scaricate e gli asset di build sono esclusi da Git tramite `.gitignore`.

### 3. Inizializza l'applicazione

Al primo avvio genera la chiave e carica i dati iniziali. Crea poi l'account titolare scegliendo nome, e-mail e una password di almeno 12 caratteri. Il link allo storage rende disponibili le foto caricate dal pannello.

```bash
php artisan key:generate
php artisan migrate --seed
php artisan app:create-owner
php artisan storage:link
```

### 4. Avvia Laravel e Vite

Esegui `php artisan serve` in un terminale e `npm run dev` in un secondo terminale, entrambi nella radice del progetto.

[Sito pubblico in locale](http://127.0.0.1:8000/) · [Pannello in locale](http://127.0.0.1:8000/area-riservata)

## 🧪 Test

La suite usa SQLite in memoria. Copre l'accesso e l'uscita dal pannello, gli inviti, i permessi, la visibilità del menù, la selezione delle pizze in evidenza e la validazione degli orari.

```bash
npm run build
php artisan test
```

## 👤 Autore

Sviluppato da **Melvin Rosales**. [Portfolio](https://melvinrosales.com/)

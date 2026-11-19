# Document AI Extractor

Web application per l'estrazione dati strutturati da documenti (PDF, JPG, PNG) tramite OCR + LLM (Cloud API o Ollama locale).

Basata su **Laravel 13** + **Bootstrap FlexStart** + **Vue 3** + **MySQL**.

---

## Funzionalità Principali

| Area | Funzionalità |
|------|-------------|
| **Autenticazione** | Login sicuro, ruoli (user/admin), remember me |
| **Dashboard Utente** | Statistiche elaborazioni, grafici, cache OCR |
| **Configurazione** | API Keys (OpenAI, Google, Anthropic) criptate, endpoint Ollama |
| **Documenti** | Upload PDF/JPG/PNG (max 10MB), download, eliminazione, stato cache OCR |
| **Vettori** | CRUD schemi di estrazione (campi + tipi: string, integer, float, date, boolean) |
| **Elaborazione** | Wizard a 6 step reattivo (Vue 3) con anteprima live output |
| **Risultati** | Storico, visualizzazione JSON formattato, download JSON/CSV/XML |
| **Admin** | Gestione utenti, statistiche globali, tasso errore, modelli più usati |

---

## Architettura

### Database (MySQL)
```sql
users           -- Utenti (role: user/admin, ollama_endpoint)
api_keys        -- API Keys criptate (provider: google/openai/anthropic)
documents       -- File caricati + cache testo OCR (longText)
vectors         -- Schemi estrazione (JSON: {campo: tipo})
results         -- Risultati elaborazione (data JSON, ai_model, status)
```

### Servizi PHP (`app/Services/`)
- **OcrService** → Wrapper per `OcrMicroService.py` (Tesseract + pdftoppm)
- **AiExtractionService** → Chiamate LLM (Ollama `/api/generate`, OpenAI, Google, Anthropic)
- **ResultFormatterService** → Export JSON / CSV (;) / XML

### Python OCR (`OcrMicroService.py`)
- Converte PDF → immagini 300 DPI (`pdftoppm`)
- OCR su ogni pagina (`tesseract -l ita+eng`)
- Restituisce testo su stdout

### Frontend
- **Layout**: Sidebar sinistra (FlexStart), navbar top
- **Vue 3** (global) per pagina `/user/elaborate` (stepper, anteprima live, log)
- **Chart.js** per grafici dashboard
- **Bootstrap 5** + Bootstrap Icons

---

## Requisiti Sistema

| Componente | Versione Minima |
|------------|-----------------|
| PHP | 8.3+ |
| Composer | 2.x |
| MySQL | 8.0+ / MariaDB 10.6+ |
| Node.js | 18+ (per Vite/build assets) |
| Python | 3.8+ |
| Tesseract OCR | 5.x + lingue `ita`, `eng` |
| Poppler (pdftoppm) | 22.x+ |
| Ollama (opzionale) | 0.1+ per modelli locali |

---

## Installazione

### 1. Clona e Dependencies PHP
```bash
git clone <repo>
cd laravel-boilerplate-laravel-13
composer install
```

### 2. Environment
```bash
cp .env.example .env
php artisan key:generate
```

Modifica `.env`:
```env
APP_NAME="Document AI Extractor"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=document_ai
DB_USERNAME=root
DB_PASSWORD=1234

# Per criptazione API Keys (generate con: php artisan db:encryption:key)
DB_ENCRYPTION_KEY=base64:your-32-char-key-here
```

### 3. Database
```bash
# Crea database MySQL
mysql -u root -p1234 -e "CREATE DATABASE document_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrazioni + Seeder
php artisan migrate --seed
```

### 4. Assets Frontend
```bash
npm install
npm run build
# oppure per development:
npm run dev
```

### 5. Python OCR - Dipendenze Sistema

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y tesseract-ocr tesseract-ocr-ita tesseract-ocr-eng poppler-utils python3
```

**macOS (Homebrew):**
```bash
brew install tesseract tesseract-lang poppler python3
```

**Windows:**
- Tesseract: https://github.com/UB-Mannheim/tesseract/wiki (installa + lingue ita/eng)
- Poppler: https://github.com/oschwartz10612/poppler-windows/releases/ (aggiungi `bin` al PATH)
- Python: https://python.org/downloads/

Verifica:
```bash
tesseract --version
pdftoppm -v
python --version
```

### 6. Ollama (Opzionale - per modelli locali)
```bash
# Installa Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Scarica modelli (esempi)
ollama pull llama3
ollama pull mistral
ollama pull gemma2

# Avvia server (default: http://localhost:11434)
ollama serve
```

---

## Avvio

```bash
# Terminale 1: Laravel
php artisan serve

# Terminale 2: Vite (dev mode)
npm run dev
```

Apri: http://localhost:8000 , redirect a `/login`

---

## Utenti Default (da Seeder)

| Email | Password | Ruolo |
|-------|----------|-------|
| admin@admin.com | password | admin |
| user@user.com | password | user |

> **Nota**: Le password sono hashate. Seeder crea utenti con `password` come password.

---

## Guida Utilizzo

### 1. Configurazione (Utente)
Vai su **Configurazione** → Aggiungi **API Key**:
- **OpenAI**: `sk-...` (modelli: gpt-4o, gpt-4o-mini, ecc.)
- **Google**: API Key da AI Studio (modelli: gemini-1.5-pro, gemini-1.5-flash)
- **Anthropic**: `sk-ant-...` (modelli: claude-3-5-sonnet, claude-3-haiku)

Configura **Ollama Endpoint** (default: `http://localhost:11434`), test connessione.

### 2. Documenti
**Documenti** → **Carica Documento**: PDF, JPG, PNG (max 10MB).
- Stato **OCR in cache** = testo già estratto (non rielaborato)
- Stato **OCR da eseguire** = prima elaborazione farà OCR

### 3. Vettori
**Vettori** → **Nuovo Vettore**: definisci schema es.:
```
Nome: Fatture | Formato: JSON
Campi:
  - Fornitore (string)
  - Data (date)
  - Totale (float)
  - IVA (float)
  - Numero Fattura (string)
```

### 4. Elabora
**Elabora** → Wizard 6 step:
1. **Documento** → click su card per selezionare
2. **Vettore** → select + campi visibili
3. **Formato Output** → JSON / CSV / XML (anteprima live a destra)
4. **Modalità** → Cloud API (seleziona API Key) / Ollama Locale
5. **Modello** → dropdown popolato dinamicamente (API Key o `/api/tags` Ollama)
6. **Elabora** → pulsante attivo se tutto completato → log live + stepper progress

### 5. Risultati
**Risultati** → lista con stato, download diretto JSON/CSV/XML, visualizzazione dettaglio con syntax highlighting.

---

## Comandi Utili

```bash
# Pulizia cache
php artisan optimize:clear

# Rigenera chiave criptazione (se cambiata .env)
php artisan db:encryption:key

# Reset database + seed
php artisan migrate:fresh --seed

# Test OCR manuale
python OcrMicroService.py --file storage/app/documents/test.pdf --mime application/pdf --lang ita+eng --dpi 300

# Lista modelli Ollama
curl http://localhost:11434/api/tags
```

---

## 📁 Struttura Progetto (File Chiave)

```
app/
├── Http/Controllers/
│   ├── Auth/LoginController.php           # Login + redirect per ruolo
│   ├── User/
│   │   ├── HomeController.php             # Dashboard stats + grafici
│   │   ├── ConfigController.php           # API Keys + Ollama
│   │   ├── DocumentController.php         # CRUD documenti
│   │   ├── VectorController.php           # CRUD vettori (campi dinamici)
│   │   ├── ElaborateController.php        # Wizard elaborazione (OCR + LLM)
│   │   └── ResultController.php           # Storico + download
│   └── Admin/
│       ├── StatsController.php            # Statistiche globali
│       └── UserController.php             # Gestione utenti + toggle role
├── Models/
│   ├── User.php          # role, ollama_endpoint, relazioni
│   ├── ApiKey.php        # key criptata (cast encrypted)
│   ├── Document.php      # content (cache OCR), hasOcrCache()
│   ├── Vector.php        # data (JSON schema), getSchema()
│   └── Result.php        # data, ai_model, status enum
├── Services/
│   ├── OcrService.php            # Wrapper Python OCR
│   ├── AiExtractionService.php   # Cloud/Ollama LLM + prompt builder
│   └── ResultFormatterService.php # JSON/CSV/XML export
routes/
└── web.php              # Tutte le route (auth, user.*, admin.*)
resources/views/
├── layouts/app.blade.php         # Layout principale + sidebar
├── layouts/lsidebar.blade.php    # Menu laterale (user/admin)
├── user/
│   ├── home.blade.php            # Dashboard + Chart.js
│   ├── config.blade.php          # API Keys modal + Ollama test
│   ├── documents.blade.php       # Upload + tabella
│   ├── vectors.blade.php         # CRUD + modal campi dinamici
│   ├── elaborate.blade.php       # Vue 3 stepper + anteprima live
│   └── results/                  # Lista + show (JSON viewer)
└── admin/stats.blade.php         # Statistiche globali + grafici
OcrMicroService.py                # Python OCR (Tesseract + pdftoppm)
```

---

## Sicurezza

- **API Keys**: Criptate nel DB (`cast encrypted` + `elgibor-solution/laravel-database-encryption`)
- **Password**: Hash Bcrypt (Laravel default)
- **Email**: Criptata nel DB per login
- **CSRF**: Token su tutte le form
- **Middleware**: `auth`, `status` (utente attivo), `role:admin` (area admin)
- **Validazione**: Stretta su upload (mime, size), input sanitizzati

---

## Troubleshooting

| Problema | Soluzione |
|----------|-----------|
| OCR fallisce | Verifica `tesseract --version`, `pdftoppm -v`, lingue `ita.traineddata`/`eng.traineddata` in `tessdata` |
| "API Key non valida" | Controlla chiave nel provider, rigenera se revocata |
| Ollama: connection refused | `ollama serve` attivo? Endpoint corretto in config? Firewall? |
| Modelli non caricati | Cloud: API Key ha permessi? Ollama: `ollama list` mostra modelli? |
| Errore 500 elaborazione | `storage/logs/laravel.log` → dettagli; verifica `maxTextLength` (50k char) |
| Cache OCR non funziona | `document->content` popolato? `hasOcrCache()` restituisce true? |

---

## Note Sviluppo

- **Single-user concurrent**: Nessuna coda/async, elaborazione sincrona
- **OCR Cache**: `documents.content` evita rielaborazione stesso documento
- **Prompt Dinamico**: Costruito da `Vector.data` (chiavi + tipi) → inietta in system prompt
- **Pulizia Risposta LLM**: Regex rimuove ````json ... ```` markdown
- **CSV Separatore**: `;` (standard italiano/Europa)
- **Vue 3**: Caricato globalmente via CDN in `app.blade.php` (niente build step per componenti)

---

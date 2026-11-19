@extends('layouts.app')

@section('style')
<style>
    /* Stepper */
    .stepper {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 2rem;
        padding: 0 1rem;
    }
    .stepper::before {
        content: '';
        position: absolute;
        top: 1.5rem;
        left: 1rem;
        right: 1rem;
        height: 3px;
        background: #e0e0e0;
        z-index: 1;
    }
    .stepper .step {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        text-align: center;
    }
    .stepper .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #9e9e9e;
        transition: all 0.3s;
    }
    .stepper .step.active .step-circle {
        border-color: #1976d2;
        background: #1976d2;
        color: #fff;
    }
    .stepper .step.completed .step-circle {
        border-color: #4caf50;
        background: #4caf50;
        color: #fff;
    }
    .stepper .step.failed .step-circle {
        border-color: #f44336;
        background: #f44336;
        color: #fff;
    }
    .stepper .step-label {
        margin-top: 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #616161;
        white-space: nowrap;
    }
    .stepper .step.active .step-label {
        color: #1976d2;
        font-weight: 600;
    }

    /* Card styling */
    .elaborate-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-radius: 12px;
        transition: box-shadow 0.3s;
    }
    .elaborate-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    .card-header-custom {
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.25rem;
        font-weight: 600;
        color: #333;
        border-radius: 12px 12px 0 0;
    }

    /* Preview area */
    .preview-container {
        background: #fafafa;
        border: 1px dashed #dee2e6;
        border-radius: 8px;
        min-height: 200px;
        max-height: 400px;
        overflow-y: auto;
        font-family: 'Monospace', monospace;
        font-size: 0.85rem;
        line-height: 1.6;
        padding: 1rem;
        white-space: pre-wrap;
    }
    .preview-container pre {
        margin: 0;
    }

    /* Status badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .status-pending { background: #fff3e0; color: #e65100; }
    .status-processing_ocr { background: #e3f2fd; color: #1565c0; }
    .status-processing_llm { background: #e8eaf6; color: #283593; }
    .status-completed { background: #e8f5e9; color: #2e7d32; }
    .status-failed { background: #fbe9e7; color: #c62828; }

    /* Form controls */
    .form-select, .form-control {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.6rem 1rem;
    }
    .form-select:focus, .form-control:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
    }

    /* Loading spinner */
    .spinner-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        gap: 1rem;
    }
    .spinner-text {
        color: #666;
        font-size: 0.95rem;
    }

    /* Progress steps detail */
    .process-log {
        background: #1e1e1e;
        color: #d4d4d4;
        border-radius: 8px;
        padding: 1rem;
        font-family: 'Monospace', monospace;
        font-size: 0.8rem;
        max-height: 300px;
        overflow-y: auto;
    }
    .log-line { margin: 0.25rem 0; }
    .log-info { color: #4fc3f7; }
    .log-success { color: #81c784; }
    .log-error { color: #e57373; }
    .log-warning { color: #ffb74d; }

    @media (max-width: 768px) {
        .stepper .step-label {
            font-size: 0.65rem;
        }
        .stepper .step-circle {
            width: 32px;
            height: 32px;
            font-size: 0.8rem;
        }
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">Elabora Documento</h1>
                        <p class="text-muted mb-0">Seleziona documento, vettore e modello AI per estrarre dati strutturati</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stepper Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card elaborate-card">
                    <div class="stepper" role="navigation" aria-label="Progresso elaborazione">
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1, failed: errorStep === 1 }">
                            <div class="step-circle">1</div>
                            <div class="step-label">Documento</div>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2, failed: errorStep === 2 }">
                            <div class="step-circle">2</div>
                            <div class="step-label">Vettore</div>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3, failed: errorStep === 3 }">
                            <div class="step-circle">3</div>
                            <div class="step-label">Modalità</div>
                        </div>
                        <div class="step" :class="{ active: currentStep === 4, completed: currentStep > 4, failed: errorStep === 4 }">
                            <div class="step-circle">4</div>
                            <div class="step-label">Modello</div>
                        </div>
                        <div class="step" :class="{ active: currentStep === 5, completed: currentStep > 5, failed: errorStep === 5 }">
                            <div class="step-circle">5</div>
                            <div class="step-label">Elabora</div>
                        </div>
                        <div class="step" :class="{ active: currentStep === 6, completed: currentStep > 6 }">
                            <div class="step-circle">6</div>
                            <div class="step-label">Fine</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="row">
            <!-- Colonna Sinistra: Documento -->
            <div class="col-lg-6 mb-4">
                <div class="card elaborate-card h-100">
                    <div class="card-header-custom">
                        <i class="material-icons me-2">description</i>1. Seleziona Documento
                    </div>
                    <div class="card-body">
                        <div v-if="documents.length === 0" class="text-center text-muted py-5">
                            <i class="material-icons" style="font-size: 3rem;">cloud_off</i>
                            <p class="mt-2">Nessun documento caricato</p>
                            <a href="{{ route('user.documents') }}" class="btn btn-primary btn-sm mt-2">Carica documento</a>
                        </div>
                        <div v-else>
                            <div class="row g-3">
                                <div class="col-12 col-md-6" v-for="doc in documents" :key="doc.id">
                                    <div class="card document-card h-100"
                                        :class="{ selected: selectedDocument?.id === doc.id }"
                                        @click="selectDocument(doc)">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="material-icons me-2 text-primary" :class="getFileIcon(doc.mime_type)">
                                                    {{ getFileIconName(doc.mime_type) }}
                                                </i>
                                                <span class="fw-medium text-truncate" :title="doc.original_name">{{ doc.original_name }}</span>
                                            </div>
                                            <div class="small text-muted">
                                                <span class="badge bg-light text-dark me-1">{{ formatSize(doc.size_in_kb) }}</span>
                                                <span class="badge bg-light text-dark">{{ doc.mime_type }}</span>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                <span :class="doc.has_ocr_cache ? 'text-success' : 'text-warning'">
                                                    <i class="material-icons me-1" style="font-size: 1rem; vertical-align: middle;">
                                                        {{ doc.has_ocr_cache ? 'check_circle' : 'schedule' }}
                                                    </i>
                                                    {{ doc.has_ocr_cache ? 'OCR in cache' : 'OCR da eseguire' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonna Destra: Vettore e Output -->
            <div class="col-lg-6 mb-4">
                <div class="card elaborate-card h-100">
                    <div class="card-header-custom">
                        <i class="material-icons me-2">schema</i>2. Seleziona Vettore e Formato Output
                    </div>
                    <div class="card-body">
                        <div v-if="vectors.length === 0" class="text-center text-muted py-5">
                            <i class="material-icons" style="font-size: 3rem;">schema</i>
                            <p class="mt-2">Nessun vettore definito</p>
                            <a href="{{ route('user.vectors') }}" class="btn btn-primary btn-sm mt-2">Crea vettore</a>
                        </div>
                        <div v-else>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Vettore</label>
                                <select class="form-select" v-model="selectedVectorId" @change="onVectorChange">
                                    <option value="">-- Seleziona un vettore --</option>
                                    <option v-for="v in vectors" :value="v.id">{{ v.name }} ({{ v.format.toUpperCase() }})</option>
                                </select>
                            </div>

                            <div class="mb-3" v-if="selectedVector">
                                <label class="form-label fw-medium">Campi del Vettore</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr><th>Campo</th><th>Tipo</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(type, key) in selectedVector.data" :key="key">
                                                <td class="fw-medium">{{ key }}</td>
                                                <td><span class="badge bg-secondary">{{ type }}</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="mb-3">
                                <label class="form-label fw-medium">Formato Output</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="outputFormat" id="fmt-json" value="json" v-model="outputFormat" @change="updatePreview">
                                    <label class="btn btn-outline-primary" for="fmt-json"><i class="material-icons me-1">code</i>JSON</label>
                                    <input type="radio" class="btn-check" name="outputFormat" id="fmt-csv" value="csv" v-model="outputFormat" @change="updatePreview">
                                    <label class="btn btn-outline-primary" for="fmt-csv"><i class="material-icons me-1">table_chart</i>CSV</label>
                                    <input type="radio" class="btn-check" name="outputFormat" id="fmt-xml" value="xml" v-model="outputFormat" @change="updatePreview">
                                    <label class="btn btn-outline-primary" for="fmt-xml"><i class="material-icons me-1">data_object</i>XML</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Modalità, Modello, Preview -->
        <div class="row">
            <!-- Colonna Sinistra: Modalità e Modello -->
            <div class="col-lg-6 mb-4">
                <div class="card elaborate-card h-100">
                    <div class="card-header-custom">
                        <i class="material-icons me-2">tune</i>3-4. Modalità e Modello AI
                    </div>
                    <div class="card-body">
                        <!-- Modalità -->
                        <div class="mb-4">
                            <label class="form-label fw-medium">Modalità Elaborazione</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="mode" id="mode-cloud" value="cloud" v-model="mode" @change="onModeChange">
                                <label class="btn btn-outline-primary" for="mode-cloud">
                                    <i class="material-icons me-1">cloud</i>Cloud API
                                </label>
                                <input type="radio" class="btn-check" name="mode" id="mode-ollama" value="ollama" v-model="mode" @change="onModeChange">
                                <label class="btn btn-outline-success" for="mode-ollama">
                                    <i class="material-icons me-1">memory</i>Ollama Locale
                                </label>
                            </div>
                        </div>

                        <!-- Cloud: API Key + Modello -->
                        <div v-if="mode === 'cloud'" class="mb-3">
                            <label class="form-label fw-medium">API Key</label>
                            <select class="form-select" v-model="selectedApiKeyId" @change="loadCloudModels">
                                <option value="">-- Seleziona API Key --</option>
                                <option v-for="key in apiKeys" :value="key.id">{{ key.name }} ({{ key.provider }})</option>
                            </select>
                            <div class="form-text" v-if="selectedApiKey">
                                Provider: <strong>{{ selectedApiKey.provider }}</strong>
                            </div>
                        </div>

                        <!-- Ollama: Endpoint + Modello -->
                        <div v-if="mode === 'ollama'" class="mb-3">
                            <label class="form-label fw-medium">Endpoint Ollama</label>
                            <div class="input-group">
                                <input type="text" class="form-control" v-model="ollamaEndpoint" placeholder="http://localhost:11434" @blur="loadOllamaModels">
                                <button class="btn btn-outline-secondary" type="button" @click="loadOllamaModels" :disabled="loadingModels">
                                    <i class="material-icons" style="font-size: 1.2rem;">refresh</i>
                                </button>
                            </div>
                            <div class="form-text">Configurabile in <a href="{{ route('user.config') }}">Configurazione</a></div>
                        </div>

                        <!-- Selezione Modello -->
                        <div class="mb-3">
                            <label class="form-label fw-medium">Modello AI</label>
                            <select class="form-select" v-model="selectedModel" :disabled="loadingModels || availableModels.length === 0">
                                <option value="" disabled v-if="loadingModels">Caricamento modelli...</option>
                                <option value="" disabled v-else-if="availableModels.length === 0">Nessun modello disponibile</option>
                                <option v-for="model in availableModels" :value="model">{{ model }}</option>
                            </select>
                            <div class="form-text" v-if="availableModels.length > 0">{{ availableModels.length }} modelli disponibili</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonna Destra: Preview e Pulsante Elabora -->
            <div class="col-lg-6 mb-4">
                <div class="card elaborate-card h-100">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="material-icons me-2">preview</i>5. Anteprima Struttura Output</span>
                        <span class="badge bg-primary">{{ outputFormat.toUpperCase() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="preview-container" v-if="!processing">
                            <pre v-if="previewStructure">{{ previewStructure }}</pre>
                            <div v-else class="text-center text-muted py-5">
                                <i class="material-icons" style="font-size: 3rem;">preview</i>
                                <p class="mt-2">Seleziona vettore e formato per vedere l'anteprima</p>
                            </div>
                        </div>

                        <!-- Processing View -->
                        <div v-if="processing" class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Elaborazione in corso...</span>
                            </div>
                            <h5 class="mb-2">Elaborazione in corso...</h5>
                            <p class="text-muted mb-3">{{ processingStep }}</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" :style="{ width: processingProgress + '%' }"></div>
                            </div>
                        </div>

                        <!-- Log di elaborazione -->
                        <div v-if="showLog && processLog.length > 0" class="mt-3">
                            <label class="form-label fw-medium small">Log elaborazione</label>
                            <div class="process-log">
                                <div class="log-line" v-for="(log, idx) in processLog" :key="idx">
                                    <span class="log-info">[{{ log.time }}]</span>
                                    <span :class="log.type">{{ log.message }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pulsante Elabora / Risultato -->
                        <div v-if="!processing" class="mt-4 d-grid gap-2">
                            <button class="btn btn-primary btn-lg"
                                    @click="startProcessing"
                                    :disabled="!canProcess"
                                    :class="{ 'btn-secondary': !canProcess }">
                                <i class="material-icons me-2">play_arrow</i>Elabora Documento
                            </button>
                            <div class="form-text text-center" v-if="!canProcess">
                                Completa tutti i campi per abilitare l'elaborazione
                            </div>
                        </div>

                        <!-- Success Message -->
                        <div v-if="resultId && !processing" class="alert alert-success mt-3" role="alert">
                            <i class="material-icons me-2">check_circle</i>
                            <strong>Elaborazione completata!</strong>
                            <div class="mt-2 d-flex gap-2">
                                <a :href="'{{ route('user.results.show', ':id') }}'.replace(':id', resultId)" class="btn btn-sm btn-outline-success">
                                    <i class="material-icons me-1">visibility</i>Visualizza
                                </a>
                                <a :href="'{{ route('user.results.download', {id: ':id', format: 'json'}) }}'.replace(':id', resultId)" class="btn btn-sm btn-outline-primary">
                                    <i class="material-icons me-1">download</i>JSON
                                </a>
                                <a :href="'{{ route('user.results.download', {id: ':id', format: 'csv'}) }}'.replace(':id', resultId)" class="btn btn-sm btn-outline-info">
                                    <i class="material-icons me-1">download</i>CSV
                                </a>
                                <a :href="'{{ route('user.results.download', {id: ':id', format: 'xml'}) }}'.replace(':id', resultId)" class="btn btn-sm btn-outline-secondary">
                                    <i class="material-icons me-1">download</i>XML
                                </a>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errorMessage" class="alert alert-danger mt-3" role="alert">
                            <i class="material-icons me-2">error</i>
                            <strong>Errore:</strong> {{ errorMessage }}
                            <button class="btn btn-sm btn-outline-danger ms-2" @click="resetForm">Riprova</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inizializza Vue App per la pagina Elabora
const { createApp, ref, computed, watch, onMounted } = Vue;

createApp({
    setup() {
        // Stato reattivo
        const documents = ref(@json($documents->toArray()));
        const vectors = ref(@json($vectors->toArray()));
        const apiKeys = ref(@json($apiKeys->toArray()));
        const ollamaEndpoint = ref('{{ Auth::user()->ollama_endpoint ?? "http://localhost:11434" }}');

        // Selezioni utente
        const selectedDocument = ref(null);
        const selectedVectorId = ref('');
        const selectedVector = ref(null);
        const outputFormat = ref('json');
        const mode = ref('cloud');
        const selectedApiKeyId = ref('');
        const selectedModel = ref('');
        const availableModels = ref([]);

        // Stato elaborazione
        const processing = ref(false);
        const processingStep = ref('');
        const processingProgress = ref(0);
        const processLog = ref([]);
        const showLog = ref(false);
        const resultId = ref(null);
        const errorMessage = ref(null);
        const errorStep = ref(null);
        const loadingModels = ref(false);

        const currentStep = ref(1);

        const selectedApiKey = computed(() => {
            return apiKeys.value.find(k => k.id == selectedApiKeyId.value) || null;
        });

        const canProcess = computed(() => {
            return selectedDocument.value &&
                   selectedVectorId.value &&
                   selectedModel.value &&
                   ((mode.value === 'cloud' && selectedApiKeyId.value) || mode.value === 'ollama');
        });

        // Helper per icone file
        const getFileIcon = (mime) => {
            if (mime === 'application/pdf') return 'text-danger';
            if (mime.startsWith('image/')) return 'text-success';
            return '';
        };

        const getFileIconName = (mime) => {
            if (mime === 'application/pdf') return 'picture_as_pdf';
            if (mime.startsWith('image/')) return 'image';
            return 'insert_drive_file';
        };

        const formatSize = (kb) => {
            if (kb >= 1024) return (kb / 1024).toFixed(1) + ' MB';
            return kb + ' KB';
        };

        // Anteprima struttura
        const previewStructure = computed(() => {
            if (!selectedVector.value) return null;

            const schema = selectedVector.value.data;
            const sample = {};
            for (const [key, type] of Object.entries(schema)) {
                sample[key] = getSampleValue(type);
            }

            try {
                switch (outputFormat.value) {
                    case 'json':
                        return JSON.stringify(sample, null, 2);
                    case 'csv':
                        const headers = Object.keys(sample).join(';');
                        const values = Object.values(sample).map(v =>
                            typeof v === 'string' && (v.includes(';') || v.includes('"') || v.includes('\n'))
                                ? '"' + v.replace(/"/g, '""') + '"'
                                : v
                        ).join(';');
                        return headers + '\n' + values;
                    case 'xml':
                        let xml = '<?xml version="1.0" encoding="UTF-8"?>\n<result>\n';
                        for (const [key, value] of Object.entries(sample)) {
                            const safeKey = key.replace(/[^a-zA-Z0-9_-]/g, '_');
                            xml += `  <${safeKey}>${value === null ? '' : value}</${safeKey}>\n`;
                        }
                        xml += '</result>';
                        return xml;
                }
            } catch (e) {
                return 'Errore generazione anteprima';
            }
            return null;
        });

        const getSampleValue = (type) => {
            switch (type) {
                case 'string': return 'esempio_testo';
                case 'integer': return 123;
                case 'float': return 123.45;
                case 'date': return '2025-01-15';
                case 'boolean': return true;
                default: return 'valore';
            }
        };

        // Selezione documento
        const selectDocument = (doc) => {
            selectedDocument.value = { ...doc, has_ocr_cache: !!doc.content && doc.content.trim() !== '' };
            currentStep.value = Math.max(currentStep.value, 2);
        };

        // Cambio vettore
        const onVectorChange = () => {
            const vec = vectors.value.find(v => v.id == selectedVectorId.value);
            selectedVector.value = vec || null;
            currentStep.value = Math.max(currentStep.value, 3);
        };

        // Cambio modalità
        const onModeChange = async () => {
            selectedApiKeyId.value = '';
            selectedModel.value = '';
            availableModels.value = [];
            currentStep.value = Math.max(currentStep.value, 3);

            if (mode.value === 'ollama') {
                await loadOllamaModels();
            }
        };

        // Carica modelli Ollama
        const loadOllamaModels = async () => {
            if (!ollamaEndpoint.value) return;

            loadingModels.value = true;
            addLog('info', `Connessione a Ollama: ${ollamaEndpoint.value}`);

            try {
                const response = await axios.post('{{ route('elaborate.ollama-models') }}', {
                    endpoint: ollamaEndpoint.value
                });
                availableModels.value = response.data.models || [];
                addLog('success', `${availableModels.value.length} modelli Ollama trovati`);
            } catch (e) {
                addLog('error', 'Errore connessione Ollama: ' + (e.response?.data?.error || e.message));
                availableModels.value = [];
            } finally {
                loadingModels.value = false;
            }
        };

        // Carica modelli Cloud
        const loadCloudModels = async () => {
            if (!selectedApiKeyId.value) {
                availableModels.value = [];
                return;
            }

            loadingModels.value = true;
            const key = apiKeys.value.find(k => k.id == selectedApiKeyId.value);
            addLog('info', `Recupero modelli per ${key?.provider}...`);

            try {
                const response = await axios.post('{{ route('elaborate.cloud-models') }}', {
                    api_key_id: selectedApiKeyId.value
                });
                availableModels.value = response.data.models || [];
                addLog('success', `${availableModels.value.length} modelli ${key?.provider} trovati`);
            } catch (e) {
                addLog('error', 'Errore recupero modelli: ' + (e.response?.data?.error || e.message));
                availableModels.value = [];
            } finally {
                loadingModels.value = false;
            }
        };

        // Avvia elaborazione
        const startProcessing = async () => {
            if (!canProcess.value) return;

            processing.value = true;
            showLog.value = true;
            processLog.value = [];
            resultId.value = null;
            errorMessage.value = null;
            errorStep.value = null;
            currentStep.value = 5;
            processingProgress.value = 10;

            addLog('info', 'Avvio elaborazione...');

            try {
                // Step 1: OCR
                processingStep.value = 'Estrazione testo OCR...';
                processingProgress.value = 25;
                addLog('info', 'Fase 1: OCR in corso...');

                // Step 2: LLM
                processingStep.value = 'Estrazione dati con AI...';
                processingProgress.value = 50;
                addLog('info', 'Fase 2: Chiamata modello AI...');

                const response = await axios.post('{{ route('elaborate.start') }}', {
                    document_id: selectedDocument.value.id,
                    vector_id: selectedVectorId.value,
                    mode: mode.value,
                    model: selectedModel.value,
                    api_key_id: selectedApiKeyId.value,
                    output_format: outputFormat.value,
                });

                processingProgress.value = 75;
                addLog('info', 'Fase 3: Salvataggio risultati...');

                if (response.data.success) {
                    resultId.value = response.data.result_id;
                    currentStep.value = 6;
                    processingProgress.value = 100;
                    processingStep.value = 'Completato!';
                    addLog('success', 'Elaborazione completata con successo');
                } else {
                    throw new Error(response.data.error || 'Errore sconosciuto');
                }
            } catch (e) {
                errorMessage.value = e.response?.data?.error || e.message;
                errorStep.value = currentStep.value;
                addLog('error', 'Errore: ' + errorMessage.value);
                currentStep.value = Math.max(currentStep.value, 5);
            } finally {
                processing.value = false;
            }
        };

        const addLog = (type, message) => {
            const now = new Date();
            const time = now.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            processLog.value.push({ time, type, message });
        };

        const resetForm = () => {
            processing.value = false;
            showLog.value = false;
            processLog.value = [];
            resultId.value = null;
            errorMessage.value = null;
            errorStep.value = null;
            currentStep.value = selectedDocument.value ? 2 : 1;
        };

        const updatePreview = () => {
            // trigger reactivity
        };

        // Watch per aggiornare step corrente
        watch(selectedDocument, (val) => {
            if (val) currentStep.value = Math.max(currentStep.value, 2);
        });
        watch(selectedVectorId, (val) => {
            if (val) currentStep.value = Math.max(currentStep.value, 3);
        });
        watch(mode, (val) => {
            currentStep.value = Math.max(currentStep.value, 3);
        });
        watch(selectedModel, (val) => {
            if (val) currentStep.value = Math.max(currentStep.value, 5);
        });

        // Carica modelli Ollama all'avvio se modalità predefinita
        onMounted(async () => {
            if (mode.value === 'ollama' && ollamaEndpoint.value) {
                await loadOllamaModels();
            }
        });

        return {
            documents, vectors, apiKeys, ollamaEndpoint,
            selectedDocument, selectedVectorId, selectedVector, outputFormat,
            mode, selectedApiKeyId, selectedModel, availableModels, selectedApiKey,
            processing, processingStep, processingProgress, processLog, showLog,
            resultId, errorMessage, errorStep, currentStep, canProcess, loadingModels,
            getFileIcon, getFileIconName, formatSize, previewStructure,
            selectDocument, onVectorChange, onModeChange, loadOllamaModels,
            loadCloudModels, startProcessing, resetForm, addLog, updatePreview,
        };
    }
}).mount('#elaborate-app');
</script>
@endsection
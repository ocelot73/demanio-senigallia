# Manuale d'Uso di Qwen Coder per il Progetto Gestione Demanio

## Introduzione

Questo manuale fornisce istruzioni passo-passo su come utilizzare **Qwen Coder** (o qualsiasi AI assistant per il coding) per lavorare efficacemente con il progetto **Gestione Demanio - Comune di Senigallia**.

Il progetto è un'applicazione web PHP per la gestione delle concessioni demaniali marittime, con architettura MVC (Model-View-Controller).

---

## Indice

1. [Struttura del Progetto](#1-struttura-del-progetto)
2. [Come Formulare le Richieste a Qwen Coder](#2-come-formulare-le-richieste-a-qwen-coder)
3. [Esempi Pratici di Utilizzo](#3-esempi-pratici-di-utilizzo)
4. [Best Practices](#4-best-practices)
5. [Risoluzione dei Problemi Comuni](#5-risoluzione-dei-problemi-comuni)

---

## 1. Struttura del Progetto

Prima di iniziare, è fondamentale comprendere l'organizzazione dei file:

```
/workspace/
├── config/                    # Configurazione
│   ├── config.php.example     # Template configurazione DB
│   └── pages.php              # Definizione pagine e routing
├── public/                    # Punto di accesso pubblico
│   ├── index.php              # Router principale
│   ├── login_form.php         # Form login
│   ├── login_logic.php        # Logica autenticazione
│   └── assets/                # CSS, JS, immagini
├── src/                       # Logica applicativa
│   ├── controllers/           # Controller (gestione dati)
│   ├── models/                # Modelli (query database)
│   └── lib/                   # Librerie utility
├── templates/                 # Viste HTML/PHP
│   ├── layouts/               # Layout principali
│   ├── partials/              # Componenti riutilizzabili
│   └── *.php                  # Template delle pagine
└── readme.md                  # Documentazione generale
```

### Flusso di una Richiesta

1. **`public/index.php`** riceve la richiesta HTTP
2. Carica la configurazione da **`config/config.php`**
3. Determina la pagina da `config/pages.php`
4. Esegue il controller corrispondente (es: `concessioni_controller.php`)
5. Il controller chiama i modelli (es: `concessione.php`) per i dati
6. Renderizza il template (es: `templates/concessioni.php`)

---

## 2. Come Formulare le Richieste a Qwen Coder

### Regole Generali

✅ **SI**: Fornire contesto specifico sul file e sulla funzionalità  
✅ **SI**: Citare nomi esatti di file, funzioni e variabili  
✅ **SI**: Specificare se si vuole modificare, aggiungere o correggere  
❌ **NO**: Richieste generiche senza riferimento al progetto  

### Template per le Richieste

#### Per Aggiungere una Nuova Funzionalità

```
Vorrei aggiungere [FUNZIONALITÀ] al progetto.

Contesto:
- File da modificare: [PERCORSO_FILE]
- Funzione esistente simile: [NOME_FUNZIONE] (opzionale)
- Comportamento desiderato: [DESCRIZIONE]

Requisiti:
1. [REQUISITO_1]
2. [REQUISITO_2]

Mostrami il codice completo da inserire.
```

#### Per Correggere un Bug

```
Ho riscontrato un bug in [FILE/FUNZIONE].

Problema: [DESCRIZIONE_DEL_PROBLEMA]
Errore mostrato: [MESSAGGIO_ERRORE]
Comportamento atteso: [COSA_DOVREBBE_FARE]

File coinvolti:
- [PERCORSO_FILE_1]
- [PERCORSO_FILE_2]

Analizza il codice e proponi una correzione.
```

#### Per Modificare Funzionalità Esistente

```
Vorrei modificare la funzione [NOME_FUNZIONE] in [PERCORSO_FILE].

Attualmente fa: [COMPORTAMENTO_ATTUALE]
Dovrebbe fare: [NUOVO_COMPORTAMENTO]

Motivazione: [PERCHÉ_CAMBIARE]

Fornisci la versione aggiornata della funzione.
```

---

## 3. Esempi Pratici di Utilizzo

### Esempio 1: Aggiungere un Nuovo Campo alla Vista Concessioni

**Scenario**: Vuoi aggiungere una colonna "Email" alla tabella delle concessioni.

**Richiesta a Qwen Coder**:

```
Vorrei aggiungere la colonna "email_concessionario" alla vista delle concessioni.

Contesto:
- La vista principale è in templates/concessioni.php
- Il controller è src/controllers/concessioni_controller.php
- Il modello per i dati è src/models/concessione.php
- La vista database è concessioni_unione_v

Requisiti:
1. La colonna deve essere visibile nella tabella
2. Deve essere ordinabile cliccando sull'intestazione
3. Deve essere filtrabile come le altre colonne testo

Mostrami le modifiche necessarie in tutti i file coinvolti.
```

**Risposta attesa di Qwen Coder**:

Il modello ti fornirà:
1. Verifica che il campo esista nella vista database
2. Eventuali modifiche al controller (di solito nessuna, gestisce già tutte le colonne dinamicamente)
3. Nessuna modifica al modello (già dinamico)
4. Istruzioni per verificare il rendering nel template

---

### Esempio 2: Creare un Nuovo Report

**Scenario**: Vuoi creare una nuova pagina di report per le concessioni scadute.

**Richiesta a Qwen Coder**:

```
Vorrei creare una nuova pagina di report chiamata "Concessioni Scadute".

Contesto:
- Seguire la struttura esistente del progetto (MVC)
- File di configurazione: config/pages.php
- La query deve selezionare dalla vista concessioni_unione_v
- Filtro: data_scadenza < CURRENT_DATE

File da creare/modificare:
1. config/pages.php - aggiungere la definizione della pagina
2. src/controllers/scadute_controller.php - nuovo controller
3. templates/scadute.php - nuovo template (può estendere concessioni.php)

Mostrami il codice completo per ogni file.
```

**Risposta attesa di Qwen Coder**:

```php
// 1. config/pages.php - Aggiungi questo array a $PAGES:
'scadenze' => [
    'label' => 'Concessioni Scadute',
    'view' => 'scadute',
    'controller' => 'scadute_controller.php',
    'table' => 'concessioni_unione_v',
    'title' => 'Report Concessioni Scadute',
    'icon' => 'fas fa-exclamation-triangle',
    'default_filters' => ['data_scadenza' => '<CURRENT_DATE']
],

// 2. src/controllers/scadute_controller.php:
<?php
require_once __DIR__ . '/../models/concessione.php';

function scadute_controller_data(&$data, &$pageConfig) {
    $conn = get_db_connection();
    
    // Imposta filtro default per scadenze passate
    $_SESSION['column_filters']['data_scadenza'] = '<CURRENT_DATE>';
    
    // Riutilizza la logica esistente dal controller concessioni
    require_once __DIR__ . '/concessioni_controller.php';
    concessioni_controller_data($data, $pageConfig);
}

// 3. templates/scadute.php:
<?php
// Può includere il template concessioni.php con personalizzazioni
$page_title = 'Concessioni Scadute';
include __DIR__ . '/concessioni.php';
?>
```

---

### Esempio 3: Modificare il Calcolo di un Canone

**Scenario**: Vuoi cambiare la formula di calcolo del canone demaniale.

**Richiesta a Qwen Coder**:

```
Vorrei modificare il calcolo del canone demaniale nella funzione di calcolo.

Contesto:
- Attualmente il calcolo è gestito nel database (vista calcolo_canoni_v)
- Vorrei aggiungere una logica PHP nel controller per un calcolo personalizzato
- File di riferimento: src/controllers/concessioni_controller.php

Nuova formula:
canone = superficie * tariffa_base * coefficiente_mareggiata
dove coefficiente_mareggiata = 1.5 se zona = 'A', altrimenti 1.0

Mostrami:
1. Dove inserire la logica nel controller
2. Come modificare il modello se necessario
3. Come passare il dato calcolato al template
```

**Risposta attesa di Qwen Coder**:

```php
// Nel controller, dopo aver recuperato i record:
foreach ($data['records'] as &$record) {
    $superficie = floatval($record['superficie'] ?? 0);
    $tariffa_base = floatval($record['tariffa_base'] ?? 0);
    $zona = $record['zona'] ?? 'B';
    
    $coefficiente = ($zona === 'A') ? 1.5 : 1.0;
    $record['canone_calcolato'] = $superficie * $tariffa_base * $coefficiente;
}

// Aggiungere 'canone_calcolato' alle colonne visibili
$data['columns'][] = 'canone_calcolato';
```

---

### Esempio 4: Correggere un Errore di Connessione Database

**Scenario**: L'applicazione mostra errore di connessione al database.

**Richiesta a Qwen Coder**:

```
Errore: "Connessione al database fallita. Controllare le credenziali"

Contesto:
- Ho creato config/config.php da config.php.example
- Il database PostgreSQL è attivo su localhost:5432
- Nome DB: demanio, utente: demanio_user

File da verificare:
- config/config.php
- src/lib/database.php

Mostrami come debuggare il problema e correggere la configurazione.
```

**Risposta attesa di Qwen Coder**:

```php
// 1. Verifica config/config.php:
<?php
define('DB_HOST', 'localhost');      // ✅ Corretto
define('DB_PORT', '5432');           // ✅ Corretto
define('DB_NAME', 'demanio');        // ⚠️ Da verificare
define('DB_USER', 'demanio_user');   // ⚠️ Da verificare
define('DB_PASSWORD', 'tua_password'); // ⚠️ INSERIRE PASSWORD REALE
define('DB_SCHEMA', 'demanio');      // ✅ Corretto

// 2. Test di connessione rapido:
$conn = pg_connect("host=localhost port=5432 dbname=demanio user=demanio_user password=tua_password");
if (!$conn) {
    echo "Errore: " . pg_last_error();
} else {
    echo "Connessione riuscita!";
    pg_close($conn);
}

// 3. Verifica in database.php che usi DB_PASSWORD (non DB_PASS):
// Righe 32-39 di src/lib/database.php devono usare DB_PASSWORD
```

---

### Esempio 5: Aggiungere un Filtro Personalizzato

**Scenario**: Vuoi aggiungere un filtro per cercare concessioni per intervallo di date.

**Richiesta a Qwen Coder**:

```
Vorrei aggiungere un filtro per intervallo di date (data_inizio - data_fine) 
nella pagina delle concessioni.

Contesto:
- I filtri attuali sono gestiti in src/models/concessione.php
- La funzione build_filter_where_clause gestisce i filtri singoli
- L'interfaccia è in templates/partials/sidebar.php

Requisiti:
1. Due campi input: data_da e data_a
2. Filtro SQL: data_scadenza BETWEEN data_da AND data_a
3. Mantenere compatibilità con filtri esistenti

Mostrami le modifiche a:
1. Il modello per gestire il filtro range
2. Il template per l'interfaccia UI
```

**Risposta attesa di Qwen Coder**:

```php
// 1. Nel modello (src/models/concessione.php), aggiungi a build_filter_where_clause:
// Dopo il foreach esistente, aggiungi gestione range:
if (!empty($_SESSION['date_range']['data_da']) && !empty($_SESSION['date_range']['data_a'])) {
    $where[] = "$c BETWEEN $" . $i . " AND $" . ($i + 1);
    $params[] = $_SESSION['date_range']['data_da'];
    $params[] = $_SESSION['date_range']['data_a'];
    $i += 2;
}

// 2. Nel template (templates/partials/sidebar.php):
<div class="filter-group">
    <label>Data Scadenza (range)</label>
    <input type="date" name="data_da" value="<?php echo $_SESSION['date_range']['data_da'] ?? ''; ?>">
    <span>al</span>
    <input type="date" name="data_a" value="<?php echo $_SESSION['date_range']['data_a'] ?? ''; ?>">
</div>
```

---

## 4. Best Practices

### Quando Lavori con Qwen Coder

1. **Sii Specifico sul Contesto**
   - ✅ "Nel file `src/lib/database.php`, funzione `get_db_connection()`..."
   - ❌ "Come mi connetto al database?"

2. **Fornisci Esempi di Codice Esistente**
   - Incolla la funzione simile già presente
   - Chiedi di seguirla come pattern

3. **Chiedi Spiegazioni**
   - "Spiegami perché usi `pg_escape_identifier()` qui"
   - "Qual è la differenza tra `pg_query()` e `pg_query_params()`?"

4. **Verifica Sempre il Codice Generato**
   - Controlla che i nomi delle funzioni esistano
   - Verifica i percorsi dei file
   - Testa in ambiente di sviluppo

5. **Mantieni la Coerenza**
   - Segui lo stile di codice esistente
   - Usa le stesse convenzioni di naming
   - Rispetta la struttura MVC del progetto

### Pattern Ricorrenti nel Progetto

| Pattern | Dove si Trova | Come Usarlo |
|---------|--------------|-------------|
| **Singleton DB** | `src/lib/database.php` | Usa sempre `get_db_connection()` |
| **Query Parametrizzate** | `src/lib/database.php` | Usa `db_query($conn, $sql, $params)` |
| **Controller Pattern** | `src/controllers/*` | Funzione `{nome}_data(&$data, &$pageConfig)` |
| **Template Rendering** | `src/lib/template.php` | Usa `render_page()` |
| **Filtri Dinamici** | `src/models/concessione.php` | Sfrutta `build_filter_where_clause()` |

---

## 5. Risoluzione dei Problemi Comuni

### Problema 1: Errore "Undefined constant DB_PASSWORD"

**Causa**: Il file `config/config.php` non esiste o usa `DB_PASS` invece di `DB_PASSWORD`.

**Soluzione con Qwen Coder**:

```
Ho l'errore "Undefined constant DB_PASSWORD" in src/lib/database.php riga 38.

Contesto:
- Ho copiato config/config.php.example in config/config.php
- database.php usa DB_PASSWORD ma l'esempio usa DB_PASS

Come correggo? Mostrami il config.php corretto.
```

---

### Problema 2: La Paginazione Non Funziona

**Causa**: La costante `RECORDS_PER_PAGE` non è definita o il controller non passa i dati corretti.

**Soluzione con Qwen Coder**:

```
La paginazione nella pagina concessioni non funziona: mostra sempre tutti i record.

File coinvolti:
- src/controllers/concessioni_controller.php
- src/models/concessione.php
- config/config.php.example (dove è definito RECORDS_PER_PAGE = 35)

Analizza il flusso e dimmi dove è il problema.
```

---

### Problema 3: AJAX Restituisce Errore 500

**Causa**: La funzione `handle_ajax_request()` in `request_handler.php` ha un errore.

**Soluzione con Qwen Coder**:

```
Quando faccio una richiesta AJAX per l'ordinamento colonne, ricevo errore 500.

Contesto:
- La gestione AJAX è in src/lib/request_handler.php
- La funzione è handle_ajax_request($FIELD_HELP)
- $FIELD_HELP viene da config/config.php

Mostrami come debuggare e correggere la funzione.
```

---

### Problema 4: Importazione ZIP Fallisce

**Causa**: Permessi insufficienti o percorso temporaneo errato.

**Soluzione con Qwen Coder**:

```
L'importazione del file ZIP in public/index.php fallisce nella fase di estrazione.

Codice coinvolto (righe 36-93 di index.php):
- Crea directory in sys_get_temp_dir()
- Estrae con ZipArchive
- Sposta file con sudo

Errori possibili:
1. Permessi directory temporanea
2. ZipArchive non disponibile
3. Comando sudo non configurato

Come debuggo e risolvo?
```

---

## Appendice A: Comandi Utili per Qwen Coder

### Analisi del Codice

```
Analizza la funzione [NOME_FUNZIONE] in [PERCORSO_FILE] e spiegami:
1. Cosa fa
2. Quali parametri riceve
3. Cosa restituisce
4. Eventuali problemi o miglioramenti
```

### Refactoring

```
Refattorizza questo codice per:
1. Migliorare la leggibilità
2. Ridurre la duplicazione
3. Seguire gli standard PSR-12

[CODICE_DA_REFATTORIZZARE]
```

### Testing

```
Scrivi un test per la funzione [NOME_FUNZIONE] che verifichi:
1. Input validi
2. Input invalidi
3. Casi limite

Usa PHPUnit come framework.
```

### Documentazione

```
Genera documentazione PHPDoc per questa funzione:

[CODICE_FUNZIONE]

Includi:
- Descrizione
- @param per ogni parametro
- @return
- @throws per le eccezioni
```

---

## Appendice B: Checklist per Nuove Funzionalità

Quando chiedi a Qwen Coder di implementare una nuova feature, verifica che abbia considerato:

- [ ] **Configurazione**: Aggiornare `config/pages.php` se serve una nuova pagina
- [ ] **Controller**: Creare/modificare il controller in `src/controllers/`
- [ ] **Modello**: Aggiungere query in `src/models/` se serve accesso DB
- [ ] **Vista**: Creare template in `templates/` o modificare esistente
- [ ] **Assets**: Aggiungere CSS/JS in `public/assets/` se necessario
- [ ] **Sicurezza**: Usare `db_query()` con parametri, escape output
- [ ] **Error Handling**: Gestire eccezioni e mostrare messaggi utente
- [ ] **Logging**: Usare `error_log()` per errori critici

---

## Conclusione

Questo manuale ti guida nell'utilizzo efficace di Qwen Coder per il progetto Gestione Demanio. Ricorda:

1. **Contesto è tutto**: Più dettagli fornisci, migliore sarà il codice generato
2. **Verifica sempre**: Il codice AI va testato e validato
3. **Impara dal processo**: Usa le spiegazioni di Qwen per migliorare le tue competenze
4. **Mantieni la coerenza**: Segui i pattern esistenti nel progetto

Per ulteriori domande specifiche sul progetto, consulta il `readme.md` nella root o analizza i file esistenti per comprendere i pattern utilizzati.

---

*Documento creato per il progetto Gestione Demanio - Comune di Senigallia*  
*Versione 1.0 - 2025*

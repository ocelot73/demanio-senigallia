<?php
// /config/config.php

// Abilita la visualizzazione degli errori in fase di sviluppo. Rimuovere in produzione.
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * ==========================================================
 * Configurazione Database
 * ==========================================================
 * NOTA: Per sicurezza, questo file NON dovrebbe essere tracciato da Git.
 */
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'area11');
define('DB_USER', 'demanio');
define('DB_PASS', 'demanio60019!');
define('DB_SCHEMA', 'demanio');

/**
 * ==========================================================
 * Configurazione Applicazione
 * ==========================================================
 */
define('APP_NAME', 'Gestione Demanio');
define('RECORDS_PER_PAGE', 35);

date_default_timezone_set('Europe/Rome');

/**
 * ==========================================================
 * Configurazione HELP (da file originale)
 * ==========================================================
 */
$FIELD_HELP = [
    'idf24' => [
        'label'   => 'idf24',
        'title'   => 'ID Concessione',
        'content' => '<p>Codice univoco della pratica (modificabile con cautela).</p>',
        'hint'    => 'Es. 2025000012',
        'examples'=> ['2023000456','2024000100'],
    ],
    'pec_inviata' => [
        'label'   => 'PEC Inviata',
        'title'   => 'PEC Inviata',
        'content' => '<p>Flag booleano che indica se la PEC è stata inviata.</p><p><strong>Booleano:</strong> true/false, t/f, 1/0.</p>',
        'hint'    => 'true | false',
    ],
    // ... INCOLLA QUI IL RESTO DELL'ARRAY $FIELD_HELP DAL TUO FILE ORIGINALE ...
];

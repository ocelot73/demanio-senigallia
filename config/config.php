<?php
// config/config.php

/**
 * ==========================================================
 * Configurazione Database e Variabili Globali
 * ==========================================================
 * NOTA: Per sicurezza, questo file non dovrebbe essere
 * tracciato da Git. Usa un file config.php.example
 * come template.
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
define('APP_URL', 'http://localhost/demanio-senigallia/public'); // Modifica con il tuo URL
define('RECORDS_PER_PAGE', 35);

date_default_timezone_set('Europe/Rome');

/**
 * ==========================================================
 * Configurazione HELP
 * ==========================================================
 */
$FIELD_HELP = [
    // ... Incolla qui l'intero array $FIELD_HELP dal tuo file index.php originale ...
    'idf24' => [
        'label'   => 'idf24',
        'title'   => 'ID Concessione',
        'content' => '<p>Codice univoco della pratica (modificabile con cautela).</p>',
        'hint'    => 'Es. 2025000012',
        'examples'=> ['2023000456','2024000100'],
    ],
    // etc...
];

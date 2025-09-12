<?php
// /config/pages.php

/**
 * ==========================================================
 * Definizione Pagine e Viste dell'Applicazione
 * ==========================================================
 * Questo file definisce la struttura di navigazione e le proprietà di ogni pagina.
 * Le configurazioni sono state allineate a quelle presenti in `index.php` per garantire
 * la coerenza funzionale, specialmente per la formattazione dei canoni e l'evidenziazione
 * delle colonne. È stata rimossa la voce 'scadenzario' non presente nell'originale.
 */

$PAGES = [
    'concessioni' => [
        'label' => 'Concessioni',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'concessioni_unione_v',
        'highlight_columns' => ['verifica', 'pec inviata'],
        'format_canone_2025' => true,
        'title' => 'Tabella concessioni demaniali marittime - SID',
        'icon' => 'fas fa-umbrella-beach'
    ],
    'stampa_unione' => [
        'label' => 'Stampa Unione',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'stampa_unione_v',
        'highlight_columns' => [],
        'format_canone_2025' => true, // Mantenuto per coerenza, anche se la colonna potrebbe non esistere
        'title' => 'Tabella per stampa unione Microsoft Word',
        'icon' => 'fas fa-file-word'
    ],
    'protocollo_batch' => [
        'label' => 'Protocollo Batch',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'invio_massivo_v',
        'highlight_columns' => [],
        'format_canone_2025' => true,
        'title' => 'Elenco per protocollo batch da file tramite JEnte',
        'icon' => 'fas fa-paper-plane'
    ],
    'protocolli_canoni' => [
        'label' => 'Elenco Protocolli',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'protocolli_canoni_inviati_v',
        'highlight_columns' => [],
        'format_canone_2025' => true,
        'title' => 'Tabella con riepilogo n. protocolli inviati',
        'icon' => 'fas fa-list'
    ],
    'calcolo_canoni' => [
        'label' => 'Calcolo Canoni',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'calcolo_canoni_v',
        'highlight_columns' => ['verifica', 'pec_inviata'],
        'format_canone_2025' => false,
        'title' => 'Calcolo Canoni Demaniali Marittimi',
        'icon' => 'fas fa-calculator'
    ],
    'report_protocolli_regione' => [
        'label' => 'Report Regione',
        'view' => 'concessioni',
        'controller' => 'concessioni_controller.php',
        'table' => 'report_canoni_imposta_reg_v',
        'highlight_columns' => [],
        'format_canone_2025' => false,
        'title' => 'Report Protocolli Demanio Regione',
        'icon' => 'fas fa-chart-line'
    ],
    'modifica_concessioni' => [
        'label' => 'Modifica Concessioni',
        'url'   => 'https://sit.comune.senigallia.an.it/demanio/admin.php?pgsql=localhost&username=demanio&db=area11&ns=demanio&select=concessioni&columns%5B0%5D%5Bfun%5D=&columns%5B0%5D%5Bcol%5D=&where%5B0%5D%5Bcol%5D=cessata&where%5B0%5D%5Bop%5D=IS+NULL&where%5B0%5D%5Bval%5D=&where%5B01%5D%5Bcol%5D=&where%5B01%5D%5Bop%5D=%3D&where%5B01%5D%5Bval%5D=&order%5B0%5D=denominazione+ditta+concessionario&order%5B01%5D=&limit=300&text_length=300',
        'title' => 'Modifica la tabella delle concessioni in una nuova scheda',
        'icon'  => 'fas fa-edit'
    ],
    'importa' => [
        'label' => 'Importa Dati SID',
        'view' => 'importa',
        'controller' => 'importa_controller.php',
        'title' => 'Importazione Dati Demaniali (SID)',
        'icon' => 'fas fa-upload'
    ]
];

/**
 * ==========================================================
 * Definizione Gruppi di Menu
 * ==========================================================
 * Raggruppa le pagine correlate sotto una singola voce di menu espandibile.
 */
$MENU_GROUPS = [
    'Canoni' => [
        'icon' => 'fas fa-file-invoice-dollar',
        'pages' => [
            'stampa_unione',
            'protocollo_batch',
            'protocolli_canoni',
            'calcolo_canoni',
            'report_protocolli_regione'
        ]
    ]
];

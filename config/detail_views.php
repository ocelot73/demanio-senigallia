<?php
// /config/detail_views.php

/**
 * ==========================================================
 * Definizione Viste Dettaglio per Modale SID
 * ==========================================================
 * Questo array definisce quali viste materializzate interrogare
 * quando un utente richiede i dettagli di una concessione (icona lente).
 * La struttura ricalca quella presente nel file index.php originale.
 */
$DETAIL_VIEWS = [
    'sintesi_atti' => ['label' => 'SINTESI ATTI', 'view' => 'sintesi_atti_mv', 'icon' => 'fas fa-file-invoice'],
    'atti_amministrativi' => ['label' => 'ATTI AMMINISTRATIVI', 'view' => 'mv_atti_amministrativi', 'icon' => 'fas fa-landmark'],
    'soggetti' => ['label' => 'SOGGETTI', 'view' => 'mv_rel_atti_soggetti', 'icon' => 'fas fa-users'],
    'zd_oggetti' => ['label' => 'ZONE D.M. E OGGETTI', 'view' => 'mv_zd_oggetti_superficie', 'icon' => 'fas fa-map-marked-alt'],
    'oggetti_punti' => ['label' => 'OGGETTI', 'view' => 'mv_oggetti', 'icon' => 'fas fa-map-marker-alt'],
    'integrazioni' => ['label' => 'INTEGRAZIONI', 'view' => 'mv_integrazioni', 'icon' => 'fas fa-puzzle-piece'],
    'rate_canone' => ['label' => 'RATE CANONE', 'view' => 'mv_rate_canone', 'icon' => 'fas fa-receipt'],
    'amministrazioni' => ['label' => 'AMMINISTRAZIONI', 'view' => 'mv_amministrazioni', 'icon' => 'fas fa-building-columns'],
    'stagionalita' => ['label' => 'STAGIONALITA\'', 'view' => 'mv_stagionalita', 'icon' => 'fas fa-calendar-alt'],
    'rel_amministrazioni' => ['label' => 'REL. ATTI AMMINISTRAZ.', 'short_label' => 'REL. ATTI AMMINISTRAZ.', 'view' => 'mv_rel_atti_amministrazioni', 'icon' => 'fas fa-sitemap'],
    'rel_pdf' => ['label' => 'REL. ATTI PDF', 'view' => 'mv_rel_atti_pdf', 'icon' => 'fas fa-file-pdf'],
    'aggiornamenti' => ['label' => 'RIFERIMENTI', 'view' => 'atti_aggiornamenti_mv', 'icon' => 'fas fa-history'],
    'documenti' => ['label' => 'DOCUMENTI', 'view' => 'mv_documenti', 'icon' => 'fas fa-folder-open'],
    'occupazioni' => ['label' => 'OCCUPAZIONI', 'view' => 'mv_occupazioni', 'icon' => 'fas fa-draw-polygon'],
    'deroghe' => ['label' => 'DEROGHE SCADENZE ATTI', 'view' => 'mv_deroghe_scadenze_atti', 'icon' => 'fas fa-calendar-check'],
    'planimetrie' => ['label' => 'PLANIMETRIE RICHIESTE', 'view' => 'mv_planimetrie_richieste', 'icon' => 'fas fa-drafting-compass'],
    'contestazioni' => ['label' => 'CONTESTAZIONI', 'view' => 'mv_contestazioni', 'icon' => 'fas fa-exclamation-triangle']
];

<?php
// /src/lib/template.php

function render_page($pageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS) {
    // Estrae i dati del controller (es. $records, $columns) per renderli disponibili nella vista.
    extract($data);

    // <-- CORREZIONE CRITICA
    // Rende la variabile $currentPageKey (con il nome atteso dai template) disponibile
    // per tutti i file che verranno inclusi da qui in poi (main.php, sidebar.php, etc.).
    $currentPageKey = $pageKey;

    // Includi il layout principale che a sua volta includerà la vista specifica.
    require_once __DIR__ . '/../../templates/layouts/main.php';
}

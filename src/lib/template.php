<?php // src/lib/template.php

function render_page($pageKey, $pageConfig, $data, $PAGES, $MENU_GROUPS) {
    // Estrae i dati nell'ambito corrente per renderli disponibili nella vista
    extract($data); 
    
    // Includi il layout principale che a sua volta includerà la vista specifica
    require_once __DIR__ . '/../../templates/layouts/main.php';
}

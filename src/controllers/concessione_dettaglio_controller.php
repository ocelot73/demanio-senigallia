<?php
// /src/controllers/concessione_dettaglio_controller.php
function concessione_dettaglio_controller_data(&$data) {
    $idf24 = $_GET['idf24'] ?? null;
    if (!$idf24) {
        $data['error'] = "IDF24 non specificato.";
        return;
    }

    $conn = get_db_connection();
    // Carica i dati anagrafici principali della concessione per l'header della pagina
    $sql = "SELECT * FROM demanio.concessioni_unione_v WHERE idf24 = $1 LIMIT 1";
    $result = db_query($conn, $sql, [$idf24]);
    $concessione = pg_fetch_assoc($result);

    if (!$concessione) {
        $data['error'] = "Concessione non trovata.";
        pg_close($conn);
        return;
    }
    
    $data['concessione'] = $concessione;
    $data['title'] = "Fascicolo: " . $concessione['denominazione ditta concessionario']; // Titolo dinamico
    pg_close($conn);
}
?>

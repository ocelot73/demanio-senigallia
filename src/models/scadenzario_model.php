<?php
// /src/models/scadenzario_model.php

function get_scadenze_as_events_model($conn, $start, $end) {
    $sql = "
        SELECT 
            s.id,
            s.data_scadenza as start,
            s.livello_sollecito,
            s.stato,
            c.idf24,
            co.denominazione_ditta_concessionario as concessionario
        FROM demanio.solleciti s
        JOIN demanio.canoni_annuali ca ON s.id_canone_annuale = ca.id
        JOIN demanio.concessioni_unione_v c ON ca.idf24 = c.idf24
        JOIN demanio.concessioni co ON c.idf24 = co.idf24 -- Per il nome corretto
        WHERE s.data_scadenza BETWEEN $1 AND $2
    ";
    
    $result = db_query($conn, $sql, [$start, $end]);
    $events = [];
    
    while ($row = pg_fetch_assoc($result)) {
        $events[] = [
            'title' => $row['concessionario'],
            'start' => $row['start'],
            'extendedProps' => [
                'idf24' => $row['idf24'],
                'livello' => $row['livello_sollecito'],
                'stato' => $row['stato']
            ]
        ];
    }
    return $events;
}
?>

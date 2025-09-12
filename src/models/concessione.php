<?php
// /config/config.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * ==========================================================
 * Configurazione Database
 * ==========================================================
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
// !!! CORREZIONE CRITICA: INSERISCI QUI L'URL COMPLETO DELLA CARTELLA PUBLIC !!!
define('APP_URL', 'https://sit.comune.senigallia.an.it/demanio-senigallia/public');
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
    'nt_area_scoperta' => [
        'label'   => 'NT Area scoperta',
        'title'   => 'Area scoperta',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 1, comma 1, lett. a)</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'nt_facile_rimozione' => [
        'label'   => 'NT Facile rimozione',
        'title'   => 'Area di sedime impianti di facile rimozione',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 1, comma 1, lett. b)</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'nt_difficile_rimozione' => [
        'label'   => 'NT Difficile rimozione',
        'title'   => 'Area di sedime impianti di difficile rimozione',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 1, comma 1, lett. c)</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'nt_volume_oltre_2_7m' => [
        'label'   => 'NT Volume oltre 2.7m',
        'title'   => 'Volumetria eccedente la quota +/- 2,70 mt./al m3',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 1, comma 2</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'nt_area_pertinenze' => [
        'label'   => 'NT Pertinenze',
        'title'   => 'Area di sedime pertinenze',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 2, comma 1</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'nt_volume_oltre_2_7m_pertinenze' => [
        'label'   => 'NT Volume oltre 2.7m pertinenze',
        'title'   => 'Volumetria eccedente la quota +/- 2,70 mt. per le pertinenze del p.d.m./al m3',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 2, comma 1</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
    'pac_aree_imp_a_terra' => [
        'label'   => 'PAC Aree, manufatti e impianti ubicati a terra',
        'title'   => 'Pesca e acquacoltura: Aree, manufatti e impianti ubicati a terra sul demanio marittimo',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER LA <strong>PESCA ED ACQUACOLTURA, CANTIERI NAVALI ED ATTIVITA' CONCERNENTI LA COSTRUZIONE, MANUTENZIONE, RIPARAZIONE O DEMOLIZIONE DI MEZZI AERONAVALI</strong></p><p>Articolo 1 Pesca e Acquacoltura - Punto 1</p><p>Decreto interministeriale 15 novembre 1995, n. 595, attuativo dell'articolo 03, comma 2 del D.L. 400/93, convertito con modificazioni nella Legge 4 dicembre 1993, n. 494; Legge 23 dicembre 1996, n. 647, di conversione del D.L. 21 ottobre 1996, n. 535</p>",
        'hint'    => '--',
    ],
    'pac_acque_impianti_mare' => [
        'label'   => 'PAC Specchi acquei, manufatti e impianti ubicati nel mare',
        'title'   => 'Pesca e acquacoltura: Specchi acquei, manufatti e impianti ubicati nel mare territoriale',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER LA <strong>PESCA ED ACQUACOLTURA, CANTIERI NAVALI ED ATTIVITA' CONCERNENTI LA COSTRUZIONE, MANUTENZIONE, RIPARAZIONE O DEMOLIZIONE DI MEZZI AERONAVALI</strong></p><p>Articolo 1 Pesca e Acquacoltura - Punto 2</p><p>Decreto interministeriale 15 novembre 1995, n. 595, attuativo dell'articolo 03, comma 2 del D.L. 400/93, convertito con modificazioni nella Legge 4 dicembre 1993, n. 494; Legge 23 dicembre 1996, n. 647, di conversione del D.L. 21 ottobre 1996, n. 535</p>",
        'hint'    => '--',
    ],
    'pac_cantieristica' => [
        'label'   => 'PAC Cantieristica',
        'title'   => 'Cantieristica - Aree, specchi acquei, manufatti e pertinenze',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER LA <strong>PESCA ED ACQUACOLTURA, CANTIERI NAVALI ED ATTIVITA' CONCERNENTI LA COSTRUZIONE, MANUTENZIONE, RIPARAZIONE O DEMOLIZIONE DI MEZZI AERONAVALI</strong></p><p>Articolo 2 Cantieristica</p><p>Decreto interministeriale 15 novembre 1995, n. 595, attuativo dell'articolo 03, comma 2 del D.L. 400/93, convertito con modificazioni nella Legge 4 dicembre 1993, n. 494; Legge 23 dicembre 1996, n. 647, di conversione del D.L. 21 ottobre 1996, n. 535</p>",
        'hint'    => '--',
    ],
    't_a_scoperte' => [
        'label'   => 'T cat.A Area scoperta',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - categoria A - Area scoperta',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_b_scoperte' => [
        'label'   => 'T cat.B Area scoperta',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - categoria B - Area scoperta',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_a_facile_rimozione' => [
        'label'   => 'T cat.A Facile rimozione',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - cat. A - Aree/specchi acquei con impianti/opere di facile rimozione',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_b_facile_rimozione' => [
        'label'   => 'T cat.B Facile rimozione',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - cat. B - Aree/specchi acquei con impianti/opere di facile rimozione',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_a_difficile_rimozione' => [
        'label'   => 'T cat.A Difficile rimozione',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - cat. A - Aree/specchi acquei con impianti/opere di difficile rimozione',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_b_difficile_rimozione' => [
        'label'   => 'T cat.B Difficile rimozione',
        'title'   => 'Finalità turistico-ricreative e nautica da diporto - cat. B - Aree/specchi acquei con impianti/opere di difficile rimozione',
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_porti_e_acque_inf_100m' => [
        'label'   => 'T specchi acquei porti entro 100m dalla costa',
        'title'   => "Finalità turistico-ricreative e nautica da diporto - specchi acquei porti (art. 5 R.D. 2 aprile 1885, n. 3095) entro 100 m dalla costa",
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_acque_101_300m' => [
        'label'   => 'T specchi acquei 101–300m dalla battigia',
        'title'   => "Finalità turistico-ricreative e nautica da diporto - specchi acquei porti tra 101 m e 300 m dalla battigia",
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    't_acque_oltre_300m' => [
        'label'   => 'T specchi acquei oltre 300m dalla battigia',
        'title'   => "Finalità turistico-ricreative e nautica da diporto - specchi acquei porti oltre 300 m dalla battigia",
        'content' => "<p>Canoni relativi a concessioni demaniali marittime con <strong>finalità turistico-ricreative</strong> e per le strutture destinate alla <strong>nautica da diporto</strong></p><p>Misure di canone tabellari introdotte (art. 1, c. 251, L. 296/2006) e rivalutate con indici ISTAT dal 1999 al 2007 (decorrenza 01/01/2007). Riferimenti: L. 296/2006, circolari Agenzia Demanio 2007/7162/DAO, 2007/9801, 2009/5894, 2009/22570/DAO-CO/BD; Circolari MIT 2007 n.15, 2009 nn. 22 e 26; DL 104/2020 art. 100 convertito in L. 126/2020.</p>",
        'hint'    => '--',
    ],
    'nt_tetto_max_vol' => [
        'label'   => 'NT Volume oltre 2.7m - tetto massimo',
        'title'   => 'Volumetria eccedente la quota +/- 2,70 mt./al m3 tetto massimo',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 1, comma 2</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
	'nt_tetto_max_vol_pertinenze' => [
        'label'   => 'NT Volume oltre 2.7m pertinenze - tetto massimo',
        'title'   => 'Volumetria eccedente la quota +/- 2,70 mt. per le pertinenze del p.d.m./al m3 tetto massimo',
        'content' => "<p>CONCESSIONI DEMANIALI MARITTIME PER TUTTE LE FINALITA' <strong>DIVERSE DA TURISTICO RICREATIVO, CANTIERISTICA NAVALE E NAUTICA DA DIPORTO</strong></p><p>Articolo 2, comma 1</p><p>Decreto interministeriale 19 luglio 1989, attuativo delle disposizioni della legge 5 maggio 1989, n. 160 - Artt. 1 e 4 della legge 494/1993</p>",
        'hint'    => '--',
    ],
];

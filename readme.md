# Gestione Demanio - Comune di Senigallia

Applicazione web per la gestione delle concessioni demaniali marittime del Comune di Senigallia.

## Descrizione

Questo progetto fornisce un'interfaccia per visualizzare, modificare e gestire i dati relativi alle concessioni demaniali, importati dal sistema SID. Include funzionalità per il calcolo dei canoni, la gestione dei pagamenti e dei solleciti tramite uno scadenzario interattivo.

## Struttura del Progetto

L'applicazione segue un'architettura modulare per separare la logica, la presentazione e la configurazione.

- **/config**: File di configurazione (database, variabili globali).
- **/public**: Unico punto di accesso (index.php) e assets pubblici (CSS, JS).
- **/src**: Logica dell'applicazione (controller, model, librerie).
- **/templates**: File di layout e viste per la presentazione HTML.

## Installazione

1. Clona il repository.
2. Crea una copia del file `config/config.php.example` e rinominalo in `config/config.php`.
3. Inserisci le credenziali corrette per il database PostgreSQL in `config/config.php`.
4. Assicurati che le tabelle del database e le viste materializzate necessarie esistano.
5. Configura il tuo web server (Apache/Nginx) affinché la document root punti alla cartella `/public`.

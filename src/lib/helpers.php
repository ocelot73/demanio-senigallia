<?php
// =====================================================================
// FILE: /src/lib/helpers.php
// Contiene funzioni di utilità usate in varie parti dell'applicazione.
// =====================================================================

/**
 * Cerca il primo file con estensione .json in una directory.
 *
 * @param string $dir La directory in cui cercare.
 * @return string|null Il nome del file trovato o null se non trovato.
 */
function findJsonFile($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            return $file;
        }
    }
    return null;
}

/**
 * Cancella ricorsivamente una directory e tutto il suo contenuto.
 *
 * @param string $dirPath Il percorso della directory da cancellare.
 * @return void
 */
function deleteDirectory($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDirectory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
?>

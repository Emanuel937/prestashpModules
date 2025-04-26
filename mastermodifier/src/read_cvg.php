<?php 

function readCSV($filePath) {
    if (!file_exists($filePath)) {
        die(json_encode(["error" => true, "message" => "❌ Erreur : Fichier introuvable."]));
    }

    if (($handle = fopen($filePath, 'r')) === false) {
        die(json_encode(["error" => true, "message" => "❌ Erreur : Impossible d'ouvrir le fichier CSV."]));
    }

    $data = [];

    // Détecter le séparateur (',' ou ';')
    $firstLine = fgets($handle);
    $separator = (strpos($firstLine, ';') !== false) ? ';' : ',';
    rewind($handle); // Revenir au début du fichier

    // Lire les en-têtes
    if (($headers = fgetcsv($handle, 1000, $separator)) === false) {
        die(json_encode(["error" => true, "message" => "❌ Erreur : Impossible de lire les en-têtes du fichier CSV."]));
    }

    // Lire chaque ligne du fichier CSV
    while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
        if (count($row) !== count($headers)) {
            continue; // Ignore les lignes incomplètes
        }

        $data[] = array_combine($headers, $row);
    }

    fclose($handle);
    return $data;
}


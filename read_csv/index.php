<?php

function readCSV($filePath, $columnsToExtract = []) {
    // Vérifier si le fichier existe
    if (!file_exists($filePath)) {
        die("❌ Erreur : Fichier introuvable.");
    }

    // Ouvrir le fichier CSV
    if (($handle = fopen($filePath, 'r')) === false) {
        die("❌ Erreur : Impossible d'ouvrir le fichier CSV.");
    }

    $data = [];
    $headers = [];
    
    // Lire les en-têtes (première ligne)
    if (($headers = fgetcsv($handle)) === false) {
        die("❌ Erreur : Impossible de lire les en-têtes du fichier CSV.");
    }

    // Afficher les en-têtes pour débogage
    echo "📌 En-têtes détectés : ";
    print_r($headers);
    echo "<br>";

    // Vérifier si les colonnes demandées existent
    $columnsIndexes = [];
    foreach ($columnsToExtract as $col) {
        $index = array_search($col, $headers);
        if ($index === false) {
            echo "⚠️ Colonne manquante : $col<br>";
        } else {
            $columnsIndexes[] = $index;
        }
    }

    // Lire chaque ligne du fichier CSV
    while (($row = fgetcsv($handle)) !== false) {
        $rowAssoc = [];

        // Extraire uniquement les colonnes demandées
        foreach ($columnsIndexes as $index) {
            $rowAssoc[$headers[$index]] = $row[$index];
        }

        // Ajouter la ligne à la liste des données
        if (!empty($rowAssoc)) {
            $data[] = $rowAssoc;
        }
    }

    // Fermer le fichier CSV
    fclose($handle);

    var_dump($data);
    return $data;
}

// ====== UTILISATION ======

// Chemin du fichier CSV
$filePath = "turbo_r.csv"; // Assurez-vous que le fichier existe

// Colonnes à extraire (index et reference)
$columns = ["index", "code_moteur", "title"]; // Les noms doivent correspondre aux en-têtes dans le fichier

// Exécution de la fonction
$resultats = readCSV($filePath, $columns);

// Affichage des résultats
echo "<pre>";
print_r($resultats);
echo "</pre>";

?>

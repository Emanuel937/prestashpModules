<?php




ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once _PS_MODULE_DIR_ . 'mastermodifier/src/read_cvg.php';


class AdminFeatureMastermodifierController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true; // Activate Bootstrap for Back Office
        $this->table = 'feature'; // Set a table (important for controllers)
        $this->className = 'Feature'; // Class associated with the controller
    }
    public function initContent()
    {
 
        parent::initContent();

        if($_SERVER['REQUEST_METHOD'] == 'POST'){

        
            $data = $this->ajaxProcessUploadCsv();
        
            if (!$data || !isset($data['cvs_data'])) {
                die(json_encode(["error" => true, "message" => "❌ Erreur : Impossible d'ouvrir le fichier CSV."]));
            }
        
            $csv_data         = $data['cvs_data'];
            $featureId        = (int) $data['feature_id'];
            $productId_Column = $data['id_column'];
            $column_value     = $data['column_value'];
        
            // Vérifier si les colonnes existent
            if (!isset($csv_data[0][$productId_Column]) || !isset($csv_data[0][$column_value])) {
                die(json_encode([
                    "error"   => true,
                    "message" => "❌ Erreur : Une des colonnes spécifiées ('$productId_Column', '$column_value') n'existe pas dans le fichier CSV."
                ]));
            }

            // send response to the server 

            die(json_encode([
                "error"=> false,
                "message"=> " ✅ FILE WAS SUCCESSELY UPLOADED ........"
            ]));


        }


        if (Tools::getValue("sse") == "1") {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');

            $featureId      =  $_SESSION['feature_id'];       
            $newFilePath    = $_SESSION['cvs_path'];         
            $productIndex   = $_SESSION['product_id_column'];  
            $columnValue    = $_SESSION['column_value'];
            $csv_data       = $_SESSION['cvs_path'];
             


            $fileData  = readCSV($newFilePath);
            $count     = count($fileData);
            $current = 0;
            $progression  = null;

            foreach ($fileData as $column) {
                $current++;

               $progression  = (($current/$count) * 100);


               $product = new Product($column[$productIndex]);

              if (Validate::isLoadedObject($product)) {

                   $feature = new Feature($featureId);
                   if (Validate::isLoadedObject($feature)) {
                       $this->addFeatureValue($column[$productIndex], $featureId, $column[$columnValue ]);
                   }
           
            }

            echo "data: " . json_encode(["message" => "Event started...", "progress"=> $progression]) . "\n\n";

            ob_flush();
            flush();

            usleep(50000);
          
        
         
        }
        exit; // Fin du script pour éviter d'autres sorties inutiles
        
    
    }

}
    



    public function ajaxProcessUploadCsv()
    {
        // 1️⃣ Définir le type de réponse en JSON
        header('Content-Type: application/json');
    
        // Vérifier si la requête est bien en POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            die(json_encode(['error' => true, 'message' => 'Requête invalide']));
        }
    
        // 2️⃣ Récupérer les données du formulaire
        $productIndex = Tools::getValue('product_index');  // Index du produit
        $columnValue = Tools::getValue('column_value');    // Valeur de colonne
        $featureId = Tools::getValue('features');          // ID de la caractéristique sélectionnée
    
        // 3️⃣ Vérifier si un fichier CSV a été envoyé
        if (empty($_FILES['csv_file']['tmp_name'])) {
            die(json_encode(['error' => true, 'message' => 'Aucun fichier envoyé']));
        }
    
        // 4️⃣ Récupérer les informations du fichier
        $fileTmpPath = $_FILES['csv_file']['tmp_name']; // Chemin temporaire
        $fileName = $_FILES['csv_file']['name'];        // Nom du fichier
        $fileSize = $_FILES['csv_file']['size'];        // Taille du fichier
        $fileType = $_FILES['csv_file']['type'];        // Type MIME du fichier
    
        // Vérifier si c'est bien un fichier CSV
        $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    
        if (!in_array($fileType, $allowedMimeTypes) || strtolower($fileExtension) !== 'csv') {
            die(json_encode(['error' => true, 'message' => 'Le fichier doit être un CSV']));
        }
        


            // 5️⃣ Définir le chemin du dossier d'uploads (dans le module)
        $uploadDir = _PS_MODULE_DIR_ . 'mastermodifier/uploads/';
        
        // Vérifier si le dossier existe, sinon le créer
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Définir le chemin final du fichier
        $newFilePath = $uploadDir . 'file.csv'; // Tu peux aussi garder le nom d'origine : $uploadDir . $fileName;


        // Déplacer le fichier temporaire vers le dossier d'uploads
        if (!move_uploaded_file($fileTmpPath, $newFilePath)) {
            die(json_encode(['error' => true, 'message' => 'Erreur lors du déplacement du fichier.']));
        }
        

        // stocke all data into the session 
        $_SESSION['feature_id']          = $featureId;
        $_SESSION['cvs_path']            = $newFilePath;
        $_SESSION['product_id_column']   = $productIndex;
        $_SESSION['column_value']        = $columnValue;

        // handle cvs file 
        $csvData = readCSV($newFilePath);

       return [
            'cvs_data'=> $csvData,
            'id_column'=> $productIndex,
            'column_value' => $columnValue,
            'feature_id' => $featureId
        ];
    }
    

    public function addFeatureValue($productId, $featureId, $newValues)
    {
        $values = array_map('trim', explode(',', $newValues));
        $languages = Language::getLanguages(false);
    
        foreach ($values as $value) {
            if (empty($value)) {
                PrestaShopLogger::addLog('La valeur est vide, passage à la suivante.', 3);
                continue;
            }
    
            // Vérifier si la valeur existe déjà
            $existingFeatureValues = Db::getInstance()->executeS(
                'SELECT fv.id_feature_value
                FROM ' . _DB_PREFIX_ . 'feature_value fv
                JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl 
                ON fv.id_feature_value = fvl.id_feature_value
                WHERE fv.id_feature = ' . (int)$featureId . '
                AND fvl.value = "' . pSQL($value) . '"
                LIMIT 1'
            );
    
            if (empty($existingFeatureValues)) {
                // Insérer dans ps_feature_value
                Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . 'feature_value (id_feature, custom) 
                     VALUES (' . (int)$featureId . ', 0)'
                );
                $idFeatureValue = Db::getInstance()->Insert_ID();
    
                // Insérer dans ps_feature_value_lang pour chaque langue
                foreach ($languages as $language) {
                    Db::getInstance()->execute(
                        'INSERT INTO ' . _DB_PREFIX_ . 'feature_value_lang (id_feature_value, id_lang, value) 
                         VALUES (' . (int)$idFeatureValue . ', ' . (int)$language['id_lang'] . ', "' . pSQL($value) . '")'
                    );
                }
    
                PrestaShopLogger::addLog('Nouvelle valeur ajoutée : ' . $value, 1);
            } else {
                $idFeatureValue = (int)$existingFeatureValues[0]['id_feature_value'];
                PrestaShopLogger::addLog('Valeur existante trouvée : ' . $value, 1);
            }
    
            // Vérifier si l'association existe déjà dans ps_feature_product
            $existingProductFeature = Db::getInstance()->getValue(
                'SELECT id_feature_value FROM ' . _DB_PREFIX_ . 'feature_product 
                 WHERE id_product = ' . (int)$productId . ' 
                 AND id_feature_value = ' . (int)$idFeatureValue
            );
    
            if (!$existingProductFeature) {
                // Associer la caractéristique au produit dans ps_feature_product
                Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . 'feature_product (id_product, id_feature, id_feature_value) 
                     VALUES (' . (int)$productId . ', ' . (int)$featureId . ', ' . (int)$idFeatureValue . ')'
                );
                PrestaShopLogger::addLog('Valeur liée au produit : ' . $value, 1);
            } else {
                PrestaShopLogger::addLog('Valeur déjà associée au produit : ' . $value, 1);
            }
        }
    }


    public function sendProgress($percentage)
    {
        try {
            $data = ["progress" => (int) $percentage]; // Assurer que c'est un nombre
            echo "data: " . json_encode($data) . "\n\n";
            ob_flush();
            flush();
        } catch (Exception $e) {
            error_log("Erreur SSE: " . $e->getMessage());
        }
    }
    
    

}



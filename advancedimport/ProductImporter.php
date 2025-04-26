<?php
class ProductImporter
{
    protected $logger;
    protected $context;

    public function __construct($logger, $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function processBatch($batch, $importParams, &$result, &$categoryCache, &$manufacturerCache, &$featureCache)
        {
            $db = Db::getInstance();
            $db->execute('START TRANSACTION');
            $this->logger->logInfo("Starting batch processing, batch size: " . count($batch));

            $imageProcessor = new ImageProcessor($this->logger); 

            try {
                foreach ($batch as $row) {
                    try {
                        $this->logger->logInfo("Processing row {$row['row_index']}: " . json_encode($row['data']));
                        $this->importProduct(
                            $row['data'],
                            $importParams['forceIds'],
                            $importParams['matchRef'],
                            $importParams['multiSeparator'],
                            $categoryCache,
                            $manufacturerCache,
                            $featureCache,
                            $row['row_index'],
                            $imageProcessor

                        );
                        $this->logger->logInfo("Successfully processed row {$row['row_index']}");
                    } catch (Exception $e) {
                        $errorMsg = sprintf($this->l('Failed to import product at row %d: %s'), $row['row_index'], $e->getMessage());
                        $result['errors'][] = $errorMsg;
                        $this->logger->logError($errorMsg);
                    }
                }
                $db->execute('COMMIT');
                $this->logger->logInfo("Batch committed successfully");
            } catch (Exception $e) {
                $db->execute('ROLLBACK');
                $errorMsg = sprintf($this->l('Batch failed: %s'), $e->getMessage());
                $result['errors'][] = $errorMsg;
                $this->logger->logError($errorMsg);
            }
        }
        
        protected function importProduct(
            array $data, 
            $forceIds,
             $matchRef, 
             $multiSeparator, 
             &$categoryCache, 
             &$manufacturerCache, 
             &$featureCache, 
             int $rowIndex,
             ImageProcessor $imageProcessor 
             
             )
        {  

            $productId = (int)($data['id'] ?? 0);
        
            if ($productId <= 0 && $forceIds) {
                $this->logger->logError("ID produit manquant ou invalide à la ligne $rowIndex.");
                return;
            }
        
            $isNewProduct = false;
            $product      = null;
        
            if ($forceIds && $productId > 0) {
                
                $existingProduct = new Product($productId);
                if ($existingProduct->id) {

                    $product = $existingProduct;
                    $this->logger->logInfo("Mise à jour du produit existant ID $productId à la ligne $rowIndex.");

                } else {

                    $product = new Product();
                    $product->force_id = true;
                    $product->id = $productId;
                    $isNewProduct = true;
                    $this->logger->logInfo("Création du produit avec ID forcé $productId à la ligne $rowIndex.");
                }
            } else {
                $product = new Product();
                $isNewProduct = true;
                $this->logger->logInfo("Création d’un nouveau produit (sans ID forcé) à la ligne $rowIndex.");
            }
        
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
            // Nom et URL réécrite
            $name = $data['name'] ?? 'Produit sans nom';
            $product->name = [$id_lang => $name];
            $product->link_rewrite = [$id_lang => Tools::link_rewrite($name)];
        
            // Champs essentiels
            $product->price = (float)($data['price'] ?? 0);
            $product->active = (int)($data['active'] ?? 1);
            $product->id_category_default = (int)($data['id_category_default'] ?? 2);
            $product->description_short = [$id_lang => $data['description_short'] ?? ''];
            $product->description = [$id_lang => $data['description'] ?? ''];
        
            // Ajouter ici d'autres champs si besoin
        
            // Sauvegarde
            if ($isNewProduct) {
                if (!$product->add()) {
                    $msg = "Échec création produit ligne $rowIndex (ID: $productId): " . $product->getWsErrors();
                    $this->logger->logError($msg);
                    throw new Exception($msg);
                } else {
                    $this->logger->logInfo("Produit créé avec succès (ID: {$product->id}) à la ligne $rowIndex.");
                }
            } else {
                if (!$product->update()) {
                    $msg = "Échec mise à jour produit ID $productId ligne $rowIndex : " . $product->getWsErrors();
                    $this->logger->logError($msg);
                    throw new Exception($msg);
                } else {
                    $this->logger->logInfo("Produit mis à jour avec succès (ID: {$product->id}) à la ligne $rowIndex.");
                }
            }
        
            // Si des features sont présents
            if (!empty($data['features']) && is_array($data['features'])) {
                $position = 0;
                foreach ($data['features'] as $name => $value) {
                    $this->addFeature($product, $name, $value, $position++, $featureCache);
                }
            }
         
            if (!empty($data['image'])) {
               $imageProcessor->queueImage($product->id, $data['image']);
            }

    }
        
    public function preloadCategories()
    {
        $sql = 'SELECT c.id_category, cl.name
                FROM ' . _DB_PREFIX_ . 'category c
                LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category
                WHERE cl.id_lang = ' . (int)$this->context->language->id;
        $rows = Db::getInstance()->executeS($sql);
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['name']] = (int)$row['id_category'];
        }
        $this->logger->logInfo('Preloaded ' . count($cache) . ' categories');
        return $cache;
    }

    public function preloadManufacturers()
    {
        $sql = 'SELECT id_manufacturer, name FROM ' . _DB_PREFIX_ . 'manufacturer';
        $rows = Db::getInstance()->executeS($sql);
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['name']] = (int)$row['id_manufacturer'];
        }
        $this->logger->logInfo('Preloaded ' . count($cache) . ' manufacturers');
        return $cache;
    }

    private function addFeature($product, $featureName, $featureValue, $position, &$featureCache)
    {
        if (empty($featureName) || empty($featureValue)) {
            return;
        }
        $cacheKey = md5($featureName . ':' . $featureValue);
        if (!isset($featureCache[$cacheKey])) {
            $featureId = Feature::addFeatureImport($featureName, $position);
            $featureValueId = FeatureValue::addFeatureValueImport($featureId, $featureValue);
            $featureCache[$cacheKey] = ['feature_id' => $featureId, 'value_id' => $featureValueId];
        }
        $product->addFeaturesToDB($featureCache[$cacheKey]['feature_id'], $featureCache[$cacheKey]['value_id']);
        $this->logger->logInfo("Added feature $featureName:$featureValue to product ID {$product->id}");
    }

    private function getCategoryIdByName($categoryName)
    {
        if (empty($categoryName)) {
            return false;
        }
        $sql = 'SELECT c.id_category
                FROM ' . _DB_PREFIX_ . 'category c
                LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON c.id_category = cl.id_category
                WHERE cl.name = \'' . pSQL($categoryName) . '\'
                AND cl.id_lang = ' . (int)$this->context->language->id;
        return (int)Db::getInstance()->getValue($sql);
    }

    private function createCategory($categoryName)
    {
        if (empty($categoryName)) {
            return false;
        }
        $category = new Category();
        $category->name = [$this->context->language->id => $categoryName];
        $category->link_rewrite = [$this->context->language->id => Tools::link_rewrite($categoryName)];
        $category->id_parent = (int)Configuration::get('PS_HOME_CATEGORY');
        $category->active = 1;
        if ($category->add()) {
            $this->logger->logInfo("Created category: $categoryName (ID: {$category->id})");
            return $category->id;
        }
        $this->logger->logError("Failed to create category: $categoryName");
        return false;
    }

    private function getManufacturerIdByName($manufacturerName)
    {
        if (empty($manufacturerName)) {
            return false;
        }
        $sql = 'SELECT id_manufacturer FROM ' . _DB_PREFIX_ . 'manufacturer WHERE name = \'' . pSQL($manufacturerName) . '\'';
        return (int)Db::getInstance()->getValue($sql);
    }

    private function createManufacturer($manufacturerName)
    {
        if (empty($manufacturerName)) {
            return false;
        }
        $manufacturer = new Manufacturer();
        $manufacturer->name = $manufacturerName;
        $manufacturer->active = 1;
        if ($manufacturer->add()) {
            $this->logger->logInfo("Created manufacturer: $manufacturerName (ID: {$manufacturer->id})");
            return $manufacturer->id;
        }
        $this->logger->logError("Failed to create manufacturer: $manufacturerName");
        return false;
    }
}
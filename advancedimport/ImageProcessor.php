<?php

class ImageProcessor
{
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function queueImage($productId, $imageUrl)
    {

        try {

            // set images 
            
            Db::getInstance()->insert('advanced_import_image_queue', [
                'id_product' => (int)$productId,
                'image_url' => pSQL($imageUrl),
                'is_cover' => 'false',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logger->logInfo("Queued image for product ID $productId: $imageUrl");

        } catch (Exception $e) {

            $this->logger->logError("Failed to queue image for product ID $productId: " . $e->getMessage());
        }
    }

    public function processQueuedImages()
    {   

       $limit = 50;
       $sql = ' SELECT id_queue, id_product, image_url, is_cover
                FROM ' . pSQL(_DB_PREFIX_) . 'advanced_import_image_queue
                WHERE status = "pending" OR status = "failed" 
                LIMIT ' . (int)$limit;
    

     
        $rows = Db::getInstance()->executeS($sql);

        $result = [
            'success' => true,
            'processed' => 0,
            'errors' => []
        ];

        if (empty($rows)) {
            $this->logger->logInfo('No pending images in queue.');
            return $result;
        }


        foreach ($rows as $row) {
            try {
                $this->logger->logInfo("Processing image for product ID {$row['id_product']}: {$row['image_url']}");

                $this->addImage($row['id_product'], $row['image_url']);

                Db::getInstance()->update('advanced_import_image_queue', [
                    'status' => 'processed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id_queue = ' . (int)$row['id_queue']);

                $result['processed']++;

               

            } catch (Exception $e) {

                Db::getInstance()->update('advanced_import_image_queue', [
                    'status' => 'failed',
                    'error_message' => pSQL($e->getMessage()),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id_queue = ' . (int)$row['id_queue']);
                $result['errors'][] = sprintf(
                    'Failed to process image for product ID %d: %s',
                    $row['id_product'],
                    $e->getMessage()
                );
                $this->logger->logError("Failed to process image for product ID {$row['id_product']}: {$e->getMessage()}");
            }
        }

        if (!empty($result['errors'])) {
            $result['success'] = false;
        }

        return $result;
    }



    public function addImage($productId, $imageUrls)
    {

      
        $imageUrlsArray = explode(',', $imageUrls);
   

        if (empty($imageUrlsArray)) {
            throw new Exception('No image URLs provided.');
        }
    
  
        $coverImageUrl = $imageUrlsArray[0];
    
        // Traiter la première image comme cover
        $this->logger->logInfo("Starting image addition for product ID $productId: $coverImageUrl");

        if (!$this->downloadAndSaveImage($productId, $coverImageUrl, true)) {
            throw new Exception(sprintf('Failed to add cover image for product ID %d', $productId));
        }
            for ($i = 1; $i < count($imageUrlsArray); $i++) {
                $imageUrl = $imageUrlsArray[$i];
                if(!$imageUrl === "https"){
                    $this->logger->logInfo("Starting image addition for product ID $productId: $imageUrl");
                    if (!$this->downloadAndSaveImage($productId, $imageUrl, false)) {
                        throw new Exception(sprintf('Failed to add additional image for product ID %d, with url %s', $productId, $imageUrl));
                    }
                }
            }
    
        $this->logger->logInfo("All images added for product ID $productId.");
    
   
    }



    private function downloadAndSaveImage($productId, $imageUrl, $isCover)
    {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL) || !preg_match('/\.(jpg|jpeg|png)$/i', $imageUrl)) {
            $this->logger->logError("Invalid or unsupported image URL: $imageUrl");
            return false;
        }
    
        $product = new Product($productId);
        if (!Validate::isLoadedObject($product)) {
            $this->logger->logError("Invalid product ID: $productId");
            return false;
        }
  
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $imageUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_TIMEOUT => 10,
        ]);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);
        curl_close($ch);
    
        if ($httpCode !== 200 || !$imageData || !empty($error)) {
            $this->logger->logError("Failed to download image from $imageUrl: $error");
            return false;
        }
    
        $extension = '.jpg';
        if (strpos($contentType, 'png') !== false) {
            $extension = '.png';
        }
    
        $existingCoverImage = Db::getInstance()->getValue('
        SELECT id_image FROM ' . _DB_PREFIX_ . 'image
        WHERE id_product = ' . (int)$productId . ' AND cover = 1
         ');
    
        if ($existingCoverImage) {
            $oldImage = new Image((int)$existingCoverImage);
            $oldImage->delete(); // Supprime aussi l'entrée image_shop et les fichiers
        }
    
    
        // Create a new image object
        $image = new Image();
        $image->id_product = (int)$productId;
        $image->position = Image::getHighestPosition($productId) + 1;
        $image->cover = $isCover;  // Mark as cover if $isCover is true

        if (!$image->add()) {
            $this->logger->logError("Failed to create image object in DB for product ID $productId");
            return false;
        }
    
        // Get the image path for saving
        $pathWithoutExt = $image->getPathForCreation();
        $tmpFilePath = $pathWithoutExt . $extension;
    
        // Write the image data to a temporary file
        if (!file_put_contents($tmpFilePath, $imageData)) {
            $image->delete();
            $this->logger->logError("Failed to write image to disk: $tmpFilePath");
            return false;
        }
    
        // Resize the image to different formats defined in PrestaShop
        $imageTypes = ImageType::getImagesTypes('products');
        foreach ($imageTypes as $imageType) {
            $resized = ImageManager::resize(
                $tmpFilePath,
                $pathWithoutExt . '-' . Tools::strtolower($imageType['name']) . '.jpg',
                (int)$imageType['width'],
                (int)$imageType['height']
            );
    
            if (!$resized) {
                $image->delete();
                @unlink($tmpFilePath);
                $this->logger->logError("Failed to resize image for type: {$imageType['name']}");
                return false;
            }
        }
    
        // Resize and save the base image (without suffix)
        if (!ImageManager::resize($tmpFilePath, $pathWithoutExt . '.jpg')) {
            $image->delete();
            @unlink($tmpFilePath);
            $this->logger->logError("Failed to resize base image");
            return false;
        }
    
        // Delete the temporary file if the extension is not .jpg
        if ($extension !== '.jpg') {
            @unlink($tmpFilePath);
        }
    
        // Log success
        $this->logger->logInfo("Image added successfully for product ID $productId with image ID {$image->id}");
        return true;
    }
}        
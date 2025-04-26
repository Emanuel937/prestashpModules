<?php
class FileProcessor
{
    protected $logger;
    protected $context;
    protected $field_mapping;

    public function __construct($logger, $context)
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    public function validateFile($file)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) 
        {
            $this->logger->logError('Invalid file upload: tmp_name not set or not uploaded.');

            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime      = mime_content_type($file['tmp_name']);
        $valid     = $extension === 'csv' && in_array($mime, ['text/csv', 'text/plain', 'application/csv']);
        
        if (!$valid) {
            $this->logger->logError("Invalid file type: extension=$extension, mime=$mime");
        }

        return $valid;
    }

    public function getCsvHeaders($filePath)
    {
        $fieldSeparator = Tools::getValue('csv_separator', ';');
        $this->logger->logInfo("Reading CSV headers with separator: '$fieldSeparator'");
        try {
            $file = new SplFileObject($filePath, 'r');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            $file->setCsvControl($fieldSeparator);
            $headers = $file->fgetcsv();
            $headers = is_array($headers) ? array_map('trim', $headers) : false;
            $this->logger->logInfo('CSV headers: ' . json_encode($headers));
            return $headers;
        } catch (Exception $e) {
            $this->logger->logError('Error reading CSV headers: ' . $e->getMessage());
            return false;
        }
    }

    public function validateFieldMapping($field_mapping)
    {
        $valid = in_array('name', array_values($field_mapping));
        $this->logger->logInfo('Field mapping validation: ' . ($valid ? 'Valid (name mapped)' : 'Invalid (name not mapped)'));
        return $valid;
    }

    public function processImport($filePath, $field_mapping)
    {
        
        ini_set('memory_limit', '-1'); 
        ini_set('max_execution_time', '0');

        $result = ['success' => false, 'errors' => []];
        $importParams = $this->getImportParameters();
        $importKey = uniqid('import_', true);
        $this->logger->logInfo("Starting import with import_key: $importKey, params: " . json_encode($importParams));
        $this->field_mapping = $field_mapping;

        // Validate IDs if forceIds is enabled
        if ($importParams['forceIds']) {
            $validationResult = $this->validateCsvIds($filePath, $importParams['fieldSeparator'], $importParams['skipRows']);
            if (!empty($validationResult['errors'])) {
                $result['errors'] = array_merge($result['errors'], $validationResult['errors']);
                $this->logger->logError('ID validation failed: ' . implode(', ', $validationResult['errors']));
                return $result;
            }
        }

        if ($importParams['truncate']) {
            $this->truncateProducts();
            $this->logger->logInfo('Truncated product-related tables.');
        }

        $this->context->cookie->import_key = $importKey;
        $this->context->cookie->write();

        $productImporter = new ProductImporter($this->logger, $this->context);
        $dbHandler = new DatabaseHandler();

        try {
            $file = new SplFileObject($filePath, 'r');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            $file->setCsvControl($importParams['fieldSeparator']);

            for ($i = 0; $i < $importParams['skipRows'] + 1 && !$file->eof(); $i++) {
                $file->fgetcsv();
            }

            $batch = [];
            $processed = 0;
            $categoryCache = $productImporter->preloadCategories();
            $manufacturerCache = $productImporter->preloadManufacturers();
            $featureCache = [];
            $rowIndex = $importParams['skipRows'] + 1;

            while (!$file->eof() && ($row = $file->fgetcsv()) !== false) {
                $this->logger->logInfo("Reading row $rowIndex: " . json_encode($row));
                if (empty($row) || count($row) < count($field_mapping)) {
                    $error = sprintf('Skipping invalid row %d: insufficient columns', $rowIndex);
                    $result['errors'][] = $error;
                    $this->logger->logError($error);
                    $rowIndex++;
                    continue;
                }

                $mappedRow = $this->mapRow($row, $field_mapping, $rowIndex);
                if ($mappedRow) {
                    $batch[] = ['data' => $mappedRow, 'row_index' => $rowIndex];
                    $this->logger->logInfo("Mapped row $rowIndex: " . json_encode($mappedRow));
                } else {
                    $error = "No valid data mapped for row $rowIndex";
                    $result['errors'][] = $error;
                    $this->logger->logError($error);
                }

                if (count($batch) >= $importParams['batchSize']) {
                    $this->logger->logInfo("Processing batch of " . count($batch) . " rows starting at row $rowIndex");
                    $productImporter->processBatch($batch, $importParams, $result, $categoryCache, $manufacturerCache, $featureCache);
                    $processed += count($batch);
                    $this->logger->logInfo("Completed batch, total processed: $processed");
                    $dbHandler->updateProgress($importKey, $processed, $result['errors'], 'running');
                    $batch = [];
                }
                $rowIndex++;
            }

            if (!empty($batch)) {
                $this->logger->logInfo("Processing final batch of " . count($batch) . " rows");
                $productImporter->processBatch($batch, $importParams, $result, $categoryCache, $manufacturerCache, $featureCache);
                $processed += count($batch);
                $this->logger->logInfo("Completed final batch, total processed: $processed");
            }

            $status = empty($result['errors']) && $processed > 0 ? 'completed' : 'failed';
            $dbHandler->updateProgress($importKey, $processed, $result['errors'], $status);
            $result['success'] = $status === 'completed';
            $this->logger->logInfo("Import finished with status: $status, errors: " . json_encode($result['errors']));
        } catch (Exception $e) {
            $error = 'Import failed: ' . $e->getMessage();
            $result['errors'][] = $error;
            $this->logger->logError($error);
            $dbHandler->updateProgress($importKey, $processed, $result['errors'], 'failed');
        }

        return $result;
    }

    private function getImportParameters()
    {
        $params = [
            'fieldSeparator' => Tools::getValue('csv_separator', ';'),
            'multiSeparator' => Tools::getValue('multiple_value_separator', ','),
            'truncate' => (bool)Tools::getValue('truncate', 0),
            'matchRef' => (bool)Tools::getValue('match_ref', 0),
            'forceIds' => (bool)Tools::getValue('force_all_id', 0),
            'skipRows' => (int)Tools::getValue('skip', 0),
            'batchSize' => (int)Configuration::get('ADVANCEDIMPORT_BATCH_SIZE', 50),
        ];
        $this->logger->logInfo('Import parameters: ' . json_encode($params));
        return $params;
    }

    private function mapRow($row, $field_mapping, $rowIndex)
    {
        $mappedRow = [];
        foreach ($field_mapping as $index => $field) {
            if ($field !== '-1' && isset($row[$index])) {
                $value = trim($row[$index]);
                if ($field === 'id' && !empty($value) && (!is_numeric($value) || (int)$value <= 0)) {
                    $this->logger->logError("Invalid ID value '$value' at row $rowIndex");
                    return null;
                }
                $mappedRow[$field] = $value;
            }
        }
        if (empty($mappedRow) || empty($mappedRow['name'])) {
            $this->logger->logError("Empty or missing name in mapped row at index $rowIndex: " . json_encode($mappedRow));
            return null;
        }
        $this->logger->logInfo("Mapped row $rowIndex successfully: " . json_encode($mappedRow));
        return $mappedRow;
    }

    private function truncateProducts()
    {
        $tables = [
            'product',
            'product_shop',
            'product_lang',
            'category_product',
            'image',
            'image_shop',
            'image_lang',
            'feature_product',
            'stock_available',
        ];
        foreach ($tables as $table) {
            Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . $table);
            $this->logger->logInfo("Truncated table: $table");
        }
    }

    private function validateCsvIds($filePath, $fieldSeparator, $skipRows)
    {
        $errors = [];
        $usedIds = [];
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($fieldSeparator);

        $this->logger->logInfo("Validating IDs in CSV with separator: '$fieldSeparator', skipRows: $skipRows");
        for ($i = 0; $i < $skipRows + 1 && !$file->eof(); $i++) {
            $file->fgetcsv();
        }

        $idIndex = array_search('id', $this->field_mapping);
        if ($idIndex === false && $this->field_mapping['forceIds']) {
            $error = 'ID field not mapped for forceIds option. Please map the ID column.';
            $errors[] = $error;
            $this->logger->logError($error);
            return ['errors' => $errors];
        }

        $rowIndex = $skipRows + 1;
        while (!$file->eof() && ($row = $file->fgetcsv()) !== false) {
            if (!isset($row[$idIndex]) || empty(trim($row[$idIndex]))) {
                $this->logger->logInfo("Row $rowIndex: Empty or missing ID, will use auto-generated ID.");
                $rowIndex++;
                continue;
            }
            $id = trim($row[$idIndex]);
            if (!is_numeric($id) || (int)$id <= 0) {
                $error = sprintf('Invalid ID %s at row %d.', $id, $rowIndex);
                $errors[] = $error;
                $this->logger->logError($error);
            } elseif (in_array($id, $usedIds)) {
                $error = sprintf('Duplicate ID %s at row %d.', $id, $rowIndex);
                $errors[] = $error;
                $this->logger->logError($error);
            } else {
                $usedIds[] = $id;
                $this->logger->logInfo("Row $rowIndex: Valid ID $id");
            }
            $rowIndex++;
        }

        $this->logger->logInfo("Validated IDs: " . count($usedIds) . " unique IDs found in CSV.");
        return ['errors' => $errors];
    }
}
<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAdvancedImportImageProcessorController extends ModuleAdminController
{
    protected $log_file = _PS_MODULE_DIR_ . 'advancedimport/logs/import.log';

    public function __construct()
    {
        parent::__construct();
        $this->module = Module::getInstanceByName('advancedimport');
        if (!$this->module->active) {
            $this->logError('Module not active.');
            die(Tools::displayError('Module not active.'));
        }
        $this->ajax = true;
    }

    public function initContent()
    {
        if (!$this->context->employee->isLoggedBack()) {
            $this->logError('Unauthorized access attempt.');
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => $this->module->l('Unauthorized access.')
            ]));
        }

        $batchSize = (int)Configuration::get('ADVANCEDIMPORT_IMAGE_BATCH_SIZE', 50);
        $result = $this->processImages($batchSize);

        $this->ajaxDie(json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'processed' => $result['processed'],
            'errors' => $result['errors']
        ]));
    }

    private function processImages($limit)
    {
        $result = [
            'success' => true,
            'message' => $this->module->l('Image processing completed.'),
            'processed' => 0,
            'errors' => []
        ];

        $this->logInfo("Starting image processing with limit=$limit");
        $sql = 'SELECT id_queue, id_product, image_url, is_cover
                FROM ' . _DB_PREFIX_ . 'advanced_import_image_queue
                WHERE status = "pending"
                LIMIT ' . (int)$limit;
        $rows = Db::getInstance()->executeS($sql);

        if (empty($rows)) {
            $result['message'] = $this->module->l('No images to process.');
            $this->logInfo('No pending images in queue.');
            return $result;
        }

        foreach ($rows as $row) {
            try {
                $this->logInfo("Processing image for product ID {$row['id_product']}: {$row['image_url']}");
                $this->module->addImage($row['id_product'], $row['image_url'], $row['is_cover']);
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
                    $this->module->l('Failed to process image for product ID %d: %s'),
                    $row['id_product'],
                    $e->getMessage()
                );
                $this->logError("Failed to process image for product ID {$row['id_product']}: {$e->getMessage()}");
            }
        }

        if (!empty($result['errors'])) {
            $result['success'] = false;
            $result['message'] = $this->module->l('Some images failed to process.');
        }

        return $result;
    }

    private function logError($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] IMAGE ERROR: $message\n", FILE_APPEND);
    }

    private function logInfo($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] IMAGE INFO: $message\n", FILE_APPEND);
    }
}
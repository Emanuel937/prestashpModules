<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/DatabaseHandler.php';
require_once __DIR__ . '/FileProcessor.php';
require_once __DIR__ . '/ProductImporter.php'; 
require_once __DIR__ . '/ImageProcessor.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/FormRenderer.php';

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    
    error_reporting(E_ALL);
}

class AdvancedImport extends Module
{
    protected $field_mapping = [];
    protected $temp_file_path = null;
    protected $logger;

    public function __construct()
    {
        $this->name = 'advancedimport';
        $this->tab = 'administration';
        $this->version = '2.0.3';
        $this->author = 'Custom Dev';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Advanced Import');
        $this->description = $this->l('Optimized product import with real-time progress and image queuing.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->logger = new Logger(_PS_MODULE_DIR_ . 'advancedimport/logs/import.log');
        
        // Create log directory
        $log_dir = _PS_MODULE_DIR_ . 'advancedimport/logs/';
        if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true)) {
            $this->context->controller->errors[] = $this->l('Cannot create log directory.');
        }
    }

    public function install()
    {
        $dbHandler = new DatabaseHandler();
        return parent::install() &&
               $dbHandler->installProgressTable() &&
               $dbHandler->installImageQueueTable() &&
               Configuration::updateValue('ADVANCEDIMPORT_BATCH_SIZE', 50) &&
               Configuration::updateValue('ADVANCEDIMPORT_IMAGE_BATCH_SIZE', 50);
    }

    public function uninstall()
    {
        $dbHandler = new DatabaseHandler();
        return parent::uninstall() &&
               $dbHandler->uninstallProgressTable() &&
               $dbHandler->uninstallImageQueueTable() &&
               Configuration::deleteByName('ADVANCEDIMPORT_BATCH_SIZE') &&
               Configuration::deleteByName('ADVANCEDIMPORT_IMAGE_BATCH_SIZE');
    }

    public function getContent()
    {
        $output = '';
        $errors = [];
        $fileProcessor = new FileProcessor($this->logger, $this->context);
        $formRenderer =  new FormRenderer($this, $this->context);

        // Display forms if no import file is submitted or there are errors
        if (!Tools::isSubmit('submitImportFile') || !empty($errors)) {
            $output .= $formRenderer->renderConfigForm();
            $output .= $formRenderer->renderImportForm();
        }

        // Handle configuration form submission
        if (Tools::isSubmit('submitConfig')) {
            Configuration::updateValue('ADVANCEDIMPORT_BATCH_SIZE', (int)Tools::getValue('batch_size', 50));
            Configuration::updateValue('ADVANCEDIMPORT_IMAGE_BATCH_SIZE', (int)Tools::getValue('image_batch_size', 50));
            $output .= $this->displayConfirmation($this->l('Settings updated.'));
        }

        // Handle CSV file upload
        if (Tools::isSubmit('submitImportFile')) {

            $file = $_FILES['file'] ?? null;
            if ($file && $fileProcessor->validateFile($file)) {

                $tempDir = _PS_MODULE_DIR_ . $this->name . '/temp/';

                if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true)) {

                    $errors[] = $this->l('Cannot create temporary directory.');
                    $this->logger->logError('Failed to create temp directory: ' . $tempDir);

                } else {
                    $this->temp_file_path = $tempDir . uniqid('import_') . '.csv';
                    if (move_uploaded_file($file['tmp_name'], $this->temp_file_path)) {
                        $headers = $fileProcessor->getCsvHeaders($this->temp_file_path);
                        if ($headers) {
                            $this->context->cookie->temp_file_path = $this->temp_file_path;
                            $output .= $formRenderer->renderMappingForm($headers);
                        } else {
                            $errors[] = $this->l('Unable to read CSV headers.');
                            $this->logger->logError('Failed to read CSV headers: ' . $this->temp_file_path);
                            @unlink($this->temp_file_path);
                            $this->temp_file_path = null;
                        }
                    } else {
                        $errors[] = $this->l('Failed to process uploaded file.');
                        $this->logger->logError('Failed to move uploaded file to: ' . $this->temp_file_path);
                    }
                }
                // start import the images;
              

            } else {

                $errors[] = $this->l('Invalid file. Only CSV files are allowed.');
                $this->logger->logError('Invalid file: ' . ($file['name'] ?? 'unknown'));
            }
        }

        // Handle the actual import after field mapping
        if (Tools::isSubmit('submitImport')) {
            $this->field_mapping = Tools::getValue('field_mapping', []);
            $this->temp_file_path = $this->context->cookie->temp_file_path ?? null;
            
            if ($this->temp_file_path && file_exists($this->temp_file_path)) {
                if (!$fileProcessor->validateFieldMapping($this->field_mapping)) {
                    $errors[] = $this->l('Invalid field mapping. "Name" field is required.');
                    $this->logger->logError('Invalid field mapping: Name field missing.');
                } else {
                    $result = $fileProcessor->processImport($this->temp_file_path, $this->field_mapping);

                    if ($result['success']) {

                        $output .= $this->displayConfirmation($this->l('Products imported successfully.'));

                        /**
                         * Now start import des images 
                         */

                 

                    } else {
                        foreach ($result['errors'] as $error) {
                            $output .= $this->displayError($error);
                        }
                    }
                    @unlink($this->temp_file_path);
                    $this->temp_file_path = null;
                    unset($this->context->cookie->temp_file_path);
                    $this->context->cookie->write();
                }
            } else {
                $errors[] = $this->l('Temporary file not found. Please upload again.');
                $this->logger->logError('Temporary file not found: ' . $this->temp_file_path);
            }
        }

        // Display errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output .= $this->displayError($error);
            }
        }

        return $output;
    }

    public function cronProcessImages()
    {
        
        
        $imageProcessor =   new ImageProcessor($this->logger);
        $batchSize      =  (int)Configuration::get('ADVANCEDIMPORT_IMAGE_BATCH_SIZE', 50);
        $result         =  $imageProcessor->processQueuedImages($batchSize);

        $this->logger->logInfo(sprintf(
            'Cron image processing: %d images processed, %d errors',
            $result['processed'],
            count($result['errors'])
        ));

        return $result;
    }
}
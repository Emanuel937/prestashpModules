<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAdvancedImportProgressController extends ModuleAdminController
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
        if (!$this->ajax) {
            parent::initContent();
        }
    }

    public function displayAjax()
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }
        ini_set('zlib.output_compression', 'Off');

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        if (!$this->context->employee->isLoggedBack()) {
            $this->logError('Unauthorized access attempt.');
            $this->sendSSE([
                'status' => 'error',
                'processed_rows' => 0,
                'total_rows' => 0,
                'errors' => [$this->module->l('Unauthorized access.')]
            ]);
            exit;
        }

        $importKey = $this->context->cookie->import_key ?? null;
        $this->logInfo("SSE request received with import_key: " . ($importKey ?? 'null'));
        if (!$importKey || !preg_match('/^import_[a-z0-9\.]+$/', $importKey)) {
            $this->logError('Invalid or missing import key: ' . ($importKey ?? 'null'));
            $this->sendSSE([
                'status' => 'none',
                'processed_rows' => 0,
                'total_rows' => 0,
                'errors' => [$this->module->l('No import in progress.')]
            ]);
            exit;
        }

        try {
            $progress = $this->fetchProgress($importKey);
            $this->sendSSE($progress);

            if (in_array($progress['status'], ['completed', 'failed', 'none'])) {
                $this->logInfo("Closing SSE for import_key=$importKey, status={$progress['status']}");
                unset($this->context->cookie->import_key);
                $this->context->cookie->write();
            }
        } catch (Exception $e) {
            $this->logError('Error fetching progress: ' . $e->getMessage());
            $this->sendSSE([
                'status' => 'error',
                'processed_rows' => 0,
                'total_rows' => 0,
                'errors' => [$this->module->l('Error fetching progress: ') . $e->getMessage()]
            ]);
        }

        exit;
    }

    private function fetchProgress($importKey)
    {
        $query = new DbQuery();
        $query->select('processed_rows, total_rows, status, errors')
              ->from('advanced_import_progress')
              ->where('import_key = \'' . pSQL($importKey) . '\'');
        $result = Db::getInstance()->getRow($query);

        $progress = [
            'processed_rows' => 0,
            'total_rows' => 0,
            'status' => 'none',
            'errors' => [],
        ];

        if ($result) {
            $progress['processed_rows'] = (int)$result['processed_rows'];
            $progress['total_rows'] = (int)$result['total_rows'];
            $progress['status'] = $result['status'];
            $progress['errors'] = $result['errors'] ? json_decode($result['errors'], true) : [];
            $this->logInfo("Progress fetched: status={$progress['status']}, processed={$progress['processed_rows']}/{$progress['total_rows']}");
        } else {
            $this->logError("No progress found for import_key=$importKey");
            $progress['errors'] = [$this->module->l('No import progress found.')];
        }

        return $progress;
    }

    private function sendSSE($data)
    {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    private function logError($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] SSE ERROR: $message\n", FILE_APPEND);
    }

    private function logInfo($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] SSE INFO: $message\n", FILE_APPEND);
    }
}
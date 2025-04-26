<?php
class DatabaseHandler
{
    public function installProgressTable()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'advanced_import_progress (
                id_import INT AUTO_INCREMENT PRIMARY KEY,
                import_key VARCHAR(32) NOT NULL,
                total_rows INT NOT NULL DEFAULT 0,
                processed_rows INT NOT NULL DEFAULT 0,
                status ENUM("pending", "running", "completed", "failed") NOT NULL DEFAULT "pending",
                errors TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_import_key (import_key)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
        ';
        return Db::getInstance()->execute($sql);
    }

    public function uninstallProgressTable()
    {
        $sql = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'advanced_import_progress';
        return Db::getInstance()->execute($sql);
    }

    public function installImageQueueTable()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'advanced_import_image_queue (
                id_queue INT AUTO_INCREMENT PRIMARY KEY,
                id_product INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                is_cover TINYINT(1) NOT NULL DEFAULT 0,
                status ENUM("pending", "processed", "failed") NOT NULL DEFAULT "pending",
                error_message TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;
        ';
        return Db::getInstance()->execute($sql);
    }

    public function uninstallImageQueueTable()
    {
        $sql = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'advanced_import_image_queue';
        return Db::getInstance()->execute($sql);
    }

    public function updateProgress($importKey, $processedRows, $errors, $status)
    {
        try {
            Db::getInstance()->update('advanced_import_progress', [
                'processed_rows' => (int)$processedRows,
                'status' => pSQL($status),
                'errors' => !empty($errors) ? json_encode($errors) : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'import_key = \'' . pSQL($importKey) . '\'');
        } catch (Exception $e) {
            throw new Exception('Failed to update progress: ' . $e->getMessage());
        }
    }
}
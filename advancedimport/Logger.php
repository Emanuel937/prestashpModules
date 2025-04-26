<?php
class Logger
{
    protected $log_file;

    public function __construct($log_file)
    {
        $this->log_file = $log_file;
    }

    public function logError($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] ERROR: $message\n", FILE_APPEND);
    }

    public function logInfo($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] INFO: $message\n", FILE_APPEND);
    }
}
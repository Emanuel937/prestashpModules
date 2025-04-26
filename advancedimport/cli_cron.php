<?php
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'data' => []];

    try {
        $module = Module::getInstanceByName('advancedimport');
        if (!($module instanceof AdvancedImport)) {
            throw new Exception('Module not loaded.');
        }

        // Run the cron process
        $result = $module->cronProcessImages();

        // Check if there are still pending images
        $hasPending = (bool) Db::getInstance()->getValue('
            SELECT id_product
            FROM ' . _DB_PREFIX_ . 'advanced_import_image_queue
            WHERE status = "pending" 
        ');

        $response['success'] = true;
        $response['data'] = [
            'processed' => $result['processed'],
            'errors' => $result['errors'],
            'hasPending' => $hasPending
        ];
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Output HTML and JavaScript for GET requests
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Import Image Processor</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #log-container { 
            border: 1px solid #ccc; 
            padding: 10px; 
            height: 300px; 
            overflow-y: auto; 
            background: #f9f9f9; 
            margin-top: 10px; 
        }
        .log-entry { margin: 5px 0; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        #status { font-weight: bold; margin-top: 10px; }
        button { padding: 10px 20px; margin-bottom: 10px; }
        button:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <h1>Advanced Import Image Processor</h1>
    <button id="start-process" onclick="startProcessing()">Start Processing</button>
    <div id="status">Status: Idle</div>
    <div id="log-container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let isProcessing = false;

        function startProcessing() {
            if (isProcessing) {
                logMessage('Processing already in progress.', 'info');
                return;
            }

            isProcessing = true;
            $('#start-process').prop('disabled', true);
            $('#status').text('Status: Processing...');
            logMessage('Starting image processing...', 'info');
            processImages();
        }

        function processImages() {
            $.ajax({
                url: '<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const { processed, errors, hasPending } = response.data;
                        logMessage(`Processed: ${processed} | Errors: ${errors.length}`, 'info');

                        if (errors.length > 0) {
                            errors.forEach(error => logMessage(error, 'error'));
                        }

                        if (!hasPending) {
                            logMessage('✅ All images have been processed.', 'success');
                            $('#status').text('Status: Completed');
                            isProcessing = false;
                            $('#start-process').prop('disabled', false);
                        } else {
                            logMessage('⏳ Waiting 4 minutes before next batch...', 'info');
                            setTimeout(processImages, 240000); // 4 minutes
                        }
                    } else {
                        logMessage(`Error: ${response.message}`, 'error');
                        $('#status').text('Status: Error');
                        isProcessing = false;
                        $('#start-process').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    logMessage(`AJAX Error: ${error}`, 'error');
                    $('#status').text('Status: Error');
                    isProcessing = false;
                    $('#start-process').prop('disabled', false);
                }
            });
        }

        function logMessage(message, type = 'info') {
            const timestamp = new Date().toLocaleString();
            const className = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
            $('#log-container').append(`<div class="log-entry ${className}">[${timestamp}] ${message}</div>`);
            $('#log-container').scrollTop($('#log-container')[0].scrollHeight); // Auto-scroll to bottom
        }
    </script>
</body>
</html>
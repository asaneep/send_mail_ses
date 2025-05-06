<?php
/**
 * Mock Save Settings Script for Testing
 * 
 * This script is a modified version of save_settings.php for testing.
 */

// Use our HeaderMock instead of the real header function
use Tests\Mocks\HeaderMock;
use Tests\Mocks\ConfigFunctions;

// No need to include config_functions.php as it's already included in the test

// Set content type to JSON (using our mock)
HeaderMock::capture('Content-Type: application/json');

// Set up the $_SERVER superglobal for testing
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}

try {
    // Get form data
    $awsRegion = isset($_POST['awsRegion']) ? trim($_POST['awsRegion']) : '';
    $awsAccessKey = isset($_POST['awsAccessKey']) ? trim($_POST['awsAccessKey']) : '';
    $awsSecretKey = isset($_POST['awsSecretKey']) ? trim($_POST['awsSecretKey']) : '';
    $batchSize = isset($_POST['batchSize']) ? trim($_POST['batchSize']) : '';
    $delayBetweenBatches = isset($_POST['delayBetweenBatches']) ? trim($_POST['delayBetweenBatches']) : '';
    
    // Validate batch size
    if (!empty($batchSize) && (!is_numeric($batchSize) || $batchSize < 1 || $batchSize > 50)) {
        throw new Exception('Batch size must be a number between 1 and 50');
    }
    
    // Validate delay between batches
    if (!empty($delayBetweenBatches) && (!is_numeric($delayBetweenBatches) || $delayBetweenBatches < 0)) {
        throw new Exception('Delay between batches must be a non-negative number');
    }
    
    // Prepare settings array
    $settings = [
        'awsRegion' => $awsRegion,
        'awsAccessKey' => $awsAccessKey,
        'awsSecretKey' => $awsSecretKey,
        'batchSize' => is_numeric($batchSize) ? (int) $batchSize : 10,
        'delayBetweenBatches' => is_numeric($delayBetweenBatches) ? (int) $delayBetweenBatches : 1
    ];
    
    // Save settings
    $result = ConfigFunctions::saveSettings($settings);
    
    if ($result) {
        return json_encode([
            'status' => 'success',
            'message' => 'Settings saved successfully'
        ]);
    } else {
        throw new Exception('Failed to save settings');
    }
} catch (Exception $e) {
    return json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
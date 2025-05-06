<?php
/**
 * Save Settings Script
 * 
 * This script handles saving the Amazon SES settings.
 */

// Include configuration
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    // Get settings from POST data
    $settings = [
        'awsRegion' => isset($_POST['awsRegion']) ? trim($_POST['awsRegion']) : '',
        'awsAccessKey' => isset($_POST['awsAccessKey']) ? trim($_POST['awsAccessKey']) : '',
        'awsSecretKey' => isset($_POST['awsSecretKey']) ? trim($_POST['awsSecretKey']) : '',
        'batchSize' => isset($_POST['batchSize']) ? (int) $_POST['batchSize'] : 10,
        'delayBetweenBatches' => isset($_POST['delayBetweenBatches']) ? (int) $_POST['delayBetweenBatches'] : 1
    ];
    
    // Validate settings
    if (empty($settings['awsRegion'])) {
        throw new Exception('AWS Region is required');
    }
    
    if (empty($settings['awsAccessKey'])) {
        throw new Exception('AWS Access Key is required');
    }
    
    if (empty($settings['awsSecretKey'])) {
        throw new Exception('AWS Secret Key is required');
    }
    
    // Validate batch size (1-50)
    if ($settings['batchSize'] < 1 || $settings['batchSize'] > 50) {
        throw new Exception('Batch size must be between 1 and 50');
    }
    
    // Validate delay between batches (non-negative)
    if ($settings['delayBetweenBatches'] < 0) {
        throw new Exception('Delay between batches must be non-negative');
    }
    
    // Save settings
    if (saveSettings($settings)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Settings saved successfully'
        ]);
    } else {
        throw new Exception('Failed to save settings');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
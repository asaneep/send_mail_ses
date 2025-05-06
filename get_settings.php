<?php
/**
 * Get Settings Script
 * 
 * This script retrieves the Amazon SES settings.
 */

// Include configuration
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get settings
    $settings = getSettings();
    
    // Mask the secret key for security
    if (!empty($settings['awsSecretKey'])) {
        $length = strlen($settings['awsSecretKey']);
        $visibleChars = min(4, $length);
        $maskedLength = $length - $visibleChars;
        
        if ($maskedLength > 0) {
            $settings['awsSecretKey'] = substr($settings['awsSecretKey'], 0, $visibleChars) . str_repeat('*', $maskedLength);
        }
    }
    
    // Return settings
    echo json_encode([
        'status' => 'success',
        'settings' => $settings
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
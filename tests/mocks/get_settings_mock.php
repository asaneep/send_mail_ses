<?php
/**
 * Mock Get Settings Script for Testing
 * 
 * This script is a modified version of get_settings.php for testing.
 */

// Use our HeaderMock instead of the real header function
use Tests\Mocks\HeaderMock;
use Tests\Mocks\ConfigFunctions;

// No need to include config_functions.php as it's already included in the test

// Set content type to JSON (using our mock)
HeaderMock::capture('Content-Type: application/json');

try {
    // Get settings
    $settings = ConfigFunctions::getSettings();
    
    // Return settings
    return json_encode([
        'status' => 'success',
        'settings' => $settings
    ]);
} catch (Exception $e) {
    return json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
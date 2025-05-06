<?php
/**
 * Test Bootstrap File
 * 
 * This file is loaded before running tests to set up the testing environment.
 */

// Include Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define a constant for the test environment
define('TESTING', true);

// Define test file paths for backward compatibility
if (!defined('TEST_SETTINGS_FILE')) {
    define('TEST_SETTINGS_FILE', __DIR__ . '/test_settings.json');
}
if (!defined('TEST_DB_FILE')) {
    define('TEST_DB_FILE', __DIR__ . '/test_email_history.db');
}

// Create a test settings file if it doesn't exist
if (!file_exists(TEST_SETTINGS_FILE)) {
    $testSettings = [
        'awsRegion' => 'us-east-1',
        'awsAccessKey' => 'test-key',
        'awsSecretKey' => 'test-secret',
        'batchSize' => 5,
        'delayBetweenBatches' => 0
    ];
    
    file_put_contents(TEST_SETTINGS_FILE, json_encode($testSettings, JSON_PRETTY_PRINT));
}

// Function to create a clean test database
function createTestDatabase() {
    // Remove existing test database if it exists
    if (file_exists(TEST_DB_FILE)) {
        unlink(TEST_DB_FILE);
    }
    
    // Create a new test database
    $db = new PDO('sqlite:' . TEST_DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create email_history table
    $db->exec('CREATE TABLE IF NOT EXISTS email_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date DATETIME NOT NULL,
        sender TEXT NOT NULL,
        subject TEXT NOT NULL,
        recipients TEXT NOT NULL,
        message TEXT NOT NULL,
        status TEXT NOT NULL,
        details TEXT
    )');
    
    return $db;
}
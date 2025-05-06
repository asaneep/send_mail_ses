<?php

namespace Tests;

use Tests\Mocks\ConfigFunctions;

/**
 * Test case for settings functionality
 */
class SettingsTest extends TestCase
{
    /**
     * Test settings file path
     */
    protected $settingsFile;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define the test settings file path
        $this->settingsFile = __DIR__ . '/test_settings.json';
        
        // Initialize ConfigFunctions with test paths
        ConfigFunctions::init(
            $this->settingsFile,
            __DIR__ . '/test_email_history.db'
        );
    }
    
    /**
     * Clean up after the test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Remove the test settings file
        if (file_exists($this->settingsFile)) {
            unlink($this->settingsFile);
        }
    }
    
    /**
     * Test saving settings
     */
    public function testSaveSettings()
    {
        // Mock the $_POST superglobal
        $_POST = [
            'awsRegion' => 'eu-west-1',
            'awsAccessKey' => 'test-access-key',
            'awsSecretKey' => 'test-secret-key',
            'batchSize' => '20',
            'delayBetweenBatches' => '3'
        ];
        
        // Include our mock save_settings.php file
        $output = require __DIR__ . '/mocks/save_settings_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the settings file exists
        $this->assertFileExists($this->settingsFile);
        
        // Read the file contents
        $fileContents = file_get_contents($this->settingsFile);
        $savedSettings = json_decode($fileContents, true);
        
        // Assert that the saved settings match the input
        $this->assertEquals($_POST['awsRegion'], $savedSettings['awsRegion']);
        $this->assertEquals($_POST['awsAccessKey'], $savedSettings['awsAccessKey']);
        $this->assertEquals($_POST['awsSecretKey'], $savedSettings['awsSecretKey']);
        $this->assertEquals((int)$_POST['batchSize'], $savedSettings['batchSize']);
        $this->assertEquals((int)$_POST['delayBetweenBatches'], $savedSettings['delayBetweenBatches']);
    }
    
    /**
     * Test saving settings with invalid data
     */
    public function testSaveSettingsWithInvalidData()
    {
        // Mock the $_POST superglobal with invalid data
        $_POST = [
            'awsRegion' => 'eu-west-1',
            'awsAccessKey' => 'test-access-key',
            'awsSecretKey' => 'test-secret-key',
            'batchSize' => 'not-a-number',
            'delayBetweenBatches' => '3'
        ];
        
        // Include our mock save_settings.php file
        $output = require __DIR__ . '/mocks/save_settings_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is error
        $this->assertEquals('error', $result['status']);
    }
    
    /**
     * Test getting settings
     */
    public function testGetSettings()
    {
        // Create a test settings file
        $testSettings = [
            'awsRegion' => 'eu-west-1',
            'awsAccessKey' => 'test-access-key',
            'awsSecretKey' => 'test-secret-key',
            'batchSize' => 15,
            'delayBetweenBatches' => 2
        ];
        
        file_put_contents($this->settingsFile, json_encode($testSettings, JSON_PRETTY_PRINT));
        
        // Include our mock get_settings.php file
        $output = require __DIR__ . '/mocks/get_settings_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the settings array exists
        $this->assertArrayHasKey('settings', $result);
        $this->assertIsArray($result['settings']);
        
        // Assert that the settings match the test settings
        $settings = $result['settings'];
        $this->assertEquals($testSettings['awsRegion'], $settings['awsRegion']);
        $this->assertEquals($testSettings['awsAccessKey'], $settings['awsAccessKey']);
        $this->assertEquals($testSettings['awsSecretKey'], $settings['awsSecretKey']);
        $this->assertEquals($testSettings['batchSize'], $settings['batchSize']);
        $this->assertEquals($testSettings['delayBetweenBatches'], $settings['delayBetweenBatches']);
    }
    
    /**
     * Test getting settings when file doesn't exist
     */
    public function testGetSettingsWhenFileDoesntExist()
    {
        // Make sure the file doesn't exist
        if (file_exists($this->settingsFile)) {
            unlink($this->settingsFile);
        }
        
        // Include our mock get_settings.php file
        $output = require __DIR__ . '/mocks/get_settings_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the settings array exists
        $this->assertArrayHasKey('settings', $result);
        $this->assertIsArray($result['settings']);
        
        // Assert that the settings have default values
        $settings = $result['settings'];
        $this->assertEquals('', $settings['awsRegion']);
        $this->assertEquals('', $settings['awsAccessKey']);
        $this->assertEquals('', $settings['awsSecretKey']);
        $this->assertEquals(10, $settings['batchSize']);
        $this->assertEquals(1, $settings['delayBetweenBatches']);
    }
}
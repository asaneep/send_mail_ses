<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Mocks\ConfigFunctions;

/**
 * Test case for configuration functions
 */
class ConfigTest extends TestCase
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
        
        // Create a test settings file
        $testSettings = [
            'awsRegion' => 'eu-west-1',
            'awsAccessKey' => 'test-access-key',
            'awsSecretKey' => 'test-secret-key',
            'batchSize' => 15,
            'delayBetweenBatches' => 2
        ];
        
        file_put_contents($this->settingsFile, json_encode($testSettings, JSON_PRETTY_PRINT));
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
     * Test getAWSConfig function
     */
    public function testGetAWSConfig()
    {
        // Get the AWS configuration
        $config = ConfigFunctions::getAWSConfig();
        
        // Assert that the configuration is an array
        $this->assertIsArray($config);
        
        // Assert that the configuration contains the expected keys
        $this->assertArrayHasKey('region', $config);
        $this->assertArrayHasKey('version', $config);
        $this->assertArrayHasKey('credentials', $config);
        $this->assertArrayHasKey('batch_size', $config);
        $this->assertArrayHasKey('delay_between_batches', $config);
        
        // Assert that the credentials array contains the expected keys
        $this->assertArrayHasKey('key', $config['credentials']);
        $this->assertArrayHasKey('secret', $config['credentials']);
        
        // Assert that the values match the test settings
        $this->assertEquals('us-east-1', $config['region']);
        $this->assertEquals('test-key', $config['credentials']['key']);
        $this->assertEquals('test-secret', $config['credentials']['secret']);
        $this->assertEquals(15, $config['batch_size']);
        $this->assertEquals(2, $config['delay_between_batches']);
    }
    
    /**
     * Test getAWSConfig with environment variables
     */
    public function testGetAWSConfigWithEnvironmentVariables()
    {
        // Set environment variables
        putenv('AWS_REGION=us-west-2');
        putenv('AWS_ACCESS_KEY_ID=env-access-key');
        putenv('AWS_SECRET_ACCESS_KEY=env-secret-key');
        
        // Get the AWS configuration
        $config = ConfigFunctions::getAWSConfig();
        
        // Assert that the values match the environment variables (which should override the file settings)
        $this->assertEquals('us-west-2', $config['region']);
        $this->assertEquals('env-access-key', $config['credentials']['key']);
        $this->assertEquals('env-secret-key', $config['credentials']['secret']);
        
        // Clean up environment variables
        putenv('AWS_REGION');
        putenv('AWS_ACCESS_KEY_ID');
        putenv('AWS_SECRET_ACCESS_KEY');
    }
    
    /**
     * Test saveSettings function
     */
    public function testSaveSettings()
    {
        // Create new settings
        $newSettings = [
            'awsRegion' => 'ap-southeast-1',
            'awsAccessKey' => 'new-access-key',
            'awsSecretKey' => 'new-secret-key',
            'batchSize' => 20,
            'delayBetweenBatches' => 3
        ];
        
        // Save the settings
        $result = ConfigFunctions::saveSettings($newSettings);
        
        // Assert that the function returned true
        $this->assertTrue($result);
        
        // Assert that the file exists
        $this->assertFileExists($this->settingsFile);
        
        // Read the file contents
        $fileContents = file_get_contents($this->settingsFile);
        $savedSettings = json_decode($fileContents, true);
        
        // Assert that the saved settings match the new settings
        $this->assertEquals($newSettings['awsRegion'], $savedSettings['awsRegion']);
        $this->assertEquals($newSettings['awsAccessKey'], $savedSettings['awsAccessKey']);
        $this->assertEquals($newSettings['awsSecretKey'], $savedSettings['awsSecretKey']);
        $this->assertEquals($newSettings['batchSize'], $savedSettings['batchSize']);
        $this->assertEquals($newSettings['delayBetweenBatches'], $savedSettings['delayBetweenBatches']);
    }
    
    /**
     * Test saveSettings with invalid data
     */
    public function testSaveSettingsWithInvalidData()
    {
        // Try to save invalid settings
        $result = ConfigFunctions::saveSettings('not an array');
        
        // Assert that the function returned false
        $this->assertFalse($result);
    }
    
    /**
     * Test getSettings function
     */
    public function testGetSettings()
    {
        // Get the settings
        $settings = ConfigFunctions::getSettings();
        
        // Assert that the settings is an array
        $this->assertIsArray($settings);
        
        // Assert that the settings contains the expected keys
        $this->assertArrayHasKey('awsRegion', $settings);
        $this->assertArrayHasKey('awsAccessKey', $settings);
        $this->assertArrayHasKey('awsSecretKey', $settings);
        $this->assertArrayHasKey('batchSize', $settings);
        $this->assertArrayHasKey('delayBetweenBatches', $settings);
        
        // Assert that the values match the test settings
        $this->assertEquals('eu-west-1', $settings['awsRegion']);
        $this->assertEquals('test-access-key', $settings['awsAccessKey']);
        $this->assertEquals('test-secret-key', $settings['awsSecretKey']);
        $this->assertEquals(15, $settings['batchSize']);
        $this->assertEquals(2, $settings['delayBetweenBatches']);
    }
    
    /**
     * Test isAWSConfigured function
     */
    public function testIsAWSConfigured()
    {
        // Test with valid credentials
        $this->assertTrue(ConfigFunctions::isAWSConfigured());
        
        // Create settings with empty credentials
        $emptySettings = [
            'awsRegion' => 'eu-west-1',
            'awsAccessKey' => '',
            'awsSecretKey' => '',
            'batchSize' => 15,
            'delayBetweenBatches' => 2
        ];
        
        // Save the empty settings
        ConfigFunctions::saveSettings($emptySettings);
        
        // Test with empty credentials
        $this->assertFalse(ConfigFunctions::isAWSConfigured());
    }
}
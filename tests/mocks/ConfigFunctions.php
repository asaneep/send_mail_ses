<?php

namespace Tests\Mocks;

/**
 * Mock config functions for testing
 */
class ConfigFunctions
{
    /**
     * @var string Test settings file path
     */
    private static $settingsFile;
    
    /**
     * @var string Test database file path
     */
    private static $dbFile;
    
    /**
     * Initialize the mock config functions
     * 
     * @param string $settingsFile Test settings file path
     * @param string $dbFile Test database file path
     */
    public static function init($settingsFile, $dbFile)
    {
        self::$settingsFile = $settingsFile;
        self::$dbFile = $dbFile;
    }
    
    /**
     * Get AWS SES configuration
     * 
     * @return array AWS configuration array
     */
    public static function getAWSConfig()
    {
        // Default configuration
        $config = [
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key' => '',
                'secret' => ''
            ],
            'batch_size' => 10,
            'delay_between_batches' => 1
        ];
        
        // Try to load from settings file
        if (file_exists(self::$settingsFile)) {
            $settings = json_decode(file_get_contents(self::$settingsFile), true);
            
            if (is_array($settings)) {
                if (!empty($settings['awsRegion'])) {
                    $config['region'] = $settings['awsRegion'];
                }
                
                if (!empty($settings['awsAccessKey'])) {
                    $config['credentials']['key'] = $settings['awsAccessKey'];
                }
                
                if (!empty($settings['awsSecretKey'])) {
                    $config['credentials']['secret'] = $settings['awsSecretKey'];
                }
                
                if (!empty($settings['batchSize']) && is_numeric($settings['batchSize'])) {
                    $config['batch_size'] = (int) $settings['batchSize'];
                }
                
                if (!empty($settings['delayBetweenBatches']) && is_numeric($settings['delayBetweenBatches'])) {
                    $config['delay_between_batches'] = (int) $settings['delayBetweenBatches'];
                }
            }
        }
        
        // Check for environment variables (these override file settings)
        if (getenv('AWS_REGION')) {
            $config['region'] = getenv('AWS_REGION');
        }
        
        if (getenv('AWS_ACCESS_KEY_ID')) {
            $config['credentials']['key'] = getenv('AWS_ACCESS_KEY_ID');
        }
        
        if (getenv('AWS_SECRET_ACCESS_KEY')) {
            $config['credentials']['secret'] = getenv('AWS_SECRET_ACCESS_KEY');
        }
        
        return $config;
    }
    
    /**
     * Save settings to the settings file
     * 
     * @param array $settings Settings to save
     * @return bool True if successful, false otherwise
     */
    public static function saveSettings($settings)
    {
        try {
            // Ensure we have an array
            if (!is_array($settings)) {
                return false;
            }
            
            // Filter out any unwanted keys
            $allowedKeys = ['awsRegion', 'awsAccessKey', 'awsSecretKey', 'batchSize', 'delayBetweenBatches'];
            $filteredSettings = array_intersect_key($settings, array_flip($allowedKeys));
            
            // Convert to JSON
            $json = json_encode($filteredSettings, JSON_PRETTY_PRINT);
            
            // Write to file
            return file_put_contents(self::$settingsFile, $json) !== false;
        } catch (\Exception $e) {
            error_log('Error saving settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get settings from the settings file
     * 
     * @return array Settings array
     */
    public static function getSettings()
    {
        $settings = [
            'awsRegion' => '',
            'awsAccessKey' => '',
            'awsSecretKey' => '',
            'batchSize' => 10,
            'delayBetweenBatches' => 1
        ];
        
        if (file_exists(self::$settingsFile)) {
            $fileSettings = json_decode(file_get_contents(self::$settingsFile), true);
            
            if (is_array($fileSettings)) {
                $settings = array_merge($settings, $fileSettings);
            }
        }
        
        return $settings;
    }
    
    /**
     * Check if AWS credentials are configured
     * 
     * @return bool True if configured, false otherwise
     */
    public static function isAWSConfigured()
    {
        $config = self::getAWSConfig();
        return !empty($config['credentials']['key']) && !empty($config['credentials']['secret']);
    }
    
    /**
     * Create database tables if they don't exist
     */
    public static function initializeDatabase()
    {
        $dbFile = self::$dbFile;
        $isNewDb = !file_exists($dbFile);
        
        try {
            $db = new \PDO('sqlite:' . $dbFile);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            if ($isNewDb) {
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
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log('Database initialization error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save email history to database
     * 
     * @param array $emailDetails Email details
     * @return int Email ID
     */
    public static function saveEmailHistory($emailDetails)
    {
        try {
            $db = new \PDO('sqlite:' . self::$dbFile);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare('INSERT INTO email_history (date, sender, subject, recipients, message, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $emailDetails['date'],
                $emailDetails['sender'],
                $emailDetails['subject'],
                $emailDetails['recipients'],
                $emailDetails['message'],
                $emailDetails['status'],
                $emailDetails['details']
            ]);
            
            return $db->lastInsertId();
        } catch (\PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update email history status
     * 
     * @param int $emailId Email ID
     * @param string $status Status
     * @param array $details Details
     * @return bool True if successful, false otherwise
     */
    public static function updateEmailHistory($emailId, $status, $details)
    {
        try {
            $db = new \PDO('sqlite:' . self::$dbFile);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare('UPDATE email_history SET status = ?, details = ? WHERE id = ?');
            $stmt->execute([
                $status,
                json_encode($details),
                $emailId
            ]);
            
            return true;
        } catch (\PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            return false;
        }
    }
}
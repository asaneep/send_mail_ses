<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Mocks\ConfigFunctions;
use Tests\Mocks\HeaderMock;

/**
 * Base TestCase for all tests
 */
class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize ConfigFunctions with test paths
        ConfigFunctions::init(
            __DIR__ . '/test_settings.json',
            __DIR__ . '/test_email_history.db'
        );
        
        // Create a clean test database
        $this->createTestDatabase();
    }
    
    /**
     * Create a clean test database
     *
     * @return \PDO Database connection
     */
    protected function createTestDatabase()
    {
        $dbFile = __DIR__ . '/test_email_history.db';
        
        // Remove existing test database if it exists
        if (file_exists($dbFile)) {
            unlink($dbFile);
        }
        
        // Create a new test database
        $db = new \PDO('sqlite:' . $dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
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
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up any test files if needed
        $dbFile = __DIR__ . '/test_email_history.db';
        if (file_exists($dbFile) && !getenv('KEEP_TEST_DB')) {
            unlink($dbFile);
        }
    }
    
    /**
     * Create a mock for AWS SesClient
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockSesClient()
    {
        $mockSesClient = $this->getMockBuilder('Aws\Ses\SesClient')
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        return $mockSesClient;
    }
    
    /**
     * Create a mock for PHPMailer
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMockPHPMailer()
    {
        $mockPHPMailer = $this->getMockBuilder('PHPMailer\PHPMailer\PHPMailer')
            ->disableOriginalConstructor()
            ->onlyMethods(['setFrom', 'addAddress', 'isHTML', 'preSend', 'getSentMIMEMessage', 'addAttachment'])
            ->getMock();
            
        return $mockPHPMailer;
    }
    
    /**
     * Create a test PDO connection to the test database
     * 
     * @return \PDO
     */
    protected function createTestPDO()
    {
        $dbFile = __DIR__ . '/test_email_history.db';
        $db = new \PDO('sqlite:' . $dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $db;
    }
}
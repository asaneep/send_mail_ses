<?php

namespace Tests;

use Tests\Mocks\ConfigFunctions;

/**
 * Test case for database operations
 */
class DatabaseTest extends TestCase
{
    /**
     * Test database file path
     */
    protected $dbFile;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define the test database file path
        $this->dbFile = __DIR__ . '/test_email_history.db';
        
        // Initialize ConfigFunctions with test paths
        ConfigFunctions::init(
            __DIR__ . '/test_settings.json',
            $this->dbFile
        );
        
        // Create a clean test database
        $this->createTestDatabase();
    }
    
    /**
     * Clean up after the test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Remove the test database file
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
    }
    
    /**
     * Test database initialization
     */
    public function testInitializeDatabase()
    {
        // Call the function to initialize the database
        $result = ConfigFunctions::initializeDatabase();
        
        // Assert that the function returned true
        $this->assertTrue($result);
        
        // Assert that the database file exists
        $this->assertFileExists($this->dbFile);
        
        // Connect to the database
        $db = new \PDO('sqlite:' . $this->dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Check if the email_history table exists
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='email_history'");
        $table = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Assert that the table exists
        $this->assertNotEmpty($table);
        
        // Check the table structure
        $stmt = $db->query("PRAGMA table_info(email_history)");
        $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Assert that the table has the expected columns
        $this->assertCount(8, $columns);
        
        // Check column names
        $columnNames = array_column($columns, 'name');
        $this->assertContains('id', $columnNames);
        $this->assertContains('date', $columnNames);
        $this->assertContains('sender', $columnNames);
        $this->assertContains('subject', $columnNames);
        $this->assertContains('recipients', $columnNames);
        $this->assertContains('message', $columnNames);
        $this->assertContains('status', $columnNames);
        $this->assertContains('details', $columnNames);
    }
    
    /**
     * Test saveEmailHistory function
     */
    public function testSaveEmailHistory()
    {
        // Create email details
        $emailDetails = [
            'date' => '2023-01-01 12:00:00',
            'sender' => 'sender@example.com',
            'subject' => 'Test Subject',
            'recipients' => 'recipient1@example.com, recipient2@example.com',
            'message' => 'Test message content',
            'status' => 'pending',
            'details' => json_encode([])
        ];
        
        // Save the email history
        $emailId = ConfigFunctions::saveEmailHistory($emailDetails);
        
        // Assert that the function returned a valid ID
        $this->assertIsNumeric($emailId);
        $this->assertGreaterThan(0, $emailId);
        
        // Connect to the database
        $db = new \PDO('sqlite:' . $this->dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Retrieve the saved record
        $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
        $stmt->execute([$emailId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Assert that the record exists
        $this->assertNotEmpty($record);
        
        // Assert that the record values match the input
        $this->assertEquals($emailDetails['date'], $record['date']);
        $this->assertEquals($emailDetails['sender'], $record['sender']);
        $this->assertEquals($emailDetails['subject'], $record['subject']);
        $this->assertEquals($emailDetails['recipients'], $record['recipients']);
        $this->assertEquals($emailDetails['message'], $record['message']);
        $this->assertEquals($emailDetails['status'], $record['status']);
        $this->assertEquals($emailDetails['details'], $record['details']);
    }
    
    /**
     * Test updateEmailHistory function
     */
    public function testUpdateEmailHistory()
    {
        // Create email details
        $emailDetails = [
            'date' => '2023-01-01 12:00:00',
            'sender' => 'sender@example.com',
            'subject' => 'Test Subject',
            'recipients' => 'recipient1@example.com, recipient2@example.com',
            'message' => 'Test message content',
            'status' => 'pending',
            'details' => json_encode([])
        ];
        
        // Save the email history
        $emailId = ConfigFunctions::saveEmailHistory($emailDetails);
        
        // Create details for update
        $details = [
            ['email' => 'recipient1@example.com', 'status' => 'success', 'message' => 'Email sent successfully'],
            ['email' => 'recipient2@example.com', 'status' => 'error', 'message' => 'Failed to send email']
        ];
        
        // Update the email history
        $result = ConfigFunctions::updateEmailHistory($emailId, 'partial', $details);
        
        // Assert that the function returned true
        $this->assertTrue($result);
        
        // Connect to the database
        $db = new \PDO('sqlite:' . $this->dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Retrieve the updated record
        $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
        $stmt->execute([$emailId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Assert that the record exists
        $this->assertNotEmpty($record);
        
        // Assert that the status was updated
        $this->assertEquals('partial', $record['status']);
        
        // Assert that the details were updated
        $savedDetails = json_decode($record['details'], true);
        $this->assertEquals($details, $savedDetails);
    }
}
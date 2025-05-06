<?php

namespace Tests;

use Tests\Mocks\HeaderMock;

/**
 * Test case for email history functionality
 */
class HistoryTest extends TestCase
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
        $this->dbFile = TEST_DB_FILE;
        
        // Initialize ConfigFunctions with test paths
        \Tests\Mocks\ConfigFunctions::init(
            __DIR__ . '/test_settings.json',
            $this->dbFile
        );
        
        // Create a clean test database
        $db = createTestDatabase();
        
        // Insert some test data
        $this->insertTestData($db);
        
        // Clear any mock headers
        HeaderMock::clear();
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
     * Insert test data into the database
     * 
     * @param \PDO $db Database connection
     */
    protected function insertTestData($db)
    {
        // Insert 15 test records
        for ($i = 1; $i <= 15; $i++) {
            $date = date('Y-m-d H:i:s', strtotime("-$i hours"));
            $status = $i % 3 === 0 ? 'error' : ($i % 3 === 1 ? 'success' : 'partial');
            
            $stmt = $db->prepare('INSERT INTO email_history (date, sender, subject, recipients, message, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $date,
                "sender$i@example.com",
                "Test Subject $i",
                "recipient$i@example.com",
                "Test message content $i",
                $status,
                json_encode([
                    ['email' => "recipient$i@example.com", 'status' => $status, 'message' => 'Test message']
                ])
            ]);
        }
    }
    
    /**
     * Test retrieving email history
     */
    public function testGetEmailHistory()
    {
        // Mock the $_GET superglobal
        $_GET['page'] = 1;
        
        // Include our mock get_history.php file
        $output = require __DIR__ . '/mocks/get_history_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the history array exists and has 10 items (default per page)
        $this->assertArrayHasKey('history', $result);
        $this->assertIsArray($result['history']);
        $this->assertCount(10, $result['history']);
        
        // Assert that the pagination information is correct
        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(1, $result['page']);
        
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertEquals(2, $result['totalPages']);
        
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertEquals(15, $result['totalCount']);
        
        // Check the first item in the history
        $firstItem = $result['history'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('date', $firstItem);
        $this->assertArrayHasKey('sender', $firstItem);
        $this->assertArrayHasKey('subject', $firstItem);
        $this->assertArrayHasKey('recipients', $firstItem);
        $this->assertArrayHasKey('status', $firstItem);
    }
    
    /**
     * Test retrieving email history with pagination
     */
    public function testGetEmailHistoryWithPagination()
    {
        // Mock the $_GET superglobal for page 2
        $_GET['page'] = 2;
        
        // Include our mock get_history.php file
        $output = require __DIR__ . '/mocks/get_history_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the history array exists and has 5 items (remaining items on page 2)
        $this->assertArrayHasKey('history', $result);
        $this->assertIsArray($result['history']);
        $this->assertCount(5, $result['history']);
        
        // Assert that the pagination information is correct
        $this->assertArrayHasKey('page', $result);
        $this->assertEquals(2, $result['page']);
    }
    
    /**
     * Test retrieving email details
     */
    public function testGetEmailDetails()
    {
        // Mock the $_GET superglobal
        $_GET['id'] = 1;
        
        // Include our mock get_email_details.php file
        $output = require __DIR__ . '/mocks/get_email_details_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the details array exists
        $this->assertArrayHasKey('details', $result);
        $this->assertIsArray($result['details']);
        
        // Check the details
        $details = $result['details'];
        $this->assertArrayHasKey('id', $details);
        $this->assertEquals(1, $details['id']);
        
        $this->assertArrayHasKey('sender', $details);
        $this->assertArrayHasKey('subject', $details);
        $this->assertArrayHasKey('recipients', $details);
        $this->assertArrayHasKey('message', $details);
        $this->assertArrayHasKey('status', $details);
        $this->assertArrayHasKey('details', $details);
    }
    
    /**
     * Test retrieving email details with invalid ID
     */
    public function testGetEmailDetailsWithInvalidId()
    {
        // Mock the $_GET superglobal with an invalid ID
        $_GET['id'] = 999;
        
        // Include our mock get_email_details.php file
        $output = require __DIR__ . '/mocks/get_email_details_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is error
        $this->assertEquals('error', $result['status']);
        
        // Assert that the error message exists
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Email not found', $result['message']);
    }
}
<?php

namespace Tests;

use Tests\Mocks\HeaderMock;

/**
 * Test case for email resending functionality
 */
class ResendTest extends TestCase
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
        
        // Insert a test email record
        $this->insertTestEmail($db);
        
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
     * Insert a test email record into the database
     * 
     * @param \PDO $db Database connection
     * @return int Email ID
     */
    protected function insertTestEmail($db)
    {
        $stmt = $db->prepare('INSERT INTO email_history (date, sender, subject, recipients, message, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            date('Y-m-d H:i:s'),
            'sender@example.com',
            'Test Subject',
            'recipient1@example.com, recipient2@example.com',
            'Test message content',
            'success',
            json_encode([
                ['email' => 'recipient1@example.com', 'status' => 'success', 'message' => 'Email sent successfully'],
                ['email' => 'recipient2@example.com', 'status' => 'success', 'message' => 'Email sent successfully']
            ])
        ]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Test resending an email
     */
    public function testResendEmail()
    {
        // Mock the $_GET superglobal
        $_GET['id'] = 1;
        
        // Include our mock resend_email.php file
        $output = require __DIR__ . '/mocks/resend_email_mock.php';
        
        // Decode the JSON output
        $result = json_decode($output, true);
        
        // Assert that the result is an array
        $this->assertIsArray($result);
        
        // Assert that the status is success
        $this->assertEquals('success', $result['status']);
        
        // Assert that the message contains the expected text
        $this->assertStringContainsString('Resend process started', $result['message']);
    }
    
    /**
     * Test resending an email with an invalid ID
     */
    public function testResendEmailWithInvalidId()
    {
        // Mock the $_GET superglobal with an invalid ID
        $_GET['id'] = 999;
        
        // Include our mock resend_email.php file
        $output = require __DIR__ . '/mocks/resend_email_mock.php';
        
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
    
    /**
     * Test the resend process
     * 
     * This test mocks the command execution since we can't actually run the process
     */
    public function testResendProcess()
    {
        // Create a mock for the SesClient
        $mockSesClient = $this->getMockBuilder(\Aws\Ses\SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
        
        // Set up the mock to return a successful result
        $mockResult = $this->getMockBuilder('Aws\Result')
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockResult->method('get')
            ->with('MessageId')
            ->willReturn('test-message-id');
        
        $mockSesClient->expects($this->exactly(2))
            ->method('sendRawEmail')
            ->willReturn($mockResult);
        
        // Create a mock for PHPMailer
        $mockPHPMailer = $this->getMockBuilder('PHPMailer\PHPMailer\PHPMailer')
            ->disableOriginalConstructor()
            ->onlyMethods(['setFrom', 'addAddress', 'isHTML', 'preSend', 'getSentMIMEMessage', 'addAttachment'])
            ->getMock();
        
        // Set up the mock PHPMailer
        $mockPHPMailer->expects($this->exactly(2))
            ->method('setFrom')
            ->with('sender@example.com');
        
        $mockPHPMailer->expects($this->exactly(2))
            ->method('addAddress')
            ->withConsecutive(
                ['recipient1@example.com'],
                ['recipient2@example.com']
            );
        
        $mockPHPMailer->expects($this->exactly(2))
            ->method('preSend')
            ->willReturn(true);
        
        $mockPHPMailer->expects($this->exactly(2))
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Connect to the database
        $db = new \PDO('sqlite:' . $this->dbFile);
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Get the email record
        $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
        $stmt->execute([1]);
        $email = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Assert that the email exists
        $this->assertNotEmpty($email);
        
        // Create a simulated resend process
        $this->simulateResendProcess($email, $mockSesClient, $mockPHPMailer);
        
        // Get the updated email record
        $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
        $stmt->execute([1]);
        $updatedEmail = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Assert that the status was updated
        $this->assertEquals('success', $updatedEmail['status']);
        
        // Assert that the details were updated
        $details = json_decode($updatedEmail['details'], true);
        $this->assertCount(2, $details);
        $this->assertEquals('success', $details[0]['status']);
        $this->assertEquals('success', $details[1]['status']);
    }
    
    /**
     * Simulate the resend process
     * 
     * This is a simplified version of the resend_process.php functionality
     * 
     * @param array $email Email record
     * @param \Aws\Ses\SesClient $sesClient SES client
     * @param \PHPMailer\PHPMailer\PHPMailer $phpMailer PHPMailer instance
     */
    protected function simulateResendProcess($email, $sesClient, $phpMailer)
    {
        // Parse recipients
        $recipientsList = [];
        
        // Split by comma
        $recipientsList = array_map('trim', explode(',', $email['recipients']));
        
        // Filter out invalid emails
        $recipients = [];
        foreach ($recipientsList as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $recipient;
            }
        }
        
        // Send emails
        $successCount = 0;
        $errorCount = 0;
        $details = [];
        
        foreach ($recipients as $recipient) {
            try {
                // Create email message
                $phpMailer->CharSet = 'UTF-8';
                $phpMailer->setFrom($email['sender']);
                $phpMailer->addAddress($recipient);
                $phpMailer->Subject = $email['subject'];
                
                // Determine if message is HTML
                if (strpos($email['message'], '<html') !== false || strpos($email['message'], '<body') !== false) {
                    $phpMailer->isHTML(true);
                    $phpMailer->Body = $email['message'];
                    $phpMailer->AltBody = strip_tags($email['message']);
                } else {
                    $phpMailer->Body = $email['message'];
                }
                
                // Get raw email content
                $phpMailer->preSend();
                $rawMessage = $phpMailer->getSentMIMEMessage();
                
                // Send email via Amazon SES
                $result = $sesClient->sendRawEmail([
                    'RawMessage' => [
                        'Data' => $rawMessage
                    ]
                ]);
                
                // Record success
                $successCount++;
                $details[] = [
                    'email' => $recipient,
                    'status' => 'success',
                    'message' => 'Email sent successfully',
                    'messageId' => $result->get('MessageId')
                ];
            } catch (\Exception $e) {
                // Record error
                $errorCount++;
                $details[] = [
                    'email' => $recipient,
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
        }
        
        // Update email history status
        $status = $errorCount === 0 ? 'success' : ($successCount === 0 ? 'error' : 'partial');
        
        try {
            // Connect to the database
            $db = new \PDO('sqlite:' . $this->dbFile);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Update the record
            $stmt = $db->prepare('UPDATE email_history SET status = ?, details = ? WHERE id = ?');
            $stmt->execute([
                $status,
                json_encode($details),
                $email['id']
            ]);
            
            // Close the database connection
            $stmt = null;
            $db = null;
        } catch (\PDOException $e) {
            // Log the error
            error_log('Database error: ' . $e->getMessage());
        }
    }
}
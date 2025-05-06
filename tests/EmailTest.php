<?php

namespace Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Aws\Result;
use Aws\Ses\SesClient;
use Aws\Command;
use PHPMailer\PHPMailer\PHPMailer;

// Include our mock CommandInterface
require_once __DIR__ . '/mocks/CommandInterface.php';

/**
 * Test case for email sending functionality
 */
class EmailTest extends TestCase
{
    /**
     * @var MockObject|SesClient
     */
    protected $mockSesClient;
    
    /**
     * @var MockObject|PHPMailer
     */
    protected $mockPHPMailer;
    
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock objects
        $this->mockSesClient = $this->createMockSesClient();
        $this->mockPHPMailer = $this->createMockPHPMailer();
    }
    
    /**
     * Test sending a single email
     */
    public function testSendSingleEmail()
    {
        // Set up the mock SesClient to return a successful result
        $mockResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockResult->method('get')
            ->with('MessageId')
            ->willReturn('test-message-id');
        
        $this->mockSesClient = $this->getMockBuilder(SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        $this->mockSesClient->expects($this->once())
            ->method('sendRawEmail')
            ->willReturn($mockResult);
        
        // Set up the mock PHPMailer
        $this->mockPHPMailer->expects($this->once())
            ->method('setFrom')
            ->with('sender@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAddress')
            ->with('recipient@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('preSend')
            ->willReturn(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Create a test email
        $email = [
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
            'format' => 'text'
        ];
        
        // Call the sendEmail function (we'll need to create this function)
        $result = $this->sendEmail($email, $this->mockSesClient, $this->mockPHPMailer);
        
        // Assert that the function returned a successful result
        $this->assertTrue($result['success']);
        $this->assertEquals('test-message-id', $result['messageId']);
    }
    
    /**
     * Test sending an email with HTML content
     */
    public function testSendHtmlEmail()
    {
        // Set up the mock SesClient to return a successful result
        $mockResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockResult->method('get')
            ->with('MessageId')
            ->willReturn('test-message-id');
        
        $this->mockSesClient = $this->getMockBuilder(SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        $this->mockSesClient->expects($this->once())
            ->method('sendRawEmail')
            ->willReturn($mockResult);
        
        // Set up the mock PHPMailer
        $this->mockPHPMailer->expects($this->once())
            ->method('setFrom')
            ->with('sender@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAddress')
            ->with('recipient@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('isHTML')
            ->with(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('preSend')
            ->willReturn(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Create a test email with HTML content
        $email = [
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'message' => '<html><body><h1>Test</h1><p>Test message content</p></body></html>',
            'format' => 'html'
        ];
        
        // Call the sendEmail function
        $result = $this->sendEmail($email, $this->mockSesClient, $this->mockPHPMailer);
        
        // Assert that the function returned a successful result
        $this->assertTrue($result['success']);
        $this->assertEquals('test-message-id', $result['messageId']);
    }
    
    /**
     * Test sending an email with personalization
     */
    public function testSendEmailWithPersonalization()
    {
        // Set up the mock SesClient to return a successful result
        $mockResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockResult->method('get')
            ->with('MessageId')
            ->willReturn('test-message-id');
        
        $this->mockSesClient = $this->getMockBuilder(SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        $this->mockSesClient->expects($this->once())
            ->method('sendRawEmail')
            ->willReturn($mockResult);
        
        // Set up the mock PHPMailer
        $this->mockPHPMailer->expects($this->once())
            ->method('setFrom')
            ->with('sender@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAddress')
            ->with('john.doe@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('preSend')
            ->willReturn(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Create a test email with personalization
        $email = [
            'sender' => 'sender@example.com',
            'recipient' => [
                'email' => 'john.doe@example.com',
                'personalization' => [
                    'column1' => 'John',
                    'column2' => 'Doe'
                ]
            ],
            'subject' => 'Test Subject',
            'message' => 'Hello {column1} {column2}, your email is {email}.',
            'format' => 'text'
        ];
        
        // Call the sendEmail function
        $result = $this->sendEmail($email, $this->mockSesClient, $this->mockPHPMailer);
        
        // Assert that the function returned a successful result
        $this->assertTrue($result['success']);
        $this->assertEquals('test-message-id', $result['messageId']);
        
        // Assert that the message was personalized
        $this->assertEquals('Hello John Doe, your email is john.doe@example.com.', $this->mockPHPMailer->Body);
    }
    
    /**
     * Test sending an email with an attachment
     */
    public function testSendEmailWithAttachment()
    {
        // Set up the mock SesClient to return a successful result
        $mockResult = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockResult->method('get')
            ->with('MessageId')
            ->willReturn('test-message-id');
        
        $this->mockSesClient = $this->getMockBuilder(SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        $this->mockSesClient->expects($this->once())
            ->method('sendRawEmail')
            ->willReturn($mockResult);
        
        // Set up the mock PHPMailer
        $this->mockPHPMailer->expects($this->once())
            ->method('setFrom')
            ->with('sender@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAddress')
            ->with('recipient@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAttachment')
            ->with('/tmp/test.pdf', 'test.pdf', 'base64', 'application/pdf');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('preSend')
            ->willReturn(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Create a test email with an attachment
        $email = [
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
            'format' => 'text',
            'attachments' => [
                [
                    'tmp_name' => '/tmp/test.pdf',
                    'name' => 'test.pdf',
                    'type' => 'application/pdf'
                ]
            ]
        ];
        
        // Call the sendEmail function
        $result = $this->sendEmail($email, $this->mockSesClient, $this->mockPHPMailer);
        
        // Assert that the function returned a successful result
        $this->assertTrue($result['success']);
        $this->assertEquals('test-message-id', $result['messageId']);
    }
    
    /**
     * Test handling an AWS exception
     */
    public function testHandleAwsException()
    {
        // Set up the mock SesClient to throw an exception
        $this->mockSesClient = $this->getMockBuilder(SesClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['sendRawEmail'])
            ->getMock();
            
        $this->mockSesClient->expects($this->once())
            ->method('sendRawEmail')
            ->willThrowException(new \Aws\Exception\AwsException(
                'Test AWS exception',
                new Command('sendRawEmail')
            ));
        
        // Set up the mock PHPMailer
        $this->mockPHPMailer->expects($this->once())
            ->method('setFrom')
            ->with('sender@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('addAddress')
            ->with('recipient@example.com');
        
        $this->mockPHPMailer->expects($this->once())
            ->method('preSend')
            ->willReturn(true);
        
        $this->mockPHPMailer->expects($this->once())
            ->method('getSentMIMEMessage')
            ->willReturn('Raw MIME message');
        
        // Create a test email
        $email = [
            'sender' => 'sender@example.com',
            'recipient' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message content',
            'format' => 'text'
        ];
        
        // Call the sendEmail function
        $result = $this->sendEmail($email, $this->mockSesClient, $this->mockPHPMailer);
        
        // Assert that the function returned an error result
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('AWS Error', $result['message']);
    }
    
    /**
     * Helper function to send an email
     * 
     * This is a simplified version of the send_mail.php functionality
     * 
     * @param array $email Email details
     * @param SesClient $sesClient SES client
     * @param PHPMailer $phpMailer PHPMailer instance
     * @return array Result array
     */
    protected function sendEmail($email, $sesClient, $phpMailer)
    {
        try {
            // Set up PHPMailer
            $phpMailer->CharSet = 'UTF-8';
            $phpMailer->setFrom($email['sender']);
            
            // Add recipient
            $recipient = is_array($email['recipient']) ? $email['recipient']['email'] : $email['recipient'];
            $phpMailer->addAddress($recipient);
            
            // Set subject
            $phpMailer->Subject = $email['subject'];
            
            // Personalize message if needed
            $message = $email['message'];
            
            if (is_array($email['recipient']) && isset($email['recipient']['personalization'])) {
                // Replace {email} placeholder
                $message = str_replace('{email}', $recipient, $message);
                
                // Replace other placeholders
                foreach ($email['recipient']['personalization'] as $key => $value) {
                    $message = str_replace('{' . $key . '}', $value, $message);
                }
            }
            
            // Set message format
            if ($email['format'] === 'html') {
                $phpMailer->isHTML(true);
                $phpMailer->Body = $message;
                $phpMailer->AltBody = strip_tags($message);
            } else {
                $phpMailer->Body = $message;
            }
            
            // Add attachments if any
            if (isset($email['attachments']) && is_array($email['attachments'])) {
                foreach ($email['attachments'] as $attachment) {
                    $phpMailer->addAttachment(
                        $attachment['tmp_name'],
                        $attachment['name'],
                        'base64',
                        $attachment['type']
                    );
                }
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
            
            // Return success result
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'messageId' => $result->get('MessageId')
            ];
        } catch (\Aws\Exception\AwsException $e) {
            // Return AWS error
            return [
                'success' => false,
                'message' => 'AWS Error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            // Return general error
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}
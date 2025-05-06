<?php
/**
 * Resend Process Script
 * 
 * This script handles the background processing of resending emails.
 * It is called by resend_email.php and runs in the background.
 */

// Set maximum execution time to 0 (no limit) for long-running processes
set_time_limit(0);

// Include configuration
require_once 'config.php';

// Check if Composer autoloader exists
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    error_log('AWS SDK not installed. Please run "composer install" to install dependencies.');
    exit(1);
}

// Import AWS SDK classes
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use PHPMailer\PHPMailer\PHPMailer;

// Check if email ID is provided
if ($argc < 2 || !is_numeric($argv[1])) {
    error_log('Invalid email ID');
    exit(1);
}

$emailId = (int) $argv[1];

try {
    // Connect to database
    $db = new PDO('sqlite:' . __DIR__ . '/email_history.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get email details
    $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
    $stmt->execute([$emailId]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        throw new Exception('Email not found');
    }
    
    // Get AWS configuration
    $awsConfig = getAWSConfig();
    
    // Create SES client
    $sesClient = new SesClient([
        'version' => $awsConfig['version'],
        'region' => $awsConfig['region'],
        'credentials' => [
            'key' => $awsConfig['credentials']['key'],
            'secret' => $awsConfig['credentials']['secret']
        ]
    ]);
    
    // Parse recipients
    $recipientsList = [];
    
    // Check if recipients contains comma or is a JSON array
    if (strpos($email['recipients'], ',') !== false) {
        // Split by comma
        $recipientsList = array_map('trim', explode(',', $email['recipients']));
    } else if (substr($email['recipients'], 0, 1) === '[' && substr($email['recipients'], -1) === ']') {
        // Try to parse as JSON
        $parsed = json_decode($email['recipients'], true);
        if (is_array($parsed)) {
            $recipientsList = $parsed;
        } else {
            // Treat as a single recipient
            $recipientsList = [$email['recipients']];
        }
    } else {
        // Treat as a single recipient
        $recipientsList = [$email['recipients']];
    }
    
    // Filter out invalid emails
    $recipients = [];
    foreach ($recipientsList as $recipient) {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $recipients[] = $recipient;
        }
    }
    
    // Check if we have any valid recipients
    if (empty($recipients)) {
        throw new Exception('No valid recipient email addresses found');
    }
    
    // Send emails in batches
    $batchSize = $awsConfig['batch_size'];
    $delayBetweenBatches = $awsConfig['delay_between_batches'];
    $totalRecipients = count($recipients);
    $successCount = 0;
    $errorCount = 0;
    $details = [];
    
    // Process recipients in batches
    for ($i = 0; $i < $totalRecipients; $i += $batchSize) {
        $batch = array_slice($recipients, $i, $batchSize);
        
        foreach ($batch as $recipient) {
            try {
                // Create email message
                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($email['sender']);
                $mail->addAddress($recipient);
                $mail->Subject = $email['subject'];
                
                // Determine if message is HTML
                if (strpos($email['message'], '<html') !== false || strpos($email['message'], '<body') !== false) {
                    $mail->isHTML(true);
                    $mail->Body = $email['message'];
                    $mail->AltBody = strip_tags($email['message']);
                } else {
                    $mail->Body = $email['message'];
                }
                
                // Get raw email content
                $mail->preSend();
                $rawMessage = $mail->getSentMIMEMessage();
                
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
            } catch (AwsException $e) {
                // Record AWS error
                $errorCount++;
                $details[] = [
                    'email' => $recipient,
                    'status' => 'error',
                    'message' => 'AWS Error: ' . $e->getAwsErrorMessage()
                ];
            } catch (Exception $e) {
                // Record general error
                $errorCount++;
                $details[] = [
                    'email' => $recipient,
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
        }
        
        // Delay between batches
        if ($i + $batchSize < $totalRecipients && $delayBetweenBatches > 0) {
            sleep($delayBetweenBatches);
        }
    }
    
    // Update email history status
    $status = $errorCount === 0 ? 'success' : ($successCount === 0 ? 'error' : 'partial');
    
    $stmt = $db->prepare('UPDATE email_history SET status = ?, details = ? WHERE id = ?');
    $stmt->execute([
        $status,
        json_encode($details),
        $emailId
    ]);
    
    exit(0);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    exit(1);
}
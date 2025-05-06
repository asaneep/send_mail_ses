<?php
/**
 * Send Mail Script
 * 
 * This script handles sending bulk emails via Amazon SES.
 */

// Set maximum execution time to 0 (no limit) for long-running processes
set_time_limit(0);

// Include configuration
require_once 'config.php';

// Check if Composer autoloader exists
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'AWS SDK not installed. Please run "composer install" to install dependencies.'
    ]);
    exit;
}

// Import AWS SDK classes
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use PHPMailer\PHPMailer\PHPMailer;

// Set content type to JSON
header('Content-Type: application/json');

// Check if AWS is configured
if (!isAWSConfigured()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'AWS credentials not configured. Please go to Settings and configure your AWS credentials.'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
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
    
    // Get form data
    $sender = isset($_POST['sender']) ? trim($_POST['sender']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $messageFormat = isset($_POST['messageFormat']) ? $_POST['messageFormat'] : 'html';
    $recipientType = isset($_POST['recipientType']) ? $_POST['recipientType'] : 'textarea';
    
    // Validate required fields
    if (empty($sender)) {
        throw new Exception('Sender email is required');
    }
    
    if (empty($subject)) {
        throw new Exception('Subject is required');
    }
    
    if (empty($message)) {
        throw new Exception('Message is required');
    }
    
    // Get recipients
    $recipients = [];
    
    if ($recipientType === 'textarea') {
        // Get recipients from textarea
        $recipientsText = isset($_POST['recipients']) ? trim($_POST['recipients']) : '';
        
        if (empty($recipientsText)) {
            throw new Exception('Recipients are required');
        }
        
        // Split by newline or comma
        $recipientsList = preg_split('/[\r\n,]+/', $recipientsText);
        
        foreach ($recipientsList as $email) {
            $email = trim($email);
            
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $email;
            }
        }
    } else {
        // Get recipients from file
        if (!isset($_FILES['recipientFile']) || $_FILES['recipientFile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Recipient file upload failed');
        }
        
        $file = $_FILES['recipientFile']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if ($handle !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if (isset($data[0])) {
                    $email = trim($data[0]);
                    
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // If there are more columns, use them as personalization data
                        $personalization = [];
                        
                        if (count($data) > 1) {
                            for ($i = 1; $i < count($data); $i++) {
                                $personalization['column' . $i] = $data[$i];
                            }
                        }
                        
                        $recipients[] = [
                            'email' => $email,
                            'personalization' => $personalization
                        ];
                    }
                }
            }
            
            fclose($handle);
        }
    }
    
    // Check if we have any valid recipients
    if (empty($recipients)) {
        throw new Exception('No valid recipient email addresses found');
    }
    
    // Process attachments
    $attachments = [];
    
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $fileCount = count($_FILES['attachments']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $attachments[] = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'type' => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'size' => $_FILES['attachments']['size'][$i]
                ];
            }
        }
    }
    
    // Prepare email details for database
    $emailDetails = [
        'date' => date('Y-m-d H:i:s'),
        'sender' => $sender,
        'subject' => $subject,
        'recipients' => is_array($recipients[0]) ? count($recipients) : implode(', ', array_slice($recipients, 0, 5)) . (count($recipients) > 5 ? '...' : ''),
        'message' => $message,
        'status' => 'pending',
        'details' => json_encode([])
    ];
    
    // Save to database
    $emailId = saveEmailHistory($emailDetails);
    
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
                // Prepare recipient data
                $email = is_array($recipient) ? $recipient['email'] : $recipient;
                $personalization = is_array($recipient) && isset($recipient['personalization']) ? $recipient['personalization'] : [];
                
                // Personalize message
                $personalizedMessage = $message;
                
                // Replace {email} placeholder
                $personalizedMessage = str_replace('{email}', $email, $personalizedMessage);
                
                // Replace other placeholders
                if (!empty($personalization)) {
                    foreach ($personalization as $key => $value) {
                        $personalizedMessage = str_replace('{' . $key . '}', $value, $personalizedMessage);
                    }
                }
                
                // Create email message
                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($sender);
                $mail->addAddress($email);
                $mail->Subject = $subject;
                
                if ($messageFormat === 'html') {
                    $mail->isHTML(true);
                    $mail->Body = $personalizedMessage;
                    $mail->AltBody = strip_tags($personalizedMessage);
                } else {
                    $mail->Body = $personalizedMessage;
                }
                
                // Add attachments
                foreach ($attachments as $attachment) {
                    $mail->addAttachment(
                        $attachment['tmp_name'],
                        $attachment['name'],
                        'base64',
                        $attachment['type']
                    );
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
                    'email' => $email,
                    'status' => 'success',
                    'message' => 'Email sent successfully',
                    'messageId' => $result->get('MessageId')
                ];
            } catch (AwsException $e) {
                // Record AWS error
                $errorCount++;
                $details[] = [
                    'email' => $email,
                    'status' => 'error',
                    'message' => 'AWS Error: ' . $e->getAwsErrorMessage()
                ];
            } catch (Exception $e) {
                // Record general error
                $errorCount++;
                $details[] = [
                    'email' => $email,
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
    updateEmailHistory($emailId, $status, $details);
    
    // Return response
    echo json_encode([
        'status' => 'success',
        'message' => "Email sending completed. $successCount sent successfully, $errorCount failed.",
        'details' => $details
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Save email history to database
 * 
 * @param array $emailDetails Email details
 * @return int Email ID
 */
function saveEmailHistory($emailDetails) {
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/email_history.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
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
    } catch (PDOException $e) {
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
function updateEmailHistory($emailId, $status, $details) {
    try {
        $db = new PDO('sqlite:' . __DIR__ . '/email_history.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare('UPDATE email_history SET status = ?, details = ? WHERE id = ?');
        $stmt->execute([
            $status,
            json_encode($details),
            $emailId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}
<?php
/**
 * Resend Email Script
 * 
 * This script handles resending a previously sent email.
 */

// Include configuration
require_once 'config.php';

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

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email ID'
    ]);
    exit;
}

$id = (int) $_GET['id'];

try {
    // Connect to database
    $db = new PDO('sqlite:' . __DIR__ . '/email_history.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get email details
    $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
    $stmt->execute([$id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        throw new Exception('Email not found');
    }
    
    // Create a new email record with the same details
    $newEmailDetails = [
        'date' => date('Y-m-d H:i:s'),
        'sender' => $email['sender'],
        'subject' => $email['subject'] . ' (Resent)',
        'recipients' => $email['recipients'],
        'message' => $email['message'],
        'status' => 'pending',
        'details' => json_encode([])
    ];
    
    // Save to database
    $stmt = $db->prepare('INSERT INTO email_history (date, sender, subject, recipients, message, status, details) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $newEmailDetails['date'],
        $newEmailDetails['sender'],
        $newEmailDetails['subject'],
        $newEmailDetails['recipients'],
        $newEmailDetails['message'],
        $newEmailDetails['status'],
        $newEmailDetails['details']
    ]);
    
    $newEmailId = $db->lastInsertId();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Email has been queued for resending',
        'emailId' => $newEmailId
    ]);
    
    // Start background process to send the email
    if (PHP_OS === 'WINNT') {
        // Windows
        pclose(popen('start /B php resend_process.php ' . $newEmailId, 'r'));
    } else {
        // Unix/Linux
        exec('php resend_process.php ' . $newEmailId . ' > /dev/null 2>&1 &');
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
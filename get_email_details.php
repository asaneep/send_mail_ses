<?php
/**
 * Get Email Details Script
 * 
 * This script retrieves the details of a specific email.
 */

// Include configuration
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

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
    
    // Parse details JSON
    $details = json_decode($email['details'], true);
    
    // Return email details
    echo json_encode([
        'status' => 'success',
        'details' => [
            'id' => $email['id'],
            'date' => $email['date'],
            'sender' => $email['sender'],
            'subject' => $email['subject'],
            'recipients' => $email['recipients'],
            'message' => $email['message'],
            'status' => $email['status'],
            'details' => $details
        ]
    ]);
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
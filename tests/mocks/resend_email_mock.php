<?php
/**
 * Mock Resend Email Script for Testing
 * 
 * This script is a modified version of resend_email.php for testing.
 */

// Use our HeaderMock instead of the real header function
use Tests\Mocks\HeaderMock;

// No need to include config_functions.php as it's already included in the test

// Set content type to JSON (using our mock)
HeaderMock::capture('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    return json_encode([
        'status' => 'error',
        'message' => 'Invalid email ID'
    ]);
}

$id = (int) $_GET['id'];

try {
    // Connect to database
    $db = new PDO('sqlite:' . TEST_DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get email details
    $stmt = $db->prepare('SELECT * FROM email_history WHERE id = ?');
    $stmt->execute([$id]);
    $email = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$email) {
        throw new Exception('Email not found');
    }
    
    // In a real environment, we would start a background process here
    // For testing, we'll just return a success message
    
    // Return success response
    $result = [
        'status' => 'success',
        'message' => 'Resend process started for email ID: ' . $id
    ];
    
    // Return as JSON string
    return json_encode($result);
} catch (PDOException $e) {
    return json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    return json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
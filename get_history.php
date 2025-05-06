<?php
/**
 * Get Email History Script
 * 
 * This script retrieves the email sending history.
 */

// Include configuration
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Connect to database
    $db = new PDO('sqlite:' . __DIR__ . '/email_history.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get page number from query string
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countStmt = $db->query('SELECT COUNT(*) FROM email_history');
    $totalCount = $countStmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // Get history data
    $stmt = $db->prepare('SELECT id, date, sender, subject, recipients, status FROM email_history ORDER BY date DESC LIMIT ? OFFSET ?');
    $stmt->execute([$perPage, $offset]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate pagination HTML
    $pagination = '';
    
    if ($totalPages > 1) {
        $pagination = '<nav><ul class="pagination">';
        
        // Previous button
        $prevDisabled = $page <= 1 ? ' disabled' : '';
        $pagination .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="?page=' . ($page - 1) . '">Previous</a></li>';
        
        // Page numbers
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = $i === $page ? ' active' : '';
            $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next button
        $nextDisabled = $page >= $totalPages ? ' disabled' : '';
        $pagination .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="?page=' . ($page + 1) . '">Next</a></li>';
        
        $pagination .= '</ul></nav>';
    }
    
    // Return history data
    echo json_encode([
        'status' => 'success',
        'history' => $history,
        'pagination' => $pagination,
        'page' => $page,
        'totalPages' => $totalPages,
        'totalCount' => $totalCount
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
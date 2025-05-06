<?php
/**
 * Mock header function for testing
 * 
 * This file overrides the header function to prevent "headers already sent" errors during testing.
 */

namespace {
    // Store the original header function if it hasn't been mocked yet
    if (!function_exists('__original_header')) {
        function __original_header($header, $replace = true, $http_response_code = 0) {
            return \header($header, $replace, $http_response_code);
        }
    }
    
    // Override the header function
    function header($header, $replace = true, $http_response_code = 0) {
        // In test mode, just store the header instead of sending it
        global $mock_headers;
        
        if (!isset($mock_headers)) {
            $mock_headers = [];
        }
        
        $mock_headers[] = [
            'header' => $header,
            'replace' => $replace,
            'http_response_code' => $http_response_code
        ];
        
        return true;
    }
    
    // Function to get the mock headers
    function get_mock_headers() {
        global $mock_headers;
        return isset($mock_headers) ? $mock_headers : [];
    }
    
    // Function to clear the mock headers
    function clear_mock_headers() {
        global $mock_headers;
        $mock_headers = [];
    }
}
<?php

namespace Tests\Mocks;

/**
 * Header Mock Class
 * 
 * This class provides a way to capture headers instead of sending them during tests.
 */
class HeaderMock
{
    /**
     * @var array Captured headers
     */
    private static $headers = [];
    
    /**
     * Capture a header
     * 
     * @param string $header Header string
     * @param bool $replace Whether to replace previous headers with the same name
     * @param int $http_response_code HTTP response code
     */
    public static function capture($header, $replace = true, $http_response_code = 0)
    {
        self::$headers[] = [
            'header' => $header,
            'replace' => $replace,
            'http_response_code' => $http_response_code
        ];
    }
    
    /**
     * Get all captured headers
     * 
     * @return array Captured headers
     */
    public static function getHeaders()
    {
        return self::$headers;
    }
    
    /**
     * Clear all captured headers
     */
    public static function clear()
    {
        self::$headers = [];
    }
    
    /**
     * Check if a specific header was captured
     * 
     * @param string $pattern Pattern to match against headers
     * @return bool True if a matching header was found, false otherwise
     */
    public static function hasHeader($pattern)
    {
        foreach (self::$headers as $header) {
            if (preg_match($pattern, $header['header'])) {
                return true;
            }
        }
        
        return false;
    }
}
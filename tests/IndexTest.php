<?php

namespace Tests;

/**
 * Test case for the index page
 */
class IndexTest extends TestCase
{
    /**
     * Test that the index page loads correctly
     */
    public function testIndexPageLoads()
    {
        // Start output buffering
        ob_start();
        
        // Include the index.php file
        include __DIR__ . '/../index.php';
        
        // Get the output
        $output = ob_get_clean();
        
        // Assert that the output contains expected HTML elements
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<body>', $output);
        
        // Assert that the output contains key elements of the application
        $this->assertStringContainsString('Amazon SES', $output);
        $this->assertStringContainsString('form', $output);
        
        // Assert that the CSS and JS files are included
        $this->assertStringContainsString('css/style.css', $output);
        $this->assertStringContainsString('js/script.js', $output);
    }
    
    /**
     * Test that the required JavaScript libraries are included
     */
    public function testRequiredJsLibrariesAreIncluded()
    {
        // Start output buffering
        ob_start();
        
        // Include the index.php file
        include __DIR__ . '/../index.php';
        
        // Get the output
        $output = ob_get_clean();
        
        // Assert that Bootstrap is included
        $this->assertStringContainsString('bootstrap', $output);
        
        // Assert that the custom JS file is included
        $this->assertStringContainsString('js/script.js', $output);
    }
}
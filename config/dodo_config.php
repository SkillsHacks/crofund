<?php
/**
 * Dodo Payments Configuration
 * 
 * This file initializes the Dodo Payments client
 */

require_once __DIR__ . '/config.php';

// Check if Dodo Payments SDK is available
$dodoSdkAvailable = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Check if Dodo Payments classes are available
    if (class_exists('Dodopayments\Client')) {
        $dodoSdkAvailable = true;
    }
}

/**
 * Get Dodo Payments API Key
 */
function getDodoApiKey() {
    $apiKey = defined('DODO_PAYMENTS_API_KEY') && !empty(DODO_PAYMENTS_API_KEY) 
        ? DODO_PAYMENTS_API_KEY 
        : 'Uo3X0kH0085ZOz7N.gK0ZXwB4WRwqw3Pbdl-NxQOZXTgCi4NdATf_lvNuFaxsbcxP';
    return $apiKey;
}

/**
 * Get Dodo Payments Mode (test or live)
 */
function getDodoMode() {
    $mode = defined('DODO_PAYMENTS_MODE') && !empty(DODO_PAYMENTS_MODE)
        ? strtolower(DODO_PAYMENTS_MODE)
        : 'test';
    
    // Ensure mode is either 'test' or 'live'
    if ($mode !== 'test' && $mode !== 'live') {
        $mode = 'test';
    }
    
    return $mode;
}

/**
 * Get Dodo Payments Product ID
 */
function getDodoProductId() {
    $productId = defined('DODO_PAYMENTS_PRODUCT_ID') && !empty(DODO_PAYMENTS_PRODUCT_ID)
        ? DODO_PAYMENTS_PRODUCT_ID
        : 'pdt_0NUZztzpCGGvDEgE04j1b';
    return $productId;
}

/**
 * Initialize and return Dodo Payments client
 * 
 * @return \Dodopayments\Client|null
 * @throws Exception
 */
function getDodoClient() {
    global $dodoSdkAvailable;
    
    if (!$dodoSdkAvailable) {
        throw new Exception('Dodo Payments SDK is not installed. Please run: composer require dodopayments/client');
    }
    
    $apiKey = getDodoApiKey();
    $mode = getDodoMode();
    
    if (empty($apiKey)) {
        throw new Exception('Dodo Payments API key is not configured');
    }
    
    // Initialize client based on mode
    // Set the base URL based on test/live mode
    try {
        // Determine the base URL based on mode
        // Test Mode: https://test.dodopayments.com
        // Live Mode: https://live.dodopayments.com
        $baseUrl = ($mode === 'test') 
            ? 'https://test.dodopayments.com' 
            : 'https://live.dodopayments.com';
        
        // Client constructor: new Client(bearerToken: $apiKey, baseUrl: $baseUrl)
        // Note: We've patched Client.php to use Util::ostype() and Util::machtype()
        // instead of the non-existent getNormalizedOS() and getNormalizedArchitecture() methods
        $client = new \Dodopayments\Client(bearerToken: $apiKey, baseUrl: $baseUrl);
        
        error_log("Dodo Payments Client initialized in {$mode} mode with baseUrl: {$baseUrl}");
        
        return $client;
    } catch (Exception $e) {
        throw new Exception('Failed to initialize Dodo Payments client: ' . $e->getMessage());
    }
}


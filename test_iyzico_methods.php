<?php
/**
 * Test Iyzico CheckoutForm Response Methods
 * This script helps identify which methods are actually available
 */

require_once 'vendor/autoload.php';

echo "🔍 Testing Iyzico CheckoutForm Response Methods\n";
echo "==============================================\n\n";

try {
    // Bootstrap Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "1. Testing with a mock response...\n";
    
    // Create a mock response object to see what methods exist
    $mockResult = new \Iyzipay\Model\CheckoutForm();
    
    // List of methods we're trying to use
    $methods_to_test = [
        'getStatus',
        'getPaymentStatus', 
        'getFraudStatus',
        'getErrorCode',
        'getErrorMessage',
        'getPaymentId',
        'getConversationId',
        'getPrice',
        'getPaidPrice',
        'getCurrency',
        'getInstallment',
        'getBasketId',
        'getCardType',
        'getCardAssociation',
        'getCardFamily',
        'getBinNumber',
        'getLastFourDigits',
        'getMdStatus',
        'getAuthCode',
        'getHostReference',
        'getTransId',
        'getOrderId',
        'getPaymentGroup', // This one was causing the error
    ];
    
    echo "\n2. Available methods:\n";
    foreach ($methods_to_test as $method) {
        if (method_exists($mockResult, $method)) {
            echo "   ✅ $method() - Available\n";
        } else {
            echo "   ❌ $method() - NOT AVAILABLE\n";
        }
    }
    
    echo "\n3. All available methods on CheckoutForm:\n";
    $reflection = new ReflectionClass($mockResult);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    
    foreach ($methods as $method) {
        if (strpos($method->getName(), 'get') === 0) {
            echo "   📋 " . $method->getName() . "()\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Use only the available methods in the code\n";
echo "2. Test the payment flow again\n";
echo "3. Check if the payment now succeeds\n";
echo "\n";

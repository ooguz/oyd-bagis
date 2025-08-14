<?php
/**
 * Iyzico Configuration Test Script
 * Run this to test if your Iyzico configuration is working
 * 
 * Usage: php test_iyzico.php
 */

require_once 'vendor/autoload.php';

echo "🔍 Testing Iyzico Configuration\n";
echo "===============================\n\n";

// Check 1: Environment variables
echo "1. Environment Variables:\n";
$env_vars = [
    'IYZI_API_KEY' => 'API Key',
    'IYZI_SECRET_KEY' => 'Secret Key', 
    'IYZI_BASE_URL' => 'Base URL',
    'APP_URL' => 'Application URL'
];

foreach ($env_vars as $var => $description) {
    $value = getenv($var);
    if ($value) {
        if ($var === 'IYZI_SECRET_KEY') {
            echo "   ✅ $description: " . substr($value, 0, 8) . "...\n";
        } else {
            echo "   ✅ $description: $value\n";
        }
    } else {
        echo "   ❌ $description: Not set\n";
    }
}

// Check 2: Laravel config
echo "\n2. Laravel Configuration:\n";
try {
    // Bootstrap Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $api_key = config('services.iyzico.api_key');
    $secret_key = config('services.iyzico.secret_key');
    $base_url = config('services.iyzico.base_url');
    $app_url = config('app.url');
    
    if ($api_key) {
        echo "   ✅ Iyzico API Key: " . substr($api_key, 0, 8) . "...\n";
    } else {
        echo "   ❌ Iyzico API Key: Not configured\n";
    }
    
    if ($secret_key) {
        echo "   ✅ Iyzico Secret Key: " . substr($secret_key, 0, 8) . "...\n";
    } else {
        echo "   ❌ Iyzico Secret Key: Not configured\n";
    }
    
    if ($base_url) {
        echo "   ✅ Iyzico Base URL: $base_url\n";
    } else {
        echo "   ❌ Iyzico Base URL: Not configured\n";
    }
    
    if ($app_url) {
        echo "   ✅ App URL: $app_url\n";
    } else {
        echo "   ❌ App URL: Not configured\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error loading Laravel config: " . $e->getMessage() . "\n";
}

// Check 3: Route generation
echo "\n3. Route Generation:\n";
try {
    $callback_url = route('donate.callback');
    echo "   ✅ Callback URL: $callback_url\n";
    
    if (strpos($callback_url, 'test1.oyd.org.tr') !== false) {
        echo "   ✅ Callback URL contains correct domain\n";
    } else {
        echo "   ❌ Callback URL domain mismatch\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error generating route: " . $e->getMessage() . "\n";
}

// Check 4: Iyzico API connection
echo "\n4. Iyzico API Connection:\n";
try {
    $options = new \Iyzipay\Options();
    $options->setApiKey($api_key ?? '');
    $options->setSecretKey($secret_key ?? '');
    $options->setBaseUrl($base_url ?? '');
    
    echo "   ✅ Iyzico options created\n";
    
    // Test a simple API call (get supported banks)
    $request = new \Iyzipay\Request\RetrieveInstallmentInfoRequest();
    $request->setLocale(\Iyzipay\Model\Locale::TR);
    $request->setConversationId('test-' . uniqid());
    $request->setBinNumber('554960');
    $request->setPrice('100.0');
    
    $result = \Iyzipay\Model\InstallmentInfo::retrieve($request, $options);
    
    if ($result->getStatus() === 'success') {
        echo "   ✅ Iyzico API connection successful\n";
    } else {
        echo "   ❌ Iyzico API call failed: " . $result->getErrorMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Iyzico API error: " . $e->getMessage() . "\n";
}

echo "\n📋 Configuration Checklist:\n";
echo "==========================\n";
echo "□ Verify IYZI_API_KEY in .env file\n";
echo "□ Verify IYZI_SECRET_KEY in .env file\n";
echo "□ Verify IYZI_BASE_URL in .env file (use https://sandbox-api.iyzipay.com for testing)\n";
echo "□ Verify APP_URL in .env file (should be https://test1.oyd.org.tr)\n";
echo "□ Check if callback URL in Iyzico dashboard matches: $callback_url\n";

echo "\n🎯 Next Steps:\n";
echo "1. Fix any configuration issues above\n";
echo "2. Test the donation flow again\n";
echo "3. Check Laravel logs for detailed error information\n";
echo "4. Verify the callback URL in your Iyzico merchant dashboard\n";
echo "\n";

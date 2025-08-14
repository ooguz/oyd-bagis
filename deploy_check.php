<?php
/**
 * Laravel Deployment Check Script
 * Run this after deployment to verify everything is working
 * 
 * Usage: php deploy_check.php
 */

echo "🔍 Laravel Deployment Check\n";
echo "==========================\n\n";

// Check 1: Environment file
echo "1. Environment Configuration:\n";
if (file_exists('.env')) {
    echo "   ✅ .env file exists\n";
    
    $env_content = file_get_contents('.env');
    $required_vars = [
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'MAIL_MAILER',
        'MAIL_FROM_ADDRESS'
    ];
    
    foreach ($required_vars as $var) {
        if (strpos($env_content, $var . '=') !== false) {
            echo "   ✅ $var is set\n";
        } else {
            echo "   ❌ $var is missing\n";
        }
    }
} else {
    echo "   ❌ .env file missing\n";
}

// Check 2: Application key
echo "\n2. Application Key:\n";
if (file_exists('.env')) {
    $env_content = file_get_contents('.env');
    if (preg_match('/APP_KEY=base64:([a-zA-Z0-9+\/=]+)/', $env_content, $matches)) {
        echo "   ✅ APP_KEY is set and valid\n";
    } else {
        echo "   ❌ APP_KEY is missing or invalid\n";
    }
}

// Check 3: Storage permissions
echo "\n3. Storage Permissions:\n";
$storage_paths = [
    'storage/app',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($storage_paths as $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   ✅ $path is writable\n";
        } else {
            echo "   ❌ $path is not writable\n";
        }
    } else {
        echo "   ❌ $path directory missing\n";
    }
}

// Check 4: Database connection
echo "\n4. Database Connection:\n";
if (file_exists('.env')) {
    $env_content = file_get_contents('.env');
    if (strpos($env_content, 'DB_CONNECTION=mysql') !== false) {
        echo "   ✅ Database connection type: MySQL\n";
        
        // Try to connect to database
        $db_host = '';
        $db_name = '';
        $db_user = '';
        $db_pass = '';
        
        if (preg_match('/DB_HOST=([^\n]+)/', $env_content, $matches)) {
            $db_host = trim($matches[1]);
        }
        if (preg_match('/DB_DATABASE=([^\n]+)/', $env_content, $matches)) {
            $db_name = trim($matches[1]);
        }
        if (preg_match('/DB_USERNAME=([^\n]+)/', $env_content, $matches)) {
            $db_user = trim($matches[1]);
        }
        if (preg_match('/DB_PASSWORD=([^\n]+)/', $env_content, $matches)) {
            $db_pass = trim($matches[1]);
        }
        
        if ($db_host && $db_name && $db_user) {
            try {
                $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
                echo "   ✅ Database connection successful\n";
            } catch (PDOException $e) {
                echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ❌ Database credentials incomplete\n";
        }
    } else {
        echo "   ❌ Database connection not configured\n";
    }
}

// Check 5: Mail configuration
echo "\n5. Mail Configuration:\n";
if (file_exists('.env')) {
    $env_content = file_get_contents('.env');
    if (preg_match('/MAIL_MAILER=([^\n]+)/', $env_content, $matches)) {
        $mailer = trim($matches[1]);
        echo "   ✅ Mail driver: $mailer\n";
        
        if ($mailer === 'smtp') {
            if (strpos($env_content, 'MAIL_HOST=') !== false) {
                echo "   ✅ SMTP host configured\n";
            } else {
                echo "   ❌ SMTP host missing\n";
            }
        }
    } else {
        echo "   ❌ Mail driver not configured\n";
    }
}

// Check 6: CSRF protection
echo "\n6. CSRF Protection:\n";
if (file_exists('bootstrap/app.php')) {
    $bootstrap_content = file_get_contents('bootstrap/app.php');
    if (strpos($bootstrap_content, 'donate/callback') !== false) {
        echo "   ✅ Callback route excluded from CSRF\n";
    } else {
        echo "   ❌ Callback route not excluded from CSRF\n";
    }
}

// Check 7: Routes
echo "\n7. Route Configuration:\n";
if (file_exists('routes/web.php')) {
    $routes_content = file_get_contents('routes/web.php');
    if (strpos($routes_content, 'Route::match([\'GET\', \'POST\'], \'/donate/callback\'') !== false) {
        echo "   ✅ Callback route accepts GET and POST\n";
    } else {
        echo "   ❌ Callback route may not accept GET requests\n";
    }
}

echo "\n📋 Deployment Checklist:\n";
echo "========================\n";
echo "□ Generate application key: php artisan key:generate\n";
echo "□ Run migrations: php artisan migrate\n";
echo "□ Create sessions table: php artisan session:table && php artisan migrate\n";
echo "□ Clear caches: php artisan config:clear && php artisan cache:clear\n";
echo "□ Set permissions: chmod -R 775 storage/ bootstrap/cache/\n";
echo "□ Test email: php test_email.php\n";
echo "□ Verify callback URL in Iyzico dashboard\n";

echo "\n🎯 Next Steps:\n";
echo "1. Fix any issues identified above\n";
echo "2. Test the donation flow end-to-end\n";
echo "3. Check server logs for errors\n";
echo "4. Verify email delivery\n";
echo "\n";

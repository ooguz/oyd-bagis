# Deployment Configuration Guide

## Issues Fixed

### 1. CSRF Token Issue (419 Page Expired)
The callback route `/donate/callback` was failing because it's called by Iyzico (external payment provider) and can't include CSRF tokens. This has been fixed by excluding the callback and webhook routes from CSRF validation in `bootstrap/app.php`.

### 2. Email Configuration
Emails are currently configured to use the `log` driver, which means they're being logged instead of sent. You need to configure proper SMTP settings.

## Required Environment Variables

Create or update your `.env` file with these variables:

```bash
# Application
APP_NAME="OYD Bağış Sistemi"
APP_ENV=production
APP_KEY=your_generated_app_key
APP_DEBUG=false
APP_URL=https://test1.oyd.org.tr
APP_LOCALE=tr
APP_FALLBACK_LOCALE=tr

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password

# Session (Important for production)
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Mail Configuration (Choose one option)
# Option 1: Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="OYD Bağış Sistemi"

# Option 2: Sendmail (if available on server)
# MAIL_MAILER=sendmail
# MAIL_FROM_ADDRESS=your_email@oyd.org.tr
# MAIL_FROM_NAME="OYD Bağış Sistemi"

# Payment Configuration
PAYMENTS_FLOW=checkout
PAYMENTS_CURRENCY=TRY
PAYMENTS_THREE_D_THRESHOLD_MAJOR=500.00
RATE_LIMIT_DONATE=10

# Iyzico Configuration
IYZI_API_KEY=your_iyzico_api_key
IYZI_SECRET_KEY=your_iyzico_secret_key
IYZI_BASE_URL=https://sandbox-api.iyzipay.com

# Admin Configuration
ADMIN_EMAIL=admin@oyd.org.tr
```

## Steps to Fix Deployment Issues

### 1. Generate Application Key
```bash
php artisan key:generate
```

### 2. Run Database Migrations
```bash
php artisan migrate
```

### 3. Create Sessions Table (if using database sessions)
```bash
php artisan session:table
php artisan migrate
```

### 4. Clear All Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 5. Set Proper Permissions
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 6. Test Email Configuration
```bash
php artisan tinker
Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });
```

## Common Issues and Solutions

### CSRF Token Mismatch
- ✅ Fixed: Callback and webhook routes are now excluded from CSRF validation
- The routes `/donate/callback` and `/webhooks/*` will work without CSRF tokens

### Emails Not Sending
- Check your SMTP configuration
- Verify port and encryption settings
- Test with a simple email first
- Check server logs for mail errors

### Session Issues
- Ensure sessions table exists if using database driver
- Check file permissions on storage/framework/sessions/
- Verify SESSION_DRIVER setting

### Database Connection
- Verify database credentials
- Check if database server is accessible
- Ensure database exists and migrations have been run

## Security Notes

- Keep APP_DEBUG=false in production
- Use strong, unique APP_KEY
- Secure your database credentials
- Use HTTPS in production (APP_URL should start with https://)
- Regularly update dependencies

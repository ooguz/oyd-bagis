## Özgür Yazılım Derneği Bağış Yazılımı
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F002eb4f2-530f-4c84-b89d-8bc9c614e681%3Flabel%3D1&style=plastic)](https://forge.laravel.com/servers/724426/sites/2816893)

[bagis.oyd.org.tr](https://bagis.oyd.org.tr) adresinde barınan bağış yazılımı.

### Hızlı Başlangıç

1) Bağımlılıklar

```bash
composer install
npm install
```

2) Ortam değişkenleri

`.env` oluşturun ve şu anahtarları doldurun:

```env
APP_NAME="Dernek Bagis"
APP_URL=https://ornek.org
APP_LOCALE=tr
APP_FALLBACK_LOCALE=tr

MAIL_MAILER=smtp
MAIL_HOST=smtp.ornek.org
MAIL_PORT=587
MAIL_USERNAME=no-reply@ornek.org
MAIL_PASSWORD=supersecret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@ornek.org
MAIL_FROM_NAME="Dernek Bağış"

ADMIN_EMAIL=bagis@ornek.org

IYZI_API_KEY=your_api_key
IYZI_SECRET_KEY=your_secret
IYZI_BASE_URL=https://sandbox-api.iyzipay.com
IYZI_THREE_D_THRESHOLD=500.00

PAYMENTS_DRIVER=iyzico
FEATURE_SUBSCRIPTIONS=false

HMAC_WEBHOOK_SECRET=change_me
RATE_LIMIT_DONATE=10
```

3) Derleme ve çalıştırma

```bash
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

Kuyruk işleri için:

```bash
php artisan queue:work
```

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aylık bağış aboneliğiniz başlatıldı</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4c2447; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #4c2447; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; color: #4c2447; }
        .detail-value { color: #666; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .success { color: #28a745; font-weight: bold; }
        .info { background: #e8f5e9; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50; margin: 15px 0; }
        .note { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Aylık Bağış Aboneliğiniz</h1>
            <p>Özgür Yazılım Derneği</p>
        </div>

        <div class="content">
            <p>Merhaba <strong>{{ $subscription->donor->full_name }}</strong>,</p>

            <p>Özgür Yazılım Derneği'ne aylık düzenli bağış aboneliğiniz başarıyla başlatılmıştır. <strong>Çok teşekkür ederiz!</strong> 🎊</p>

            <div class="details">
                <h3>📋 Abonelik Detayları</h3>

                <div class="detail-row">
                    <span class="detail-label">💰 Aylık Bağış Tutarı:</span>
                    <span class="detail-value success">₺{{ number_format($subscription->amount_minor / 100, 2, ',', '.') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">📅 Başlangıç Tarihi:</span>
                    <span class="detail-value">{{ ($subscription->started_at ?? now())->timezone('Europe/Istanbul')->format('d.m.Y') }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">🔄 Ödeme Sıklığı:</span>
                    <span class="detail-value">Her ay otomatik</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">🆔 Abonelik Referansı:</span>
                    <span class="detail-value">{{ $subscription->iyzico_sub_ref }}</span>
                </div>
            </div>

            <div class="info">
                <strong>✅ Aboneliğiniz Aktif</strong><br>
                Kartınızdan her ay otomatik olarak ₺{{ number_format($subscription->amount_minor / 100, 2, ',', '.') }} tahsilat yapılacaktır. İstediğiniz zaman bağış geçmişinize girerek aboneliğinizi iptal edebilir veya kart bilgilerinizi güncelleyebilirsiniz.
            </div>

            <div class="note">
                <strong>📋 Önemli Bilgi:</strong><br>
                Bankanız tarafından düzenlenen dekont ya da hesap özetleri alındı belgesi (makbuz) yerine geçmektedir. (Dernekler Kanunu Md. 11)
            </div>

            <p>Aylık düzenli desteğiniz, özgür yazılım mücadelesi ve "özgür yazılım, özgür toplum" hedefine ulaşmak için yaptığımız dernek çalışmalarına büyük güç katmaktadır. Tekrardan çok teşekkür ederiz!</p>
        </div>

        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Bu e-posta otomatik olarak gönderilmiştir.</p>
        </div>
    </div>
</body>
</html>

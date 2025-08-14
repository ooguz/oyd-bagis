<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bağış Makbuzunuz</title>
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
        .note { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Bağış Makbuzunuz</h1>
            <p>{{ config('app.name') }}</p>
        </div>
        
        <div class="content">
            <p>Merhaba <strong>{{ $donation->full_name }}</strong>,</p>
            
            <p>{{ config('app.name') }} adına yapmış olduğunuz bağış için <strong>çok teşekkür ederiz</strong>! 🎊</p>
            
            <div class="details">
                <h3>📋 Bağış Detayları</h3>
                
                <div class="detail-row">
                    <span class="detail-label">💰 Bağış Tutarı:</span>
                    <span class="detail-value success">₺{{ number_format($donation->amount_minor / 100, 2, ',', '.') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">📅 Bağış Tarihi:</span>
                    <span class="detail-value">{{ $donation->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">🆔 İşlem Referansı:</span>
                    <span class="detail-value">{{ $donation->conversation_id }}</span>
                </div>
                
                @if($donation->payment_id)
                <div class="detail-row">
                    <span class="detail-label">💳 Ödeme ID:</span>
                    <span class="detail-value">{{ $donation->payment_id }}</span>
                </div>
                @endif
                
                @if($donation->card_last4)
                <div class="detail-row">
                    <span class="detail-label">💳 Kart Bilgisi:</span>
                    <span class="detail-value">**** **** **** {{ $donation->card_last4 }} ({{ $donation->card_brand ?? 'Kredi Kartı' }})</span>
                </div>
                @endif
                
                @if($donation->notes)
                <div class="detail-row">
                    <span class="detail-label">📝 Notunuz:</span>
                    <span class="detail-value">{{ $donation->notes }}</span>
                </div>
                @endif
            </div>
            
            <div class="note">
                <strong>📋 Önemli Bilgi:</strong><br>
                Bankanız tarafından düzenlenen dekont ya da hesap özetleri alındı belgesi (makbuz) yerine geçmektedir. (Dernekler Kanunu Md. 11)
            </div>
            
            <p>Bağışınız, toplum yararına yapılan çalışmalarımızda kullanılacaktır. Tekrar teşekkür ederiz!</p>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
        </div>
    </div>
</body>
</html>




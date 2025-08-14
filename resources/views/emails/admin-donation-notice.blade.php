<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Bağış Bildirimi</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
        .details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #dc3545; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; color: #dc3545; min-width: 150px; }
        .detail-value { color: #666; flex: 1; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .success { color: #28a745; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .notes { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .payment-info { background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8; margin: 15px 0; }
        .tech-info { background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Yeni Bağış Bildirimi</h1>
            <p>{{ config('app.name') }}</p>
        </div>
        
        <div class="content">
            <p><strong>Yeni bir bağış işlemi tamamlandı!</strong> 🎊</p>
            
            <div class="details">
                <h3>👤 Bağışçı Bilgileri</h3>
                
                <div class="detail-row">
                    <span class="detail-label">👤 Ad Soyad:</span>
                    <span class="detail-value">{{ $donation->full_name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">📧 E-posta:</span>
                    <span class="detail-value">{{ $donation->email }}</span>
                </div>
                
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
            </div>
            
            @if($donation->notes)
            <div class="notes">
                <h4>📝 Bağışçı Notu:</h4>
                <p><strong>{{ $donation->notes }}</strong></p>
            </div>
            @endif
            
            @if($donation->payment_id || $donation->card_last4)
            <div class="payment-info">
                <h4>💳 Ödeme Bilgileri</h4>
                
                @if($donation->payment_id)
                <div class="detail-row">
                    <span class="detail-label">🆔 Ödeme ID:</span>
                    <span class="detail-value">{{ $donation->payment_id }}</span>
                </div>
                @endif
                
                @if($donation->card_last4)
                <div class="detail-row">
                    <span class="detail-label">💳 Kart Bilgisi:</span>
                    <span class="detail-value">**** **** **** {{ $donation->card_last4 }} ({{ $donation->card_brand ?? 'Kredi Kartı' }})</span>
                </div>
                @endif
                
                @if($donation->completed_at)
                <div class="detail-row">
                    <span class="detail-label">✅ Tamamlanma:</span>
                    <span class="detail-value">{{ $donation->completed_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</span>
                </div>
                @endif
            </div>
            @endif
            
            <div class="tech-info">
                <h4>🔧 Teknik Bilgiler</h4>
                
                <div class="detail-row">
                    <span class="detail-label">🌐 IP Adresi:</span>
                    <span class="detail-value">{{ $extra['ip'] ?? 'Bilinmiyor' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">📱 Tarayıcı:</span>
                    <span class="detail-value">{{ $extra['ua'] ?? 'Bilinmiyor' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">🆔 Bağış ID:</span>
                    <span class="detail-value">{{ $donation->id }}</span>
                </div>
            </div>
            
            <p><strong>Bu bağış işlemi başarıyla tamamlanmıştır.</strong> Bağışçıya otomatik olarak makbuz e-postası gönderilmiştir.</p>
        </div>
        
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong> - Otomatik Bildirim Sistemi</p>
            <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
        </div>
    </div>
</body>
</html>




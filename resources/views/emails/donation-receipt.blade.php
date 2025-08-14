<p>Merhaba {{ $donation->full_name }},</p>
<p>{{ config('app.name') }} adına yapmış olduğunuz bağış için teşekkür ederiz.</p>
<ul>
  <li>Tutar: ₺{{ number_format($donation->amount_minor / 100, 2, ',', '.') }}</li>
  <li>Tarih/Saat: {{ $donation->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</li>
  <li>Referans: {{ $donation->conversation_id }}</li>
</ul>
<p>Not: {{ $donation->notes ?? '-' }}</p>
<p>Saygılarımızla,<br>{{ config('app.name') }}</p>




<p>Yeni bağış bildirimi</p>
<ul>
  <li>İsim: {{ $donation->full_name }}</li>
  <li>Email: {{ $donation->email }}</li>
  <li>Tutar: ₺{{ number_format($donation->amount_minor / 100, 2, ',', '.') }}</li>
  <li>Ref: {{ $donation->conversation_id }}</li>
  <li>IP: {{ $extra['ip'] ?? '' }}</li>
  <li>UA: {{ $extra['ua'] ?? '' }}</li>
</ul>




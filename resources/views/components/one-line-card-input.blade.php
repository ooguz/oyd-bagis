@php($flow = config('payments.flow', 'checkout'))
<div class="w-full border rounded px-3 py-2 min-h-14 flex items-center justify-center">
  @if($flow === 'direct')
    <input name="card_oneline" autocomplete="cc-number" placeholder="Kart No MM/YY CVC" class="w-full outline-none" />
  @else
    @if($checkoutFormContent)
      <div class="w-full" style="--tw-ring-color:#4c2447;">{!! $checkoutFormContent !!}</div>
    @else
      <div class="text-sm text-gray-600 text-center">
        "Bağış Yap" butonuna bastıktan sonra <span class="font-medium">iyzico</span> tarafından
        güvenli bir ödeme formunda alınacaktır.
      </div>
    @endif
  @endif
</div>




<div class="w-full border rounded px-3 py-2 min-h-14 flex items-center justify-center">
  @if($checkoutFormContent)
    <div class="w-full" style="--tw-ring-color:#4c2447;">
      {!! $checkoutFormContent !!}
    </div>
  @else
    <div class="animate-pulse text-sm text-gray-500">Ödeme bileşeni yükleniyor…</div>
  @endif
</div>




<div class="border rounded p-4 space-y-3">
  <h3 class="font-semibold text-[#4c2447]">Diğer bağış yöntemleri</h3>
  <div class="text-sm text-gray-700">
    <p>Ayni bağışlarınız için veya farklı bir yöntem ile (çek vb.) bağışta bulunmak istiyorsanız lütfen <span id="email-placeholder" class="text-[#4c2447] underline cursor-pointer" onclick="revealEmail()">e-posta adresimizi görmek için tıklayın</span> adresinden bizimle iletişime geçin.</p>
  </div>
</div>

<script>
function revealEmail() {
    const placeholder = document.getElementById('email-placeholder');
    const email = 'bilgi' + '@' + 'oyd.org.tr';
    
    // Create mailto link
    const mailtoLink = document.createElement('a');
    mailtoLink.href = 'mailto:' + email;
    mailtoLink.textContent = email;
    mailtoLink.className = 'text-[#4c2447] underline hover:text-[#4c2447]/80';
    
    // Replace the placeholder with the actual email link
    placeholder.replaceWith(mailtoLink);
}
</script>

@if (session('success'))
    <div class="toast" role="status">✅ {{ session('success') }} <button type="button" aria-label="Tutup">×</button></div>
@elseif (session('error'))
    <div class="toast error" role="alert">⚠️ {{ session('error') }} <button type="button" aria-label="Tutup">×</button></div>
@elseif ($errors->any())
    <div class="toast error" role="alert">⚠️ Ada isian yang perlu dicek lagi. <button type="button" aria-label="Tutup">×</button></div>
@endif

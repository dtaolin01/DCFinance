@if (session('success'))
    <div class="toast" role="status">@include('partials.tile', ['n' => 'check', 'tone' => 'green']) <span>{{ session('success') }}</span> <button type="button" aria-label="Tutup">×</button></div>
@elseif (session('error'))
    <div class="toast" role="alert">@include('partials.tile', ['n' => 'alert', 'tone' => 'red']) <span>{{ session('error') }}</span> <button type="button" aria-label="Tutup">×</button></div>
@elseif ($errors->any())
    <div class="toast" role="alert">@include('partials.tile', ['n' => 'alert', 'tone' => 'red']) <span>Ada isian yang perlu dicek lagi.</span> <button type="button" aria-label="Tutup">×</button></div>
@endif

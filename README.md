# 💗 DCFinance

Aplikasi web (Laravel 12) untuk mencatat tabungan **kamu dan pasangan secara terpisah**, mendukung **Rupiah & AUD cash**, lengkap dengan **target tabungan**.

## ✨ Fitur

- **2 akun terpisah** — masing-masing login sendiri, data sendiri. Pendaftaran otomatis tertutup setelah 2 akun.
- **Setor / Tarik** dalam IDR atau AUD. Setiap transaksi AUD menyimpan kurs saat itu dan nilai rupiahnya.
- **Kurs AUD → IDR otomatis** (ExchangeRate-API, cadangan Frankfurter/ECB & Currency-API), di-cache, bisa diganti **kurs manual**.
- **Untung/rugi kurs AUD** — modal rata-rata tertimbang vs nilai hari ini.
- **Tukar uang** (Rupiah → AUD cash atau sebaliknya) tanpa terhitung sebagai setor/tarik.
- **Target** pribadi atau **bersama**, dalam IDR atau AUD: progres %, sisa, kebutuhan per bulan/minggu/hari, perkiraan tanggal tercapai, status sesuai jalur, kontribusi tiap orang.
- **Dashboard**: total dirupiahkan, saldo per mata uang, arus bulanan & grafik saldo 12 bulan, rata-rata/bulan, streak, komposisi simpanan, insight otomatis.
- **Target menabung bulanan** per orang.
- **Kita Berdua**: bandingkan progres dan lihat target bersama.
- UI pink–putih, responsif (sidebar di desktop, bottom nav di HP). Tanpa Node/Vite — cocok untuk shared hosting.

## 🚀 Menjalankan di lokal

Butuh PHP ≥ 8.2 (ekstensi: pdo_mysql, mbstring, openssl, curl), Composer, dan MySQL ≥ 5.7 / MariaDB ≥ 10.3.

```bash
git clone https://github.com/dtaolin01/DCFinance.git
cd DCFinance
composer install
cp .env.example .env          # lalu isi DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate
php artisan serve
```

Buka http://127.0.0.1:8000 → **Daftar** akun pertama, lalu pasanganmu daftar akun kedua.

Mau coba dengan data contoh? `php artisan db:seed --class=DemoSeeder` (login `demo1@dcfinance.test` / `password`). Jangan dijalankan di hosting.

## 🌐 Hosting

### Shared hosting (cPanel / Hostinger, dll.)

1. Buat database MySQL + user di panel hosting.
2. Upload project (atau `git clone` via Terminal) ke folder **di luar** `public_html`, mis. `~/dcfinance`.
3. Di Terminal hosting:
   ```bash
   cd ~/dcfinance
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
4. Edit `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://domainmu.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=nama_db
   DB_USERNAME=user_db
   DB_PASSWORD=sandi_db
   ```
5. `php artisan migrate --force`
6. Arahkan domain ke folder `public`:
   - Jika bisa atur *document root* domain → set ke `~/dcfinance/public`.
   - Jika tidak bisa: hapus isi `public_html`, lalu buat symlink: `rm -rf ~/public_html && ln -s ~/dcfinance/public ~/public_html`.
7. Optimasi: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
8. Pastikan folder `storage` dan `bootstrap/cache` bisa ditulis (`chmod -R 775 storage bootstrap/cache`).

Setelah mengubah `.env`, jalankan `php artisan config:clear` (atau `config:cache` lagi).

### VPS
Sama seperti di atas; arahkan root Nginx/Apache ke `public/`, gunakan PHP-FPM.

> ⚠️ Repo ini **publik**. Jangan pernah commit file `.env` (sudah diabaikan oleh `.gitignore`) — kredensial database hanya ditulis di `.env` di server.

## ⚙️ Pengaturan (`.env`)

| Variabel | Default | Fungsi |
|---|---|---|
| `DC_MAX_USERS` | `2` | Jumlah akun maksimal; pendaftaran tertutup setelah penuh |
| `DC_DEFAULT_AUD_RATE` | `10500` | Kurs cadangan bila semua API kurs gagal |
| `DC_RATE_CACHE_MINUTES` | `180` | Lama kurs online disimpan |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu |

## 🧮 Cara perhitungan

- **Nilai rupiah transaksi** = nominal × kurs saat transaksi (IDR = 1).
- **Total tabungan** = saldo Rupiah + saldo AUD × kurs hari ini.
- **Modal AUD** = rata-rata tertimbang kurs setiap AUD masuk; AUD keluar mengurangi modal secara proporsional.
- **Untung/rugi kurs** = nilai AUD hari ini − modal AUD.
- **Tabungan bersih bulanan** = setor − tarik (tukar uang tidak dihitung).
- **Target**: transaksi yang ditautkan ke target. AUD dinilai kurs hari ini. Kebutuhan per bulan = sisa ÷ bulan tersisa sampai tenggat. Perkiraan tercapai memakai kecepatan menabung 90 hari terakhir.
- Penarikan tidak boleh melebihi saldo mata uang tersebut.

## 🗂️ Struktur penting

```
app/Services/SavingsCalculator.php   # semua perhitungan tabungan
app/Services/ExchangeRateService.php # kurs AUD (online/manual/cadangan)
app/Models/Goal.php                  # progres & rencana target
app/Http/Controllers/                # Dashboard, Transaksi, Tukar, Target, Berdua, Kurs, Profil
resources/views/                     # tampilan Blade
public/css/app.css, public/js/app.js # tema & interaksi (tanpa build)
```

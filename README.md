# 💗 DCFinance

Aplikasi web (Laravel 12) untuk mencatat tabungan **kamu dan pasangan secara terpisah**, mendukung **Rupiah & 150+ mata uang asing**, lengkap dengan **target tabungan**.

## ✨ Fitur

- **Akun terpisah + persetujuan** — akun pertama otomatis jadi **admin**. Akun yang mendaftar setelahnya harus **disetujui admin** (menu *Persetujuan*) sebelum bisa masuk. Akun aktif maksimal `DC_MAX_USERS` (default 2).
- **Semua mata uang (150+)** — setor/tarik dalam Rupiah atau mata uang apa pun (USD, AUD, SGD, JPY, EUR, …). Setiap transaksi valas menyimpan kurs saat itu dan nilai rupiahnya.
- **Kurs otomatis semua mata uang** (ExchangeRate-API, cadangan Currency-API & Frankfurter), di-cache, bisa diganti **kurs manual** per mata uang.
- **Untung/rugi kurs per mata uang** — modal rata-rata tertimbang vs nilai hari ini.
- **Tukar uang** antar mata uang apa pun tanpa terhitung sebagai setor/tarik.
- **Target** pribadi atau **bersama**, dalam mata uang apa pun: progres, sisa, kebutuhan per bulan/minggu/hari, perkiraan tanggal tercapai, kontribusi tiap orang.
- **Tema warna per akun** (Pink default, Hitam, Biru, Ungu, Hijau, Tosca, Oranye, Merah) — dipilih di *Profil & Tema*.
- **Dashboard**: total dirupiahkan, saldo Rupiah & valas, grafik 12 bulan, rata-rata, streak, komposisi simpanan, insight otomatis.
- **Kita Berdua**: bandingkan progres dan lihat target bersama.
- Responsif (sidebar di desktop, bottom nav di HP). Tanpa Node/Vite — cocok untuk shared hosting.

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

### Hostinger (subdomain, mis. finance.domainmu.com)

1. hPanel → **Domains → Subdomains** → buat subdomain. Catat foldernya (mis. `public_html/finance`).
2. hPanel → **Advanced → PHP Configuration** → pilih **PHP 8.3**.
3. hPanel → **Advanced → SSH Access** → aktifkan, lalu login SSH.
4. Kosongkan folder subdomain, lalu:
   ```bash
   cd ~/domains/domainmu.com/public_html/finance   # sesuaikan dengan folder subdomain
   git clone https://github.com/dtaolin01/DCFinance.git .
   composer install --no-dev --optimize-autoloader
   ```
5. Buat file `.env` (isi sesuai data database dari hPanel → Databases), lalu:
   ```bash
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
   File `.htaccess` di root repo otomatis meneruskan semua request ke folder `public/`.
6. hPanel → **Security → SSL** → pasang SSL untuk subdomain. Buka situsnya dan daftar akun.

Update berikutnya:
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

### Shared hosting lain (cPanel, dll.)

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
| `DC_MAX_USERS` | `2` | Jumlah akun aktif maksimal; pendaftaran tertutup setelah penuh |
| `DC_RATE_CACHE_MINUTES` | `180` | Lama kurs online disimpan |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu |

## 🧮 Cara perhitungan

- **Nilai rupiah transaksi** = nominal × kurs saat transaksi (IDR = 1).
- **Total tabungan** = saldo Rupiah + Σ (saldo tiap valas × kurs hari ini).
- **Modal valas** = rata-rata tertimbang kurs setiap uang masuk per mata uang; uang keluar mengurangi modal secara proporsional.
- **Untung/rugi kurs** = nilai valas hari ini − modalnya.
- **Kurs dipakai** (urutan): kurs manual → kurs online → kurs online terakhir tersimpan → kurs transaksi terakhir → perkiraan offline.
- **Tabungan bersih bulanan** = setor − tarik (tukar uang tidak dihitung).
- **Target**: transaksi yang ditautkan ke target, semua dinilai ke Rupiah dengan kurs hari ini lalu ke mata uang target. Kebutuhan per bulan = sisa ÷ bulan tersisa sampai tenggat. Perkiraan tercapai memakai kecepatan menabung 90 hari terakhir.
- Penarikan tidak boleh melebihi saldo mata uang tersebut.

## 🗂️ Struktur penting

```
app/Services/SavingsCalculator.php   # semua perhitungan tabungan
app/Services/ExchangeRateService.php # kurs semua mata uang (online/manual/cadangan)
config/currencies.php                # daftar mata uang
config/themes.php                    # tema warna
app/Models/Goal.php                  # progres & rencana target
app/Http/Controllers/                # Dashboard, Transaksi, Tukar, Target, Berdua, Kurs, Profil
resources/views/                     # tampilan Blade
public/css/app.css, public/js/app.js # tema & interaksi (tanpa build)
```

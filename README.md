# 📷 LensCraft - Sistem Rental Kamera Profesional

<div align="center">

![LensCraft Banner](https://img.shields.io/badge/LensCraft-Rental%20System-c7a65a?style=for-the-badge&logo=camera&logoColor=white)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

**Platform rental peralatan fotografi dan videografi profesional dengan sistem manajemen lengkap**

[Demo](#-demo) • [Fitur](#-fitur-utama) • [Instalasi](#-instalasi) • [Dokumentasi](#-dokumentasi) • [Kontribusi](#-kontribusi)

</div>

---

## 🌟 Tentang LensCraft

LensCraft adalah sistem manajemen rental kamera dan peralatan fotografi berbasis web yang dirancang untuk memudahkan proses penyewaan peralatan profesional. Dengan antarmuka modern dan intuitif, LensCraft menyediakan solusi lengkap untuk bisnis rental kamera, dari katalog produk hingga manajemen pengembalian.

### 🎯 Mengapa LensCraft?

- **🎨 UI/UX Premium** - Desain modern dengan Tailwind CSS dan animasi halus
- **👥 Multi-Role System** - Admin, Staff, dan Customer dengan hak akses berbeda
- **📊 Dashboard Lengkap** - Statistik real-time dan laporan komprehensif
- **🔒 Keamanan Terjamin** - CSRF protection, password hashing, dan session management
- **📱 Responsive Design** - Optimal di semua perangkat (desktop, tablet, mobile)
- **⚡ Performa Tinggi** - Query optimization dan caching strategy
- **🌐 Bahasa Indonesia** - Interface dan dokumentasi dalam Bahasa Indonesia

---

## ✨ Fitur Utama

### 👤 Untuk Pelanggan (Customer)
- 🔍 **Katalog Produk Interaktif** - Browse kamera, lensa, dan aksesori dengan filter canggih
- 📅 **Sistem Booking** - Pilih tanggal rental dengan kalender interaktif
- 💳 **Manajemen Pembayaran** - Upload bukti transfer dan tracking status
- 📦 **Riwayat Rental** - Lihat semua transaksi dan status rental
- 👤 **Profil Pengguna** - Update informasi pribadi dan avatar
- 🔔 **Notifikasi Real-time** - Flash messages untuk setiap aksi

### 👨‍💼 Untuk Staff (Petugas)
- ✅ **Approval Rental** - Review dan approve/reject permintaan rental
- 📋 **Manajemen Pengembalian** - Konfirmasi pengembalian dan catat denda
- 💰 **Update Harga & Stok** - Bulk update harga dan stok produk
- 📊 **Dashboard Staff** - Statistik rental aktif dan pending
- 📄 **Export Laporan** - Generate laporan dalam format Excel/PDF

### 👨‍💻 Untuk Admin
- 🎛️ **Full Control Panel** - Kelola semua aspek sistem
- 👥 **Manajemen User** - CRUD users dengan role management
- 📦 **Manajemen Produk** - Tambah, edit, hapus produk dengan upload gambar
- 🏷️ **Manajemen Kategori** - Organisasi produk berdasarkan kategori
- 💸 **Manajemen Rental** - Override dan kelola semua transaksi
- 📈 **Analytics Dashboard** - Grafik pendapatan, rental populer, dan statistik
- 📜 **Activity Log** - Audit trail untuk semua aktivitas sistem

---

## 🛠️ Teknologi yang Digunakan

### Backend
- **PHP 8.0+** - Server-side scripting
- **MySQL 8.0+** - Relational database
- **MySQLi** - Database driver dengan prepared statements

### Frontend
- **HTML5 & CSS3** - Semantic markup dan modern styling
- **Tailwind CSS 3.x** - Utility-first CSS framework
- **Vanilla JavaScript** - Interactive UI tanpa framework berat
- **Google Fonts** - Inter & Playfair Display typography

### Security & Best Practices
- ✅ CSRF Token Protection
- ✅ Password Hashing (bcrypt)
- ✅ SQL Injection Prevention (Prepared Statements)
- ✅ XSS Protection (HTML Escaping)
- ✅ Session Management
- ✅ Input Validation & Sanitization

---

## 📋 Prasyarat

Sebelum instalasi, pastikan sistem Anda memiliki:

- **PHP** >= 8.0 dengan ekstensi:
  - `mysqli`
  - `json`
  - `session`
  - `gd` (untuk image processing)
- **MySQL** >= 8.0 atau **MariaDB** >= 10.5
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **Composer** (opsional, untuk dependency management)

---

## 🚀 Instalasi

### 1️⃣ Clone Repository

```bash
git clone https://github.com/username/lenscraft.git
cd lenscraft
```

### 2️⃣ Konfigurasi Database

**Buat database MySQL:**

```bash
mysql -u root -p
```

```sql
CREATE DATABASE lenscraft CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Import schema database:**

```bash
mysql -u root -p lenscraft < database/lenscraft-complete.sql
```

### 3️⃣ Konfigurasi Koneksi Database

Edit file `config/koneksi.php` atau set environment variables:

```php
// Opsi 1: Edit langsung di config/koneksi.php
$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'lenscraft';
$db_user = 'root';
$db_pass = 'your_password';
```

```bash
# Opsi 2: Gunakan environment variables
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=lenscraft
export DB_USER=root
export DB_PASS=your_password
```

### 4️⃣ Set Permissions

```bash
# Linux/Mac
chmod -R 755 .
chmod -R 777 uploads/
chmod -R 777 data/

# Pastikan web server memiliki akses write ke folder uploads
chown -R www-data:www-data uploads/
```

### 5️⃣ Konfigurasi Web Server

**Apache (.htaccess sudah included):**

```apache
<VirtualHost *:80>
    ServerName lenscraft.local
    DocumentRoot /path/to/lenscraft
    
    <Directory /path/to/lenscraft>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 80;
    server_name lenscraft.local;
    root /path/to/lenscraft;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6️⃣ Akses Aplikasi

Buka browser dan akses:

```
http://localhost/lenscraft
atau
http://lenscraft.local
```

---

## 👥 Default User Accounts

Setelah import database, gunakan akun berikut untuk login:

### Admin
- **Username:** `admin`
- **Password:** `admin123`
- **Email:** `admin@lenscraft.local`

### Staff (Petugas)
- **Username:** `staff`
- **Password:** `staff123`
- **Email:** `staff@lenscraft.local`

### Customer (Pelanggan)
- **Username:** `customer`
- **Password:** `customer123`
- **Email:** `customer@lenscraft.local`

> ⚠️ **PENTING:** Segera ubah password default setelah login pertama kali!

---

## 📁 Struktur Direktori

```
lenscraft/
├── admin/                      # Panel admin
│   ├── index.php              # Dashboard admin
│   ├── users.php              # Manajemen user
│   ├── products.php           # Manajemen produk
│   ├── categories.php         # Manajemen kategori
│   ├── borrowings.php         # Manajemen rental
│   ├── returns.php            # Manajemen pengembalian
│   └── activity-log.php       # Log aktivitas
├── staff/                      # Panel staff
│   ├── index.php              # Dashboard staff
│   ├── borrowings.php         # Approval rental
│   ├── returns.php            # Konfirmasi pengembalian
│   ├── stock-price.php        # Update stok & harga
│   └── reports.php            # Laporan
├── user/                       # Panel customer
│   ├── profile.php            # Profil pengguna
│   ├── rentals.php            # Riwayat rental
│   ├── payment.php            # Upload pembayaran
│   └── settings.php           # Pengaturan
├── process/                    # Backend processors
│   ├── login-process.php
│   ├── register-process.php
│   ├── rental-create-process.php
│   └── ...
├── includes/                   # Helper functions
│   ├── functions.php          # Core functions
│   ├── flash.php              # Flash messages
│   ├── auth-check.php         # Authentication
│   └── upload.php             # File upload handler
├── data/                       # Data access layer
│   ├── products-data.php
│   ├── rentals-data.php
│   ├── users/
│   └── ...
├── config/                     # Configuration files
│   ├── koneksi.php            # Database connection
│   └── base_url.php           # Base URL helper
├── database/                   # Database schema
│   └── lenscraft-complete.sql
├── images/                     # Static images
├── uploads/                    # User uploads (avatars, bukti transfer)
├── index.php                   # Landing page
├── login.php                   # Login page
├── register.php                # Registration page
├── products.php                # Katalog produk
├── product-detail.php          # Detail produk
└── README.md                   # Dokumentasi ini
```

---

## 🔐 Keamanan

LensCraft menerapkan best practices keamanan:

### 1. CSRF Protection
```php
// Generate token
<?= csrf_input(); ?>

// Validasi token
if (!csrf_validate()) {
    die('Invalid CSRF token');
}
```

### 2. Password Hashing
```php
// Hash password saat registrasi
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Verifikasi saat login
password_verify($input, $hashed);
```

### 3. SQL Injection Prevention
```php
// Gunakan prepared statements
db_execute('INSERT INTO users (name, email) VALUES (?, ?)', [$name, $email]);
```

### 4. XSS Protection
```php
// Escape output
echo e($user_input);
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
```

### 5. Session Security
```php
// Regenerate session ID setelah login
session_regenerate_id(true);

// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
```

---

## 📊 Database Schema

### Tabel Utama

#### `users`
Menyimpan data pengguna (admin, staff, customer)

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| fullname | VARCHAR(120) | Nama lengkap |
| email | VARCHAR(120) | Email (unique) |
| username | VARCHAR(60) | Username (unique) |
| password | VARCHAR(255) | Hashed password |
| role | ENUM | admin/petugas/pelanggan |
| phone | VARCHAR(30) | Nomor telepon |
| avatar_path | VARCHAR(255) | Path foto profil |
| created_at | DATETIME | Tanggal registrasi |

#### `products`
Katalog produk rental

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| category_id | INT | Foreign key ke categories |
| name | VARCHAR(150) | Nama produk |
| brand | VARCHAR(80) | Merek |
| price_per_day | DECIMAL(10,2) | Harga per hari |
| discount_percentage | INT | Diskon (%) |
| description | TEXT | Deskripsi produk |
| image_path | VARCHAR(255) | Path gambar |
| stock_total | INT | Total stok |
| stock_available | INT | Stok tersedia |
| status | ENUM | aktif/nonaktif |

#### `rentals`
Transaksi rental

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| rental_code | VARCHAR(30) | Kode rental (unique) |
| user_id | INT | Foreign key ke users |
| product_id | INT | Foreign key ke products |
| start_date | DATE | Tanggal mulai |
| end_date | DATE | Tanggal selesai |
| total_days | INT | Durasi (hari) |
| daily_rate | DECIMAL(10,2) | Tarif harian |
| delivery_method | ENUM | ambil_sendiri/diantar |
| delivery_fee | DECIMAL(10,2) | Biaya antar |
| total_price | DECIMAL(10,2) | Total harga |
| status | ENUM | menunggu/disetujui/aktif/selesai/dibatalkan/ditolak |

#### `payments`
Data pembayaran

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| payment_code | VARCHAR(30) | Kode pembayaran |
| rental_id | INT | Foreign key ke rentals |
| amount | DECIMAL(10,2) | Jumlah bayar |
| method | VARCHAR(50) | Metode pembayaran |
| status | ENUM | pending/paid |
| reference_code | VARCHAR(60) | Nomor referensi |
| paid_at | DATETIME | Tanggal bayar |

#### `returns`
Data pengembalian

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| return_code | VARCHAR(30) | Kode pengembalian |
| rental_id | INT | Foreign key ke rentals |
| processed_by | INT | Staff yang memproses |
| notes | TEXT | Catatan kondisi |
| fine_amount | DECIMAL(10,2) | Denda (jika ada) |
| status | ENUM | menunggu/selesai |
| returned_at | DATETIME | Tanggal kembali |

---

## 🎨 Fitur UI/UX

### Design System
- **Color Palette:**
  - Primary: `#c7a65a` (Brass Gold)
  - Background: `#050505` (Deep Black)
  - Panel: `rgba(17, 17, 17, 0.84)` (Dark Gray with transparency)
  - Text: `#e5e5e5` (Light Gray)

- **Typography:**
  - Heading: Playfair Display (Serif)
  - Body: Inter (Sans-serif)

- **Components:**
  - Glassmorphism cards
  - Smooth animations (fadeInUp, float)
  - Hover effects dengan transform
  - Loading states
  - Toast notifications

### Responsive Breakpoints
```css
/* Mobile First */
sm: 640px   /* Small devices */
md: 768px   /* Tablets */
lg: 1024px  /* Laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large screens */
```

---

## 🔄 Workflow Sistem

### 1. Registrasi & Login
```
Customer Register → Email Verification (optional) → Login → Dashboard
```

### 2. Proses Rental
```
Browse Katalog → Pilih Produk → Set Tanggal → Submit Request
    ↓
Staff Review → Approve/Reject
    ↓
Customer Upload Bukti Transfer
    ↓
Staff Konfirmasi Pembayaran → Status: Aktif
    ↓
Customer Gunakan Produk
    ↓
Customer Kembalikan → Staff Konfirmasi → Status: Selesai
```

### 3. Status Rental
- **menunggu** - Menunggu approval staff
- **disetujui** - Disetujui, menunggu pembayaran
- **mendatang** - Sudah bayar, belum dimulai
- **aktif** - Sedang berlangsung
- **selesai** - Sudah dikembalikan
- **dibatalkan** - Dibatalkan oleh customer
- **ditolak** - Ditolak oleh staff

---

## 🧪 Testing

### Manual Testing Checklist

#### Authentication
- [ ] Register dengan data valid
- [ ] Register dengan email duplikat (harus gagal)
- [ ] Login dengan kredensial benar
- [ ] Login dengan kredensial salah
- [ ] Logout dan cek session cleared

#### Customer Flow
- [ ] Browse katalog produk
- [ ] Filter produk by kategori
- [ ] Search produk by nama
- [ ] Lihat detail produk
- [ ] Buat rental request
- [ ] Upload bukti transfer
- [ ] Cancel rental
- [ ] Lihat riwayat rental

#### Staff Flow
- [ ] Approve rental request
- [ ] Reject rental request
- [ ] Konfirmasi pembayaran
- [ ] Konfirmasi pengembalian
- [ ] Update stok produk
- [ ] Update harga produk
- [ ] Export laporan

#### Admin Flow
- [ ] CRUD users
- [ ] CRUD products
- [ ] CRUD categories
- [ ] View analytics dashboard
- [ ] View activity log
- [ ] Override rental status

---

## 📈 Optimasi Performa

### Database Optimization
```sql
-- Index untuk query cepat
CREATE INDEX idx_rentals_user ON rentals(user_id);
CREATE INDEX idx_rentals_status ON rentals(status);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
```

### Caching Strategy
```php
// Cache kategori (jarang berubah)
static $categories_cache = null;
if ($categories_cache === null) {
    $categories_cache = db_all('SELECT * FROM categories');
}
return $categories_cache;
```

### Image Optimization
- Compress images sebelum upload
- Gunakan format WebP untuk browser modern
- Lazy loading untuk gambar produk
- Thumbnail generation untuk list view

---

## 🐛 Troubleshooting

### Database Connection Error
```
Error: Koneksi database gagal
```
**Solusi:**
1. Cek kredensial di `config/koneksi.php`
2. Pastikan MySQL service running: `sudo systemctl status mysql`
3. Cek firewall: `sudo ufw allow 3306`

### Upload File Gagal
```
Error: Failed to upload file
```
**Solusi:**
1. Cek permissions folder `uploads/`: `chmod 777 uploads/`
2. Cek `php.ini` settings:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. Restart web server

### Session Tidak Tersimpan
```
Error: Session data lost
```
**Solusi:**
1. Cek folder session writable: `chmod 777 /var/lib/php/sessions`
2. Cek `php.ini`:
   ```ini
   session.save_path = "/var/lib/php/sessions"
   ```

### Blank Page / White Screen
**Solusi:**
1. Enable error reporting di `php.ini`:
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
2. Cek error log: `tail -f /var/log/apache2/error.log`

---

## 🚀 Deployment

### Production Checklist

#### Security
- [ ] Ubah semua password default
- [ ] Set `display_errors = Off` di php.ini
- [ ] Enable HTTPS (SSL/TLS)
- [ ] Set secure session cookies
- [ ] Backup database secara berkala
- [ ] Restrict database access (tidak dari internet)

#### Performance
- [ ] Enable OPcache
- [ ] Enable Gzip compression
- [ ] Minify CSS/JS (jika ada custom files)
- [ ] Setup CDN untuk static assets
- [ ] Configure caching headers

#### Monitoring
- [ ] Setup error logging
- [ ] Monitor disk space (folder uploads)
- [ ] Setup database backup automation
- [ ] Monitor server resources (CPU, RAM)

### Deployment ke Shared Hosting

1. **Upload files via FTP/cPanel File Manager**
2. **Import database via phpMyAdmin**
3. **Edit `config/koneksi.php`** dengan kredensial hosting
4. **Set folder permissions:**
   ```
   uploads/ → 755
   data/ → 755
   ```
5. **Test semua fitur**

### Deployment ke VPS (Ubuntu)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install apache2 mysql-server php8.0 php8.0-mysqli php8.0-gd -y

# Clone repository
cd /var/www/html
sudo git clone https://github.com/username/lenscraft.git

# Set permissions
sudo chown -R www-data:www-data lenscraft/
sudo chmod -R 755 lenscraft/
sudo chmod -R 777 lenscraft/uploads/

# Import database
mysql -u root -p < lenscraft/database/lenscraft-complete.sql

# Configure Apache
sudo nano /etc/apache2/sites-available/lenscraft.conf
sudo a2ensite lenscraft
sudo systemctl reload apache2

# Enable SSL (Let's Encrypt)
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Berikut cara berkontribusi:

### 1. Fork Repository
```bash
# Fork via GitHub UI, lalu clone
git clone https://github.com/your-username/lenscraft.git
cd lenscraft
```

### 2. Buat Branch Baru
```bash
git checkout -b feature/nama-fitur
# atau
git checkout -b fix/nama-bug
```

### 3. Commit Changes
```bash
git add .
git commit -m "feat: tambah fitur X"
# atau
git commit -m "fix: perbaiki bug Y"
```

**Commit Message Convention:**
- `feat:` - Fitur baru
- `fix:` - Bug fix
- `docs:` - Update dokumentasi
- `style:` - Formatting, missing semicolons, etc
- `refactor:` - Code refactoring
- `test:` - Tambah testing
- `chore:` - Update dependencies, dll

### 4. Push & Pull Request
```bash
git push origin feature/nama-fitur
```
Lalu buat Pull Request di GitHub dengan deskripsi lengkap.

### Code Style Guidelines
- Gunakan 4 spaces untuk indentasi
- Nama variabel: `$snake_case`
- Nama function: `snake_case()`
- Nama class: `PascalCase`
- Tambahkan comment untuk logic kompleks
- Follow PSR-12 coding standard

---

## 📝 Changelog

### Version 1.0.0 (2026-05-05)
- ✨ Initial release
- ✅ Multi-role authentication system
- ✅ Product catalog dengan filter & search
- ✅ Rental management system
- ✅ Payment tracking
- ✅ Return management
- ✅ Admin dashboard dengan analytics
- ✅ Staff approval workflow
- ✅ Customer profile management
- ✅ Responsive design
- ✅ Flash notification system

---

## 📄 License

Projek ini dilisensikan di bawah [MIT License](LICENSE).

```
MIT License

Copyright (c) 2026 LensCraft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 👨‍💻 Author

**LensCraft Development Team**

- Website: [lenscraft.local](http://lenscraft.local)
- Email: hallo@lenscraft.local
- GitHub: [@lenscraft](https://github.com/lenscraft)

---

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS framework
- [Google Fonts](https://fonts.google.com/) - Inter & Playfair Display
- [PHP](https://www.php.net/) - Server-side scripting language
- [MySQL](https://www.mysql.com/) - Relational database management system
- Semua kontributor yang telah membantu projek ini

---

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. **Cek dokumentasi** di README ini
2. **Cek Issues** di GitHub untuk masalah serupa
3. **Buat Issue baru** dengan detail lengkap:
   - Deskripsi masalah
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots (jika ada)
   - Environment (OS, PHP version, MySQL version)

---

## 🗺️ Roadmap

### Version 1.1.0 (Q3 2026)
- [ ] Email notification system
- [ ] SMS notification via Twilio
- [ ] WhatsApp integration
- [ ] Payment gateway integration (Midtrans)
- [ ] QR Code untuk rental tracking
- [ ] Mobile app (React Native)

### Version 1.2.0 (Q4 2026)
- [ ] Multi-language support (EN, ID)
- [ ] Advanced analytics & reporting
- [ ] Customer loyalty program
- [ ] Promo code system
- [ ] Review & rating system
- [ ] Live chat support

### Version 2.0.0 (2027)
- [ ] Microservices architecture
- [ ] API REST untuk third-party integration
- [ ] Machine learning untuk rekomendasi produk
- [ ] Progressive Web App (PWA)
- [ ] Real-time inventory tracking
- [ ] Blockchain untuk transaction history

---

## 📸 Screenshots

### Landing Page
![Landing Page](docs/screenshots/landing.png)

### Product Catalog
![Product Catalog](docs/screenshots/catalog.png)

### Admin Dashboard
![Admin Dashboard](docs/screenshots/admin-dashboard.png)

### Staff Panel
![Staff Panel](docs/screenshots/staff-panel.png)

### Customer Rentals
![Customer Rentals](docs/screenshots/customer-rentals.png)

---

## 🎓 Tutorial & Documentation

### Video Tutorials
- [Instalasi LensCraft](https://youtube.com/watch?v=xxx)
- [Konfigurasi Database](https://youtube.com/watch?v=xxx)
- [Cara Menggunakan Admin Panel](https://youtube.com/watch?v=xxx)
- [Customisasi Theme](https://youtube.com/watch?v=xxx)

### Written Guides
- [Panduan Lengkap Admin](docs/admin-guide.md)
- [Panduan Staff](docs/staff-guide.md)
- [Panduan Customer](docs/customer-guide.md)
- [API Documentation](docs/api-docs.md)
- [Database Schema](docs/database-schema.md)

---

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=username/lenscraft&type=Date)](https://star-history.com/#username/lenscraft&Date)

---

<div align="center">

### 💖 Dibuat dengan penuh dedikasi untuk komunitas rental kamera Indonesia

**Jika projek ini membantu Anda, berikan ⭐ di GitHub!**

[⬆ Kembali ke atas](#-lenscraft---sistem-rental-kamera-profesional)

</div>

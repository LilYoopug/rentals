# 📷 LensCraft - Sistem Rental Kamera Profesional

<div align="center">

![LensCraft Banner](https://img.shields.io/badge/LensCraft-Rental%20System-c7a65a?style=for-the-badge&logo=camera&logoColor=white)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

**Platform rental peralatan fotografi dan videografi profesional dengan sistem manajemen lengkap**

[Fitur](#-fitur-utama) • [Instalasi](#-instalasi) • [Struktur](#-struktur-direktori) • [Database](#-database-schema)

</div>

---

## 🌟 Tentang LensCraft

LensCraft adalah sistem manajemen rental kamera dan peralatan fotografi berbasis web yang dirancang untuk memudahkan proses penyewaan peralatan profesional. Dengan antarmuka modern dan intuitif, LensCraft menyediakan solusi lengkap untuk bisnis rental kamera, dari katalog produk hingga manajemen pengembalian.

### 🎯 Mengapa LensCraft?

- **🎨 UI/UX Premium** - Desain modern dengan Tailwind CSS dan animasi halus
- **👥 Multi-Role System** - Admin, Staff (Petugas), dan Customer (Pelanggan) dengan hak akses berbeda
- **📊 Dashboard Lengkap** - Statistik real-time dan laporan komprehensif
- **🔒 Keamanan Terjamin** - Password hashing, prepared statements, dan session management
- **📱 Responsive Design** - Optimal di semua perangkat (desktop, tablet, mobile)
- **🌐 Bahasa Indonesia** - Interface dalam Bahasa Indonesia

---

## ✨ Fitur Utama

### 👤 Untuk Pelanggan (Customer)
- 🔍 **Katalog Produk Interaktif** - Browse kamera, lensa, dan aksesori dengan filter dan search
- 📅 **Sistem Booking** - Pilih tanggal rental dengan form interaktif
- 💳 **Manajemen Pembayaran** - Upload bukti transfer dan tracking status
- 📦 **Riwayat Rental** - Lihat semua transaksi dan status rental
- 👤 **Profil Pengguna** - Update informasi pribadi dan avatar
- 🔔 **Notifikasi Flash** - Flash messages untuk setiap aksi

### 👨‍💼 Untuk Staff (Petugas)
- ✅ **Approval Rental** - Review dan approve/reject permintaan rental
- 📋 **Manajemen Pengembalian** - Konfirmasi pengembalian dan catat denda
- 💰 **Update Harga & Stok** - Bulk update harga dan stok produk
- 📊 **Dashboard Staff** - Statistik rental aktif dan pending
- 📄 **Export Laporan** - Generate laporan

### 👨‍💻 Untuk Admin
- 🎛️ **Full Control Panel** - Kelola semua aspek sistem
- 👥 **Manajemen User** - CRUD users dengan role management
- 📦 **Manajemen Produk** - Tambah, edit, hapus produk dengan upload gambar
- 🏷️ **Manajemen Kategori** - Organisasi produk berdasarkan kategori
- 💸 **Manajemen Rental** - Override dan kelola semua transaksi
- 📈 **Analytics Dashboard** - Statistik pendapatan dan rental
- 📜 **Activity Log** - Audit trail untuk aktivitas sistem

---

## 🛠️ Teknologi yang Digunakan

### Backend
- **PHP 8.0+** - Server-side scripting
- **MySQL 8.0+** - Relational database
- **MySQLi** - Database driver dengan prepared statements

### Frontend
- **HTML5 & CSS3** - Semantic markup dan modern styling
- **Tailwind CSS 3.x** - Utility-first CSS framework (via CDN)
- **Vanilla JavaScript** - Interactive UI
- **Google Fonts** - Inter & Playfair Display typography

### Security & Best Practices
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

---

## 🚀 Instalasi

### 1️⃣ Clone Repository

```bash
git clone https://github.com/LilYoopug/rentals.git
cd rentals
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

# Pastikan web server memiliki akses write ke folder uploads
chown -R www-data:www-data uploads/
```

### 5️⃣ Konfigurasi Web Server

**Apache:**

```apache
<VirtualHost *:80>
    ServerName lenscraft.local
    DocumentRoot /path/to/rentals
    
    <Directory /path/to/rentals>
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
    root /path/to/rentals;
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
http://localhost/rentals
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

### User (Pengguna)
- **Username:** `user`
- **Password:** `user123`
- **Email:** `user@lenscraft.local`

> ⚠️ **PENTING:** Segera ubah password default setelah login pertama kali!

---

## 📁 Struktur Direktori

```
rentals/
├── admin/                      # Panel admin
│   ├── index.php              # Dashboard admin
│   ├── users.php              # Manajemen user
│   ├── products.php           # Manajemen produk
│   ├── categories.php         # Manajemen kategori
│   ├── borrowings.php         # Manajemen rental
│   ├── returns.php            # Manajemen pengembalian
│   ├── activity-log.php       # Log aktivitas
│   └── _shared/               # Komponen shared admin
├── staff/                      # Panel staff
│   ├── index.php              # Dashboard staff
│   ├── borrowings.php         # Approval rental
│   ├── returns.php            # Konfirmasi pengembalian
│   ├── stock-price.php        # Update stok & harga
│   ├── reports.php            # Laporan
│   └── _shared/               # Komponen shared staff
├── user/                       # Panel customer
│   ├── profile.php            # Profil pengguna
│   ├── rentals.php            # Riwayat rental
│   ├── payment.php            # Upload pembayaran
│   └── settings.php           # Pengaturan
├── process/                    # Backend processors
│   ├── login-process.php
│   ├── register-process.php
│   ├── rental-create-process.php
│   ├── rental-payment-process.php
│   ├── admin-*.php            # Admin processors
│   └── staff-*.php            # Staff processors
├── includes/                   # Helper functions
│   ├── functions.php          # Core functions
│   ├── flash.php              # Flash messages
│   ├── auth-check.php         # Authentication
│   ├── admin-check.php        # Admin guard
│   ├── staff-check.php        # Staff guard
│   ├── customer-check.php     # Customer guard
│   ├── upload.php             # File upload handler
│   └── trigger-functions.php  # Database triggers
├── data/                       # Data access layer
│   ├── products-data.php
│   ├── rentals-data.php
│   ├── returns-data.php
│   ├── categories-data.php
│   ├── payments-data.php
│   ├── activity-data.php
│   └── users/                 # User data modules
├── config/                     # Configuration files
│   ├── koneksi.php            # Database connection
│   └── base_url.php           # Base URL helper
├── database/                   # Database schema
│   └── lenscraft-complete.sql
├── images/                     # Static images
├── uploads/                    # User uploads
│   ├── products/              # Product images
│   └── users/                 # User avatars
├── index.php                   # Landing page
├── login.php                   # Login page
├── register.php                # Registration page
├── products.php                # Katalog produk
├── product-detail.php          # Detail produk
├── logout.php                  # Logout handler
├── forgot-password.php         # Forgot password
├── terms.php                   # Terms of service
├── privacy.php                 # Privacy policy
└── README.md                   # Dokumentasi ini
```

---

## 🔐 Keamanan

LensCraft menerapkan best practices keamanan:

### 1. Password Hashing
```php
// Hash password saat registrasi
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Verifikasi saat login
password_verify($input, $hashed);
```

### 2. SQL Injection Prevention
```php
// Gunakan prepared statements
db_execute('INSERT INTO users (name, email) VALUES (?, ?)', [$name, $email]);
```

### 3. XSS Protection
```php
// Escape output
echo e($user_input);
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
```

### 4. Session Security
```php
// Regenerate session ID setelah login
session_regenerate_id(true);
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
| billing_info | JSON | Informasi billing |
| avatar_path | VARCHAR(255) | Path foto profil |
| created_at | DATETIME | Tanggal registrasi |
| last_active | DATETIME | Terakhir aktif |

#### `categories`
Kategori produk

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| name | VARCHAR(80) | Nama kategori |
| slug | VARCHAR(80) | URL slug (unique) |
| description | TEXT | Deskripsi |
| icon | VARCHAR(60) | Icon kategori |
| color | VARCHAR(30) | Warna kategori |
| status | ENUM | aktif/nonaktif |
| created_at | DATETIME | Tanggal dibuat |

#### `products`
Katalog produk rental

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| category_id | INT | Foreign key ke categories |
| name | VARCHAR(150) | Nama produk |
| brand | VARCHAR(80) | Merek |
| category_slug | VARCHAR(80) | Slug kategori |
| price_per_day | DECIMAL(10,2) | Harga per hari |
| discount_percentage | INT | Diskon (%) |
| description | TEXT | Deskripsi produk |
| image_path | VARCHAR(255) | Path gambar |
| stock_total | INT | Total stok |
| stock_available | INT | Stok tersedia |
| in_stock | TINYINT(1) | Status stok tersedia |
| status | ENUM | aktif/nonaktif |
| created_at | DATETIME | Tanggal dibuat |

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
| discount_percentage | INT | Diskon (%) |
| delivery_method | ENUM | ambil_sendiri/diantar |
| delivery_fee | DECIMAL(10,2) | Biaya antar |
| total_price | DECIMAL(10,2) | Total harga |
| status | ENUM | menunggu/disetujui/mendatang/aktif/selesai/dibatalkan/ditolak |
| cancel_reason | VARCHAR(255) | Alasan pembatalan |
| created_at | DATETIME | Tanggal dibuat |
| approved_at | DATETIME | Tanggal disetujui |
| completed_at | DATETIME | Tanggal selesai |
| cancelled_at | DATETIME | Tanggal dibatalkan |

#### `payments`
Data pembayaran

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| payment_code | VARCHAR(30) | Kode pembayaran (unique) |
| rental_id | INT | Foreign key ke rentals (unique) |
| amount | DECIMAL(10,2) | Jumlah bayar |
| method | VARCHAR(50) | Metode pembayaran |
| status | ENUM | pending/paid |
| payer_name | VARCHAR(120) | Nama pembayar |
| payer_email | VARCHAR(120) | Email pembayar |
| payer_phone | VARCHAR(30) | Telepon pembayar |
| reference_code | VARCHAR(60) | Kode referensi transfer |
| paid_at | DATETIME | Tanggal bayar |
| created_at | DATETIME | Tanggal dibuat |
| updated_at | DATETIME | Tanggal diupdate |

#### `returns`
Data pengembalian

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| return_code | VARCHAR(30) | Kode pengembalian (unique) |
| rental_id | INT | Foreign key ke rentals (unique) |
| processed_by | INT | Foreign key ke users (staff) |
| notes | TEXT | Catatan kondisi |
| fine_amount | DECIMAL(10,2) | Denda (jika ada) |
| status | ENUM | menunggu/selesai |
| returned_at | DATETIME | Tanggal kembali |
| created_at | DATETIME | Tanggal dibuat |

#### `activity_logs`
Log aktivitas sistem

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| user_id | INT | Foreign key ke users |
| actor_name | VARCHAR(120) | Nama pelaku |
| actor_role | VARCHAR(30) | Role pelaku |
| activity_type | VARCHAR(50) | Tipe aktivitas |
| message | TEXT | Pesan log |
| created_at | DATETIME | Waktu aktivitas |

---

## 🔄 Workflow Sistem

### Proses Rental

```
1. Customer Browse Katalog
   ↓
2. Pilih Produk & Set Tanggal
   ↓
3. Submit Request (Status: menunggu)
   ↓
4. Staff Review → Approve/Reject
   ↓
5. Jika Approved (Status: disetujui)
   ↓
6. Customer Upload Bukti Transfer
   ↓
7. Staff Konfirmasi Pembayaran (Status: mendatang/aktif)
   ↓
8. Customer Gunakan Produk
   ↓
9. Customer Kembalikan
   ↓
10. Staff Konfirmasi Pengembalian (Status: selesai)
```

### Status Rental
- **menunggu** - Menunggu approval staff
- **disetujui** - Disetujui, menunggu pembayaran
- **mendatang** - Sudah bayar, belum dimulai
- **aktif** - Sedang berlangsung
- **selesai** - Sudah dikembalikan
- **dibatalkan** - Dibatalkan oleh customer
- **ditolak** - Ditolak oleh staff

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
  - Flash notifications

### Responsive Breakpoints
```css
sm: 640px   /* Small devices */
md: 768px   /* Tablets */
lg: 1024px  /* Laptops */
xl: 1280px  /* Desktops */
2xl: 1536px /* Large screens */
```

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
- [ ] Backup database secara berkala
- [ ] Restrict database access

#### Performance
- [ ] Enable OPcache
- [ ] Enable Gzip compression
- [ ] Configure caching headers

---

## 🤝 Kontribusi

Kontribusi sangat diterima! Berikut cara berkontribusi:

### 1. Fork Repository
```bash
git clone https://github.com/your-username/rentals.git
cd rentals
```

### 2. Buat Branch Baru
```bash
git checkout -b feature/nama-fitur
```

### 3. Commit Changes
```bash
git add .
git commit -m "feat: tambah fitur X"
```

**Commit Message Convention:**
- `feat:` - Fitur baru
- `fix:` - Bug fix
- `docs:` - Update dokumentasi
- `style:` - Formatting
- `refactor:` - Code refactoring

### 4. Push & Pull Request
```bash
git push origin feature/nama-fitur
```

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

## 👨‍💻 Author

**LensCraft Development Team**

- GitHub: [@LilYoopug](https://github.com/LilYoopug)
- Repository: [rentals](https://github.com/LilYoopug/rentals)

---

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) - Utility-first CSS framework
- [Google Fonts](https://fonts.google.com/) - Inter & Playfair Display
- [PHP](https://www.php.net/) - Server-side scripting language
- [MySQL](https://www.mysql.com/) - Relational database management system

---

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=LilYoopug/rentals&type=Date)](https://star-history.com/#LilYoopug/rentals&Date)

---

<div align="center">

### 💖 Dibuat dengan penuh dedikasi untuk komunitas rental kamera Indonesia

**Jika projek ini membantu Anda, berikan ⭐ di GitHub!**

[⬆ Kembali ke atas](#-lenscraft---sistem-rental-kamera-profesional)

</div>

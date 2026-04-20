# Desain Fake Payment Gate Sebelum Aktivasi Rental

## Tujuan

Mengubah alur peminjaman agar persetujuan petugas tidak langsung mengaktifkan rental. Setelah petugas menyetujui permintaan, pelanggan harus menyelesaikan fake payment terlebih dahulu. Setelah fake payment sukses, rental langsung berubah menjadi aktif.

Tujuan utamanya:

- memisahkan persetujuan petugas dari aktivasi rental
- menambahkan halaman checkout internal yang meniru alur pembayaran hosted checkout tanpa API eksternal
- menjaga tampilan tetap konsisten dengan UI proyek yang sudah ada

## Keputusan Produk

- Persetujuan petugas tidak lagi mengubah rental ke `aktif`
- Status baru `disetujui` ditambahkan sebagai state antara `menunggu` dan `aktif`
- Pelanggan membayar dari tombol `Bayar Sekarang` pada baris rental yang sudah disetujui
- Checkout dibuka di halaman baru, bukan modal
- Halaman payment memakai navbar dan footer proyek yang sama
- Konten utama payment tetap memakai bahasa visual proyek yang sama: container, typography, card, form, spacing, dan button yang sudah ada
- Halaman payment tidak memakai floating nav
- Payment bersifat fake: input apa pun yang valid secara form selalu diterima
- Setelah payment sukses, rental langsung menjadi `aktif`

## Alur Pengguna

### Alur rental

1. Pelanggan membuat rental, status awal `menunggu`
2. Petugas menyetujui rental
3. Sistem mengubah rental ke `disetujui`, bukan `aktif`
4. Sistem membuat atau memastikan satu record payment `pending` untuk rental tersebut
5. Di halaman `Rental Saya`, rental yang `disetujui` dan belum dibayar menampilkan tombol `Bayar Sekarang`
6. Pelanggan membuka halaman checkout
7. Pelanggan memilih metode dan mengisi form fake payment
8. Submit payment selalu sukses selama field wajib terisi
9. Sistem menandai payment sebagai `paid` dan rental sebagai `aktif`
10. Pelanggan kembali ke `Rental Saya` dan tombol bayar hilang

### Alur terlarang

- rental `menunggu` tidak boleh dibayar
- rental `ditolak` atau `dibatalkan` tidak boleh dibayar
- rental yang sudah `aktif` tidak boleh dibayar ulang
- payment yang sudah `paid` tidak boleh diproses lagi

## Model Data

### Status rental baru

Status rental akan menjadi:

- `menunggu`
- `disetujui`
- `mendatang`
- `aktif`
- `selesai`
- `dibatalkan`
- `ditolak`

Makna:

- `menunggu`: menunggu review petugas
- `disetujui`: sudah disetujui petugas, menunggu pembayaran
- `aktif`: pembayaran selesai dan rental aktif

### Tabel baru `payments`

Ditambahkan tabel baru `payments` untuk menyimpan status dan detail fake payment secara terpisah dari lifecycle rental.

Kolom yang disarankan:

- `id` INT AUTO_INCREMENT PRIMARY KEY
- `payment_code` VARCHAR(30) UNIQUE
- `rental_id` INT NOT NULL UNIQUE
- `amount` DECIMAL(10,2) NOT NULL
- `method` VARCHAR(50) NOT NULL
- `status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending'
- `payer_name` VARCHAR(120) DEFAULT NULL
- `payer_email` VARCHAR(120) DEFAULT NULL
- `payer_phone` VARCHAR(30) DEFAULT NULL
- `reference_code` VARCHAR(60) DEFAULT NULL
- `paid_at` DATETIME DEFAULT NULL
- `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

Aturan:

- satu rental hanya punya satu payment row
- saat approval, row payment dibuat jika belum ada
- saat payment sukses, row yang sama di-update ke `paid`

### Dampak ke Trigger dan Reservasi Stok

Status `disetujui` harus dianggap sebagai status yang tetap memesan stok, sama seperti `menunggu`, `mendatang`, dan `aktif`.

Tanpa ini, barang bisa tampak tersedia lagi setelah disetujui tetapi sebelum dibayar, yang akan merusak safety flow baru.

Trigger insert, update, dan delete pada `rentals` harus diperbarui agar `disetujui` masuk ke kelompok reserve-stock.

## Arsitektur Perubahan

### File yang ditambahkan

- `data/payments-data.php`
- `user/payment.php`
- `process/rental-payment-process.php`

### File yang diubah

- `database/lenscraft.sql`
- `database/seed-lenscraft.sql`
- `includes/functions.php`
- `data/rentals-data.php`
- `process/staff-peminjaman-approve.php`
- `user/rentals.php`
- halaman admin/staff yang menampilkan status rental

### Tanggung jawab per file

#### `data/payments-data.php`

Pusat logika payment:

- mengambil payment berdasarkan rental
- membuat payment pending
- menandai payment berhasil
- membangun kode payment dan fake reference code
- menyediakan data yang dibutuhkan halaman checkout

#### `data/rentals-data.php`

Menangani:

- pengenalan status `disetujui`
- helper transisi rental
- relasi pembacaan rental dengan payment bila dibutuhkan UI

#### `process/staff-peminjaman-approve.php`

Approval flow baru:

- validasi request
- ubah status rental dari `menunggu` ke `disetujui`
- set timestamp approval
- buat payment pending bila belum ada

#### `user/payment.php`

Menangani halaman checkout:

- validasi rental milik user login
- pastikan rental berada di state `disetujui`
- pastikan payment belum `paid`
- render summary rental
- render form fake payment dengan gaya proyek yang sama

#### `process/rental-payment-process.php`

Menangani submit fake payment:

- validasi ownership
- validasi rental masih `disetujui`
- validasi payment belum `paid`
- validasi field wajib
- update payment menjadi `paid`
- update rental menjadi `aktif`

## Desain UI Halaman Payment

Halaman payment harus memakai shell yang sama dengan halaman pelanggan yang ada:

- navbar yang sama
- footer yang sama
- background dan tone visual yang sama

Tetapi halaman ini tidak memakai floating nav.

Konten utama tidak boleh memakai visual language baru. Implementasi harus menyalin dan menyesuaikan blok UI yang sudah ada di proyek agar tetap terasa satu sistem.

Panduan UI:

- gunakan lebar container yang sama dengan halaman user saat ini
- gunakan typography yang sama
- gunakan card gelap dengan border dan radius yang sama
- gunakan style button dan input yang sama
- gunakan spacing rhythm yang sama

Struktur konten:

- header halaman payment
- card ringkasan rental
- card metode pembayaran
- card form pembayaran
- card total pembayaran dan CTA

Tombol utama:

- label utama `Bayar Sekarang`
- state loading saat submit
- state sukses setelah payment

## Perilaku UI di `Rental Saya`

Pada `user/rentals.php`:

- rental `aktif` tetap menampilkan aksi detail dan pengembalian
- rental `disetujui` + payment `pending` menampilkan `Bayar Sekarang`
- rental lain tetap hanya menampilkan aksi yang relevan

Badge status di UI harus membedakan `disetujui` dari `menunggu` agar user tahu rental sudah lolos review dan tinggal dibayar.

## Error Handling dan Guardrails

- user hanya boleh membuka payment page untuk rental miliknya sendiri
- rental di luar state `disetujui` harus ditolak
- payment yang sudah `paid` harus ditolak untuk submit ulang
- submit dengan field wajib kosong harus menampilkan error yang jelas
- jika rental sudah berubah state saat user membuka page, user diarahkan kembali ke `Rental Saya`
- proses payment harus menolak jika status rental bukan lagi `disetujui`

## Non-Goals

- tidak ada integrasi Midtrans, Xendit, atau gateway nyata
- tidak ada webhook atau callback eksternal
- tidak ada histori banyak attempt per rental
- tidak ada desain visual baru yang berbeda dari sistem LensCraft

## Strategi Testing

Verifikasi minimal:

1. Staff approve rental `menunggu` dan status berubah ke `disetujui`
2. Payment row `pending` terbentuk untuk rental tersebut
3. `Rental Saya` menampilkan tombol `Bayar Sekarang`
4. Payment page bisa dibuka hanya oleh pemilik rental
5. Submit fake payment dengan field wajib terisi berhasil
6. Payment row berubah ke `paid`
7. Rental berubah ke `aktif`
8. Tombol `Bayar Sekarang` hilang setelah sukses
9. Flow pengembalian tetap berjalan untuk rental `aktif`
10. Rental `ditolak`, `dibatalkan`, `aktif`, atau `selesai` tidak bisa masuk flow payment

## Risiko Utama

- perubahan enum status akan menyentuh banyak mapping status di admin, staff, helper, dan UI pelanggan
- trigger stok harus konsisten dengan status baru agar barang tidak double-booked
- beberapa layar saat ini memetakan `aktif` menjadi `approved`, sehingga penambahan `disetujui` perlu pembaruan eksplisit
- jika halaman payment membuat gaya baru, hasilnya akan terasa tidak menyatu dengan proyek

## Ringkasan Implementasi

Perubahan ini memperkenalkan gate pembayaran yang jelas:

- approve petugas menghasilkan `disetujui`
- checkout fake payment terjadi di halaman baru
- payment sukses langsung mengubah rental ke `aktif`

Pendekatan ini menyelesaikan masalah keamanan alur saat ini tanpa membawa kompleksitas gateway nyata, dan tetap mempertahankan konsistensi UI proyek.

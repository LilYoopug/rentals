# Migrasi Bahasa Indonesia dan Rupiah Penuh

## Tujuan

Mengubah seluruh proyek ke Bahasa Indonesia penuh, termasuk:

- semua teks UI dan copy yang masih berbahasa Inggris
- semua tampilan mata uang menjadi Rupiah
- semua nilai bisnis di database, seed data, dan logika aplikasi menjadi istilah Indonesia

## Cakupan

Migrasi ini mencakup:

- halaman publik, halaman user, admin, staff, proses, helper, dan export
- format uang di PHP dan JavaScript
- schema SQL untuk enum/status/nilai bisnis
- seed SQL dan data demo
- test suite yang bergantung pada teks, status, currency, dan nilai DB

## Nilai Internal yang Akan Diubah

Nilai internal yang sebelumnya berbahasa Inggris akan dipindahkan ke Bahasa Indonesia, termasuk:

- role: `admin`, `staff`, `user` menjadi istilah Indonesia
- status user/produk/peminjaman/pengembalian
- metode pengiriman/pengambilan
- category slug dan label yang masih Inggris
- nilai teks demo/seed yang masih Inggris

Schema baru untuk instalasi fresh akan menggunakan nilai Indonesia langsung.

## Strategi Implementasi

Migrasi dilakukan bertahap dengan jendela kompatibilitas sementara:

1. Tambahkan helper normalisasi untuk menerima nilai Inggris dan Indonesia.
2. Pusatkan format mata uang Rupiah di helper PHP dan fungsi JS.
3. Ubah logika aplikasi agar menghasilkan dan membaca nilai Indonesia.
4. Ubah schema dan seed SQL untuk fresh install Indonesia penuh.
5. Ubah test agar memverifikasi perilaku baru.

Pendekatan ini mengurangi risiko breakage saat file lama dan file yang sudah dimigrasikan hidup bersamaan selama rollout.

## Arsitektur Perubahan

- `includes/functions.php` menjadi pusat format Rupiah dan helper normalisasi umum.
- file `data/*.php` menjadi pusat adaptasi nilai DB, status, role, dan method bisnis.
- halaman PHP/JS diperbarui agar tidak menulis string `$...`, `Revenue`, `Active`, `Pending`, dll secara langsung.
- `database/lenscraft.sql` dan `database/seed-lenscraft.sql` menjadi sumber kebenaran baru untuk instalasi Indonesia.

## Risiko

- enum/schema SQL bisa tidak sinkron dengan perbandingan string di PHP/JS
- banyak file admin/staff menggunakan string yang diulang dan perlu konsisten
- test SQL/manual fixture yang menyisipkan nilai Inggris akan gagal setelah schema berubah
- raw JS template strings dengan `$${...}` perlu dicari dan diganti satu per satu

## Aturan Migrasi

- semua teks user-facing harus Indonesia
- semua tampilan uang harus `Rp` dengan format ribuan Indonesia
- tidak boleh ada nilai bisnis Inggris aktif tersisa di schema baru
- test harus menjadi bukti bahwa flow utama tetap hidup setelah migrasi


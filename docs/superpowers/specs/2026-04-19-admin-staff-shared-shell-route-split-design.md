# Desain Refactor Admin/Staff Menjadi Shared Shell + Single-Section Routes

## Tujuan

Menghilangkan file admin/staff yang gemuk dan duplikatif dengan cara:

- mempertahankan URL route yang sekarang
- membuat setiap route hanya merender section miliknya sendiri
- mengekstrak layout bersama (nav, sidebar, footer, modal/detail shell yang memang benar-benar dipakai lintas halaman)
- memindahkan section content ke partial yang fokus

## Masalah Saat Ini

Sebagian besar file `admin/*.php` dan `staff/*.php` saat ini berisi:

- shell layout yang sama berulang
- banyak `content-section` yang tidak relevan dengan route file tersebut
- data preparation untuk beberapa section sekaligus
- JavaScript untuk section lain yang sebenarnya tidak dipakai di route itu

Akibatnya:

- file sangat panjang
- mudah terjadi drift antar halaman
- perubahan kecil harus disentuh di banyak file
- reasoning/debugging jadi mahal

## Pendekatan

Dipakai pendekatan `shared shell + route-specific content`:

- `admin` dan `staff` masing-masing punya shell layout bersama
- setiap route mempersiapkan data yang benar-benar dibutuhkan section itu
- isi utama dipisah ke partial section
- route file menjadi tipis: auth, data load, include shell, render partial

## Struktur Target

### Admin

- shared shell admin untuk navbar, sidebar, footer, bundle script umum
- partial terpisah untuk:
  - overview/dashboard
  - users
  - categories
  - products
  - borrowings
  - returns
  - activity log

### Staff

- shared shell staff untuk navbar, sidebar, footer, bundle script umum
- partial terpisah untuk:
  - overview/dashboard
  - borrowings approval
  - returns monitor
  - reports
  - stock price

## Aturan Refactor

- route sekarang tetap hidup:
  - `/admin/index.php`
  - `/admin/users.php`
  - dst
  - `/staff/index.php`
  - `/staff/borrowings.php`
  - dst
- setiap route hanya memuat section miliknya
- sidebar tetap ada, tetapi link hanya navigasi antar page, bukan toggle section tersembunyi dalam file yang sama
- hindari refactor unrelated pada styling kecuali perlu untuk mendukung split

## Risiko

- JS event handler sekarang mungkin bergantung pada elemen yang hanya ada di route lain
- data arrays/json saat ini dibangun bersama untuk banyak section
- modal/detail helper mungkin dipakai lintas route dan harus dipindah ke shell/helper bersama

## Strategi Aman

1. Buat shared shell terlebih dahulu.
2. Pindahkan satu section menjadi partial.
3. Ubah satu route agar memakai partial itu.
4. Tambah/update test route untuk memastikan page tetap 200 dan memuat section yang benar.
5. Ulangi sampai semua admin dan staff selesai.

## Hasil Akhir yang Diharapkan

- setiap file route jauh lebih kecil
- section lain tidak ikut terbawa
- perubahan future cukup dilakukan di partial/shell yang relevan
- URL tetap sama
- perilaku halaman tetap sama dari sisi user


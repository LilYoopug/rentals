<?php
require_once __DIR__ . '/includes/flash.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Kebijakan Privasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet" />
    <style>
      body { font-family: "Inter", sans-serif; background: #050505; }
      .font-serif { font-family: "Playfair Display", serif; }
    </style>
  </head>
  <body class="text-neutral-100">
    <main class="max-w-3xl mx-auto px-6 py-16 space-y-8">
      <a href="index.php" class="text-sm text-neutral-400 hover:text-white">Kembali ke beranda</a>
      <div class="space-y-3">
        <h1 class="text-4xl font-serif">Kebijakan Privasi</h1>
        <p class="text-neutral-400">LensCraft menyimpan data akun, riwayat rental, dan preferensi aplikasi hanya untuk menjalankan layanan rental dan dukungan pelanggan.</p>
      </div>
      <section class="space-y-3 text-sm text-neutral-300 leading-7">
        <p>Data yang kami simpan meliputi identitas akun, detail kontak, histori rental, dan preferensi pengaturan. Password disimpan dalam bentuk hash dan tidak ditampilkan kembali.</p>
        <p>Anda dapat meminta ekspor data dari halaman privasi akun. Data hanya dibagikan kepada tim operasional internal yang menangani pesanan, dukungan, dan keamanan aplikasi.</p>
      </section>
    </main>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>

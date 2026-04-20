<?php
require_once __DIR__ . '/includes/flash.php';

if (is_logged_in()) {
    redirect_logged_in_user_home();
}

?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Lupa Kata Sandi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --bg-page: #050505;
        --bg-panel-soft: rgba(17, 17, 17, 0.62);
        --border-soft: rgba(255, 255, 255, 0.08);
        --text-soft: #a3a3a3;
        --accent-brass: #c7a65a;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
      }
      body {
        font-family: "Inter", sans-serif;
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.1), transparent 28%),
          radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.05), transparent 22%),
          #050505;
      }
      .font-serif {
        font-family: "Playfair Display", serif;
      }
      .nav-blur {
        background: rgba(5, 5, 5, 0.86);
        backdrop-filter: blur(18px);
      }
      .auth-panel {
        background: linear-gradient(180deg, rgba(22, 22, 22, 0.92), rgba(12, 12, 12, 0.88));
        border: 1px solid var(--border-soft);
        box-shadow:
          0 24px 70px rgba(0, 0, 0, 0.34),
          inset 0 1px 0 rgba(255, 255, 255, 0.04);
      }
      .helper-panel {
        background: var(--bg-panel-soft);
        border: 1px solid rgba(255, 255, 255, 0.06);
      }
      .button-primary {
        transition:
          transform 0.25s ease,
          box-shadow 0.25s ease,
          background-color 0.25s ease;
      }
      .button-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(0, 0, 0, 0.24);
      }
      .input-focus {
        transition:
          border-color 0.2s ease,
          box-shadow 0.2s ease,
          background-color 0.2s ease;
      }
      .input-focus:focus {
        border-color: var(--accent-brass);
        box-shadow: 0 0 0 3px var(--accent-brass-soft);
        background-color: rgba(32, 32, 32, 0.96);
      }
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .animate-fade-in {
        opacity: 0;
        animation: fadeInUp 0.8s ease-out forwards;
      }
    </style>
  </head>

  <body class="bg-neutral-950 text-neutral-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 nav-blur border-b border-neutral-800">
      <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">
          LensCraft
        </a>
        <div class="text-sm text-neutral-400">
          Bantuan akses akun
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center pt-24 pb-12 px-6">
      <div class="max-w-md w-full space-y-8 animate-fade-in">
        <!-- Header -->
        <div class="text-center space-y-4">
          <h1 class="text-4xl font-serif mt-4">Lupa kata sandi?</h1>
          <p class="text-neutral-400 text-sm leading-relaxed max-w-sm mx-auto">
            Masukkan email terdaftar dan kami akan mencatat permintaan verifikasi akun Anda melalui email.
          </p>
        </div>

        <!-- Lupa Kata Sandi Form -->
        <form class="space-y-6 auth-panel p-8 rounded-[1.75rem]" action="process/forgot-password-process.php" method="POST">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-neutral-300 mb-2">
              Alamat Email
            </label>
            <input
              type="email"
              id="email"
              name="email"
              required
              class="w-full px-4 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Masukkan email yang terdaftar"
            />
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="w-full py-3 bg-white text-black font-semibold rounded-xl hover:bg-neutral-200 button-primary active:scale-[0.98]"
          >
            Kirim permintaan verifikasi
          </button>

          <!-- Back to Login Link -->
          <div class="text-center text-sm text-neutral-400">
            Sudah ingat kata sandi?
            <a href="login.php" class="text-white hover:underline font-medium">Kembali ke masuk</a>
          </div>
        </form>

        <!-- Info Box -->
        <div class="helper-panel p-6 rounded-2xl">
          <h3 class="text-sm font-semibold text-neutral-300 mb-2">Langkah berikutnya</h3>
          <ul class="text-xs text-neutral-500 space-y-2">
            <li>• Periksa inbox email Anda, termasuk folder spam.</li>
            <li>• Tim kami akan memverifikasi permintaan tersebut.</li>
            <li>• Perubahan kata sandi hanya tersedia setelah Anda masuk ke akun.</li>
            <li>• Jika masih bisa masuk, gunakan menu Keamanan di akun Anda.</li>
          </ul>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-neutral-500 pt-4">
          <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        </div>
      </div>
    </main>
  <?= page_runtime_bundle($flash_script) ?>
</body>
</html>

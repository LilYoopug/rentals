<?php
require_once __DIR__ . '/includes/flash.php';

if (is_logged_in()) {
    $role = normalize_role_value(current_user()['role'] ?? 'pelanggan');
    if ($role === 'admin') {
        redirect_to('admin/index.php');
    }
    if ($role === 'petugas') {
        redirect_to('staff/index.php');
    }
    redirect_to('products.php');
}

// Get old input values if validation failed
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$requestedProductId = trim((string) ($_GET['product_id'] ?? ''));
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Masuk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --bg-page: #050505;
        --bg-panel: rgba(17, 17, 17, 0.84);
        --bg-panel-soft: rgba(17, 17, 17, 0.62);
        --bg-field: rgba(28, 28, 28, 0.92);
        --border-soft: rgba(255, 255, 255, 0.08);
        --border-strong: rgba(255, 255, 255, 0.14);
        --text-soft: #a3a3a3;
        --text-muted: #737373;
        --accent-brass: #c7a65a;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
      }
      body {
        font-family: "Inter", sans-serif;
        background:
          radial-gradient(circle at top left, rgba(199, 166, 90, 0.1), transparent 28%),
          radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.05), transparent 22%),
          var(--bg-page);
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
      .subtle-link {
        color: #e5e5e5;
        text-decoration-color: rgba(199, 166, 90, 0.6);
        text-underline-offset: 0.24em;
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
      /* Animations */
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
      .delay-100 {
        animation-delay: 0.1s !important;
      }
      .delay-200 {
        animation-delay: 0.2s !important;
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
          Akses rental premium
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center py-12 px-6">
      <div class="max-w-md w-full space-y-6 animate-fade-in">
        <!-- Header -->
        <div class="text-center space-y-4">
          <h1 class="text-3xl font-serif mt-2">Masuk kembali</h1>
          <p class="text-neutral-400 text-sm leading-relaxed max-w-sm mx-auto">
            Lanjutkan ke katalog, kelola rental aktif, dan pantau permintaan dari satu ruang kerja yang sama.
          </p>
        </div>

        <!-- Login Form -->
        <form class="space-y-4 auth-panel p-6 rounded-[1.75rem]" id="login-form" action="process/login-process.php" method="POST">
          <?php if ($requestedProductId !== ""): ?>
            <input type="hidden" name="product_id" value="<?= e($requestedProductId) ?>" />
          <?php endif; ?>
          <?php echo csrf_input(); ?>
          <!-- Username -->
          <div>
            <label for="username" class="block text-sm font-medium text-neutral-300 mb-2">
              Username atau Email
            </label>
            <input
              type="text"
              id="username"
              name="username"
              required
              value="<?= htmlspecialchars($old_input['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              class="w-full px-4 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Masukkan username atau email"
            />
          </div>

          <!-- Password -->
          <div class="relative">
            <label for="password" class="block text-sm font-medium text-neutral-300 mb-2">
              Kata Sandi
            </label>
            <input
              type="password"
              id="password"
              name="password"
              required
              class="w-full px-4 pr-12 py-3 bg-neutral-800/90 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 input-focus outline-none"
              placeholder="Masukkan kata sandi"
            />
            <button
              type="button"
              onclick="togglePassword('password', this)"
              class="absolute right-3 top-9 text-neutral-400 hover:text-white transition-colors p-1"
              aria-label="Tampilkan atau sembunyikan kata sandi"
            >
              <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>

          <!-- Forgot Password -->
          <div class="flex items-center justify-end text-sm">
            <a href="forgot-password.php" class="text-neutral-400 hover:text-white transition-colors">
              Lupa kata sandi?
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="w-full py-3 bg-white text-black font-semibold rounded-xl hover:bg-neutral-200 button-primary active:scale-[0.98]"
            id="login-btn"
          >
            Masuk
          </button>

          <!-- Register Link -->
          <div class="text-center text-sm text-neutral-400">
            Belum punya akun?
            <a href="register.php" class="subtle-link hover:text-white font-medium">Buat akun</a>
          </div>
        </form>
      </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 border-t border-neutral-800">
      <div class="text-center text-xs text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Antarmuka alur login dan katalog LensCraft.</p>
      </div>
    </footer>

    <script>
      function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('svg');
        if (input.type === 'password') {
          input.type = 'text';
          icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
        } else {
          input.type = 'password';
          icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        }
      }
    </script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>

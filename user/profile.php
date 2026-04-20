<?php
require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/users/customer-data.php';
require_once __DIR__ . '/../data/settings-data.php';
require_once __DIR__ . '/../includes/flash.php';
$user = find_user_by_id((int) current_user()['id']);
$settings = get_user_settings_row((int) current_user()['id']);
$user_json = json_encode($user, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$settings_json = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$avatar_url = !empty($user['avatar_path']) ? '../' . ltrim((string) $user['avatar_path'], '/') : '';
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Pengaturan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      body { font-family: "Inter", sans-serif; }
      .font-serif { font-family: "Playfair Display", serif; }
      .nav-blur { background: rgba(5,5,5,0.86); backdrop-filter: blur(18px); }
      .sidebar-blur { background: rgba(5,5,5,0.94); backdrop-filter: blur(18px); }
      @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
      .animate-fade-in { opacity: 0; animation: fadeInUp 0.8s ease-out forwards; }
      .sidebar-scroll::-webkit-scrollbar { width: 6px; }
      .sidebar-scroll::-webkit-scrollbar-thumb { background-color: #333; border-radius: 3px; }
      .nav-item-active { background-color: rgba(199,166,90,0.18); border-left: 3px solid #c7a65a; color: #fff; }
      a[data-section="danger-zone"].nav-item-active { background-color: rgba(239,68,68,0.1); border-left-color: #f87171; color: #f87171; }
      .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; border: 1px solid; }
      .badge-success { background-color: rgba(34,197,94,0.15); color: #4ade80; border-color: rgba(34,197,94,0.3); }
      .badge-warning { background-color: rgba(251,191,36,0.15); color: #fbbf24; border-color: rgba(251,191,36,0.3); }
      .badge-danger { background-color: rgba(239,68,68,0.15); color: #f87171; border-color: rgba(239,68,68,0.3); }
      .input-focus:focus { border-color: #c7a65a; box-shadow: 0 0 0 3px rgba(199,166,90,0.18); }

      /* Light Tema Variables */
      :root {
        --light-bg-primary: #ffffff;
        --light-bg-secondary: #f9fafb;
        --light-bg-tertiary: #f3f4f6;
        --light-text-primary: #111827;
        --light-text-secondary: #4b5563;
        --light-text-tertiary: #9ca3af;
        --light-border: #e5e7eb;
        --light-border-hover: #d1d5db;
        --light-accent: #2563eb;
        --light-accent-hover: #1d4ed8;
        --light-input-bg: #ffffff;
        --light-card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --light-card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      }

      /* Light Tema Overrides */
      body.light-mode {
        background-color: var(--light-bg-secondary);
        color: var(--light-text-primary);
      }

      body.light-mode .nav-blur {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        border-bottom-color: var(--light-border);
      }

      body.light-mode .sidebar-blur {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(12px);
        border-right-color: var(--light-border);
      }

      body.light-mode .font-serif {
        font-family: "Playfair Display", serif;
        color: var(--light-text-primary);
      }

      /* Text Colors */
      body.light-mode .text-neutral-100 { color: var(--light-text-primary); }
      body.light-mode .text-neutral-200 { color: #374151; }
      body.light-mode .text-neutral-300 { color: #6b7280; }
      body.light-mode .text-neutral-400 { color: var(--light-text-secondary); }
      body.light-mode .text-neutral-500 { color: var(--light-text-tertiary); }

      /* Background Colors */
      body.light-mode .bg-neutral-950 { background-color: var(--light-bg-secondary); }
      body.light-mode .bg-neutral-900 { background-color: var(--light-bg-primary); }
      body.light-mode .bg-neutral-800 { background-color: var(--light-bg-tertiary); }
      body.light-mode .bg-neutral-700 { background-color: #e5e7eb; }
      body.light-mode .bg-neutral-100 { background-color: var(--light-bg-secondary); }

      /* Border Colors */
      body.light-mode .border-neutral-800 { border-color: var(--light-border); }
      body.light-mode .border-neutral-700 { border-color: var(--light-border-hover); }
      body.light-mode .divide-neutral-800 > :not([hidden]) ~ :not([hidden]) {
        border-color: var(--light-border);
      }

      /* Card & Hover Effects */
      body.light-mode .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: var(--light-card-hover-shadow);
      }

      body.light-mode .shadow-lg {
        box-shadow: var(--light-card-shadow);
      }

      /* Input Fields */
      body.light-mode input,
      body.light-mode textarea,
      body.light-mode select {
        background-color: var(--light-input-bg);
        border-color: var(--light-border);
        color: var(--light-text-primary);
      }

      body.light-mode input:focus,
      body.light-mode textarea:focus,
      body.light-mode select:focus {
        border-color: var(--light-accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
      }

      body.light-mode input::placeholder {
        color: var(--light-text-tertiary);
      }

      /* Buttons */
      body.light-mode button.bg-white {
        background-color: var(--light-text-primary) !important;
        color: white !important;
      }

      body.light-mode button.bg-white:hover {
        background-color: #374151 !important;
      }

      body.light-mode button.bg-neutral-800 {
        background-color: var(--light-bg-tertiary) !important;
        color: var(--light-text-primary) !important;
        border-color: var(--light-border);
      }

      body.light-mode button.bg-neutral-800:hover {
        background-color: var(--light-border) !important;
      }

      /* Badges in Mode Terang */
      body.light-mode .badge-success {
        background-color: rgba(34, 197, 94, 0.1);
        color: #16a34a;
        border-color: rgba(34, 197, 94, 0.2);
      }

      body.light-mode .badge-warning {
        background-color: rgba(251, 191, 36, 0.1);
        color: #d97706;
        border-color: rgba(251, 191, 36, 0.2);
      }

      body.light-mode .badge-danger {
        background-color: rgba(239, 68, 68, 0.1);
        color: #dc2626;
        border-color: rgba(239, 68, 68, 0.2);
      }

      body.light-mode .badge-info {
        background-color: rgba(59, 130, 246, 0.1);
        color: #2563eb;
        border-color: rgba(59, 130, 246, 0.2);
      }

      /* Nav Items */
      body.light-mode .nav-item {
        color: var(--light-text-secondary);
      }

      body.light-mode .nav-item:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: var(--light-text-primary);
      }

      body.light-mode .nav-item-active {
        background-color: rgba(37, 99, 235, 0.1);
        border-left-color: var(--light-accent);
        color: var(--light-accent);
      }

      /* Checkbox/Radio custom styles for light mode */
      body.light-mode input[type="checkbox"]:checked,
      body.light-mode input[type="radio"]:checked {
        background-color: var(--light-accent);
        border-color: var(--light-accent);
      }

      /* Toggle switch thumb - unchecked state (light track, dark thumb) */
      body.light-mode input.peer:not(:checked) + span::after {
        background-color: var(--light-text-primary) !important;
        border-color: var(--light-border) !important;
      }

      /* Toggle switch track when checked */
      body.light-mode input.peer:checked + span {
        background-color: var(--light-accent) !important;
      }

      /* Toggle switch thumb when checked - keep white for contrast on blue */
      body.light-mode input.peer:checked + span::after {
        background-color: white !important;
        border-color: white !important;
      }

      /* Hover states for cards in light mode */
      body.light-mode .bg-neutral-900 {
        background-color: var(--light-bg-primary);
        border-color: var(--light-border);
      }

      body.light-mode .bg-neutral-800 {
        background-color: var(--light-bg-tertiary);
        border-color: var(--light-border);
      }

      body.light-mode .bg-neutral-800.rounded-lg:hover {
        background-color: #e5e7eb;
      }

      /* Form labels in light mode */
      body.light-mode label.text-sm.font-medium.text-neutral-400 {
        color: var(--light-text-secondary);
      }

      /* Placeholder text */
      body.light-mode ::placeholder {
        color: var(--light-text-tertiary);
      }

      /* SVG icons in nav */
      body.light-mode .nav-item svg {
        color: var(--light-text-secondary);
      }

      body.light-mode .nav-item-active svg {
        color: var(--light-accent);
      }

      /* Footer section styling */
      body.light-mode .border-t.border-neutral-800 {
        border-top-color: var(--light-border) !important;
      }

      body.light-mode .text-xs.text-neutral-500 {
        color: var(--light-text-tertiary) !important;
      }

      /* Alert messages (if any) */
      body.light-mode .alert {
        background-color: rgba(37, 99, 235, 0.1);
        border-color: rgba(37, 99, 235, 0.2);
        color: var(--light-text-primary);
      }

      /* Form checkboxes & radios */
      body.light-mode input[type="checkbox"],
      body.light-mode input[type="radio"] {
        border-color: var(--light-border);
        background-color: var(--light-input-bg);
      }

      body.light-mode input[type="checkbox"]:checked,
      body.light-mode input[type="radio"]:checked {
        background-color: var(--light-accent);
        border-color: var(--light-accent);
      }

      /* Selection color */
      body.light-mode ::selection {
        background-color: rgba(37, 99, 235, 0.3);
      }

      /* Focus states */
      body.light-mode *:focus-visible {
        outline: 2px solid var(--light-accent);
        outline-offset: 2px;
      }

      /* Better button contrast */
      body.light-mode button.bg-neutral-800 {
        color: var(--light-text-primary);
      }

      /* Better icon contrast inside neutral-800 containers */
      body.light-mode .bg-neutral-800 .text-neutral-500,
      body.light-mode .bg-neutral-800 .text-neutral-400 {
        color: var(--light-text-secondary) !important;
      }

      /* Hover states for buttons */
      body.light-mode button.bg-neutral-800:hover {
        background-color: var(--light-border) !important;
      }

      /* Card shadows for depth */
      body.light-mode .bg-neutral-900,
      body.light-mode .bg-neutral-800 {
        box-shadow: var(--light-card-shadow);
      }

      body.light-mode .card-hover:hover {
        box-shadow: var(--light-card-hover-shadow);
      }

      /* Section titles - override text-white in light mode */
      body.light-mode .text-white.font-serif {
        color: var(--light-text-primary) !important;
      }

      body.light-mode .text-lg.font-semibold.text-white {
        color: var(--light-text-primary) !important;
      }

      /* Logo in navbar */
      body.light-mode .font-serif.text-white {
        color: var(--light-text-primary) !important;
      }

      /* Danger zone title kept red in light mode */
      body.light-mode .text-red-500 {
        color: #dc2626 !important;
      }

      body.light-mode .text-red-400 {
        color: #ef4444 !important;
      }

      /* Badge danger text stays red */
      body.light-mode .badge-danger {
        color: #dc2626 !important;
      }

      /* Notification type cards - title and subtitle text */
      body.light-mode label .font-medium.text-white {
        color: var(--light-text-primary) !important;
      }

      body.light-mode label .text-xs.text-neutral-500 {
        color: var(--light-text-tertiary) !important;
      }

      /* Description text under section headings */
      body.light-mode .text-sm.text-neutral-400 {
        color: var(--light-text-secondary) !important;
      }

      /* Card content text */
      body.light-mode .bg-neutral-800 .font-medium {
        color: var(--light-text-primary) !important;
      }

      body.light-mode .bg-neutral-800 .text-sm.text-neutral-400 {
        color: var(--light-text-secondary) !important;
      }

      /* Navbar user info - override text-white */
      body.light-mode .nav-blur .text-sm.font-medium.text-white {
        color: var(--light-text-primary) !important;
      }

      body.light-mode .nav-blur .text-xs.text-neutral-500 {
        color: var(--light-text-tertiary) !important;
      }

      /* Navbar logout icon */
      body.light-mode .nav-blur a[title="Logout"] {
        color: var(--light-text-secondary) !important;
      }

      body.light-mode .nav-blur a[title="Logout"]:hover {
        color: var(--light-text-primary) !important;
      }

      /* Privacy settings toggle labels */
      body.light-mode .flex.items-start.justify-between .font-medium.text-white {
        color: var(--light-text-primary) !important;
      }

      /* Metadata text (description under toggle labels) */
      body.light-mode .flex.items-start.justify-between .text-sm.text-neutral-400 {
        color: var(--light-text-secondary) !important;
      }

      /* Connected apps section titles */
      body.light-mode .bg-neutral-900 .text-lg.font-semibold.text-white {
        color: var(--light-text-primary) !important;
      }
body.light-mode .bg-neutral-900 .text-sm.text-neutral-400 {
  color: var(--light-text-secondary) !important;
}

/* Floating middle navigation */
.floating-nav {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(17, 17, 17, 0.6);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 1.6rem;
  padding: 0.8rem 1.3rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
  z-index: 40;
  transition:
    background 0.2s ease,
    opacity 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.floating-nav:hover {
  background: rgba(17, 17, 17, 0.75);
}

.floating-nav.footer-near:not(:hover) {
  background: rgba(17, 17, 17, 0.2);
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
  opacity: 0.2;
}

.floating-nav-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 3.75rem;
  width: auto;
  height: 3.75rem;
  border-radius: 1.05rem;
  padding: 0 0.55rem;
  background: transparent;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  transition: all 0.2s ease;
}

.floating-nav-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #f3f4f6;
}

.floating-nav-btn.active {
  background: linear-gradient(135deg, #c7a65a 0%, #8f6421 100%);
  color: white;
}

.floating-nav-btn svg {
  width: 1.5rem;
  height: 1.5rem;
}

.floating-nav-btn span {
  font-size: 0.58rem;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0.02em;
  white-space: nowrap;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .floating-nav {
    bottom: 1rem;
    padding: 0.6rem 1rem;
    gap: 0.55rem;
  }
  .floating-nav-btn {
    min-width: 3.1rem;
    width: auto;
    height: 3.1rem;
    padding: 0 0.45rem;
  }
  .floating-nav-btn svg {
    width: 1.18rem;
    height: 1.18rem;
  }
  .floating-nav-btn span {
    font-size: 0.49rem;
  }
}
</style>
    }
   </head>
  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <div class="flex items-center gap-4">
          <a href="index.php" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Pengaturan</span>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
            <div class="text-right hidden sm:block">
              <div class="text-sm font-medium text-white"><?= e((string) ($user['fullname'] ?? 'User Name')) ?></div>
              <div class="text-xs text-neutral-500">Sudah masuk</div>
            </div>
            <div class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 overflow-hidden">
              <?php if ($avatar_url !== ''): ?>
                <img src="<?= e($avatar_url) ?>" alt="Profile avatar" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
              <?php endif; ?>
              <svg class="w-5 h-5 text-neutral-400" style="<?= $avatar_url !== '' ? 'display:none;' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <a href="../logout.php" class="text-sm text-neutral-400 hover:text-white transition-colors" title="Logout">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </nav>
    <main class="pt-16 min-h-screen">
      <div class="px-6 md:px-8 pt-4 pb-8">
        <div id="content-area" class="mx-auto w-full max-w-7xl space-y-8">
          <section id="profile" class="settings-section animate-fade-in">
            <div class="w-full">
              <h2 class="text-2xl font-bold text-white font-serif mb-2">Profil</h2>
              <p class="text-neutral-400 text-sm mb-8">Kelola informasi pribadi dan foto profil Anda.</p>
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Profil Picture</h3>
                <div class="flex items-start gap-6">
                  <div class="w-24 h-24 bg-neutral-800 rounded-full flex items-center justify-center border border-neutral-700 overflow-hidden">
                    <?php if ($avatar_url !== ''): ?>
                      <img id="profile-avatar-preview" src="<?= e($avatar_url) ?>" alt="Profile avatar" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <?php else: ?>
                      <img id="profile-avatar-preview" src="" alt="Profile avatar" class="w-full h-full object-cover hidden">
                    <?php endif; ?>
                    <svg class="w-12 h-12 text-neutral-500" id="profile-avatar-fallback" style="<?= $avatar_url !== '' ? 'display:none;' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <div class="space-y-3">
                      <label for="profile-avatar-file" class="inline-flex px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors cursor-pointer">Unggah Foto Baru</label>
                      <input type="file" id="profile-avatar-file" name="avatar_file" accept="image/*" class="hidden">
                      <button type="button" id="remove-profile-avatar" class="px-4 py-2 bg-transparent border border-neutral-700 rounded-lg text-sm font-medium text-neutral-400 hover:text-white transition-colors">Hapus</button>
                    </div>
                    <p class="text-xs text-neutral-500 mt-3">Disarankan: gambar persegi minimal 200x200px. Maksimal 2MB.</p>
                  </div>
                </div>

                <form id="profile-form" class="space-y-5" action="../process/profile-update-process.php" method="POST" enctype="multipart/form-data">
                <?= csrf_input() ?>
                <input type="hidden" name="existing_avatar_path" id="existing-avatar-path" value="<?= e((string) ($user['avatar_path'] ?? '')) ?>">
                <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6">
                  <h3 class="text-lg font-semibold text-white mb-4">Informasi Pribadi</h3>
                    <div class="grid md:grid-cols-2 gap-5">
                      <div>
                        <label for="first-name" class="block text-sm font-medium text-neutral-400 mb-2">Nama Depan</label>
                        <input type="text" id="first-name" name="first_name" maxlength="50" required
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="Enter your first name">
                      </div>
                      <div>
                        <label for="last-name" class="block text-sm font-medium text-neutral-400 mb-2">Nama Belakang</label>
                        <input type="text" id="last-name" name="last_name" maxlength="50" required
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="Enter your last name">
                      </div>
                    </div>
                    <div>
                      <label for="email" class="block text-sm font-medium text-neutral-400 mb-2">Alamat Email</label>
                      <input type="email" id="email" name="email" maxlength="100" required
                        class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="you@example.com">
                    </div>
                    <div>
                      <label for="phone" class="block text-sm font-medium text-neutral-400 mb-2">Nomor Telepon</label>
                      <input type="tel" id="phone" name="phone" maxlength="20" required
                        class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="+62 812-3456-7890">
                    </div>
                </div>
 
                <!-- Address Section - Separate from Informasi Pribadi -->
                <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
                  <h3 class="text-lg font-semibold text-white mb-4">Alamat Pengiriman</h3>
                  <div class="space-y-4">
                    <div>
                      <label for="address-line1" class="block text-sm font-medium text-neutral-400 mb-2">Alamat Baris 1</label>
                      <input type="text" id="address-line1" name="address_line1" maxlength="100"
                        class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="Nama jalan, nomor rumah, atau kotak pos">
                    </div>
                    
                    <div>
                      <label for="address-line2" class="block text-sm font-medium text-neutral-400 mb-2">Alamat Baris 2 (Opsional)</label>
                      <input type="text" id="address-line2" name="address_line2" maxlength="100"
                        class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="Apartemen, suite, unit, gedung, lantai, dan sebagainya">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                      <div>
                        <label for="city" class="block text-sm font-medium text-neutral-400 mb-2">Kota</label>
                        <input type="text" id="city" name="city" maxlength="50"
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="Kota">
                      </div>
                      <div>
                        <label for="province" class="block text-sm font-medium text-neutral-400 mb-2">Provinsi</label>
                        <input type="text" id="province" name="province" maxlength="50"
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="Province or state">
                      </div>
                      <div>
                        <label for="zip-code" class="block text-sm font-medium text-neutral-400 mb-2">Kode Pos</label>
                        <input type="text" id="zip-code" name="zip_code" maxlength="20"
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="12345">
                      </div>
                      <div>
                        <label for="country" class="block text-sm font-medium text-neutral-400 mb-2">Negara</label>
                        <select id="country" name="country"
                          class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:border-neutral-600">
                          <option value="">Pilih negara</option>
                          <option value="ID">Indonesia</option>
                          <option value="US">United States</option>
                          <option value="GB">United Kingdom</option>
                          <option value="CA">Canada</option>
                          <option value="AU">Australia</option>
                          <option value="DE">Germany</option>
                          <option value="FR">France</option>
                          <option value="JP">Japan</option>
                          <option value="KR">South Korea</option>
                          <option value="SG">Singapore</option>
                          <option value="MY">Malaysia</option>
                          <option value="TH">Thailand</option>
                          <option value="VN">Vietnam</option>
                          <option value="PH">Philippines</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
 
                  <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                    <button type="button" class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-2 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">Simpan Perubahan</button>
                  </div>
                </form>
              </div>
            </div>
          </section>
          <section id="security" class="settings-section">
            <div class="w-full">
              <h2 class="text-2xl font-bold text-white font-serif mb-2">Keamanan</h2>
              <p class="text-neutral-400 text-sm mb-8">Kelola kata sandi, keamanan akun, dan sesi aktif Anda.</p>

              <!-- Ubah Kata Sandi -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-semibold text-white">Ubah Kata Sandi</h3>
                  <span class="badge badge-success" id="password-status">Terkini</span>
                </div>
                <form id="password-form" class="space-y-5" action="../process/password-update-process.php" method="POST">
                  <?= csrf_input() ?>
                  <div>
                    <label for="current-password" class="block text-sm font-medium text-neutral-400 mb-2">Kata Sandi Saat Ini</label>
                    <div class="relative">
                      <input type="password" id="current-password" name="current_password" maxlength="100" required
                        class="w-full px-4 py-3 pr-12 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="Enter current password"
                        aria-describedby="current-password-hint"
                        autocomplete="current-password"
                      >
                      <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-neutral-300 transition-colors toggle-password" tabindex="-1" aria-label="Tampilkan atau sembunyikan kata sandi">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                    </div>
                    <p id="current-password-hint" class="text-xs text-neutral-500 mt-2">Wajib diisi untuk memverifikasi identitas Anda.</p>
                  </div>

                  <div class="grid md:grid-cols-2 gap-5">
                    <div>
                      <label for="new-password" class="block text-sm font-medium text-neutral-400 mb-2">Kata Sandi Baru</label>
                      <div class="relative">
                        <input type="password" id="new-password" name="new_password" maxlength="100" required
                          class="w-full px-4 py-3 pr-12 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                          placeholder="Enter new password"
                          autocomplete="new-password"
                          aria-describedby="new-password-hint password-strength"
                        >
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-neutral-300 transition-colors toggle-password" tabindex="-1" aria-label="Tampilkan atau sembunyikan kata sandi">
                          <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                        </button>
                      </div>
                      <p id="new-password-hint" class="text-xs text-neutral-500 mt-2">Minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol.</p>
                      <div id="password-strength" class="mt-3 hidden">
                        <div class="flex items-center justify-between mb-2">
                          <span class="text-xs font-semibold text-neutral-500">Kekuatan kata sandi</span>
                          <span class="text-xs font-semibold" id="strength-label">Lemah</span>
                        </div>
                        <div class="w-full h-2 bg-neutral-800 rounded-full overflow-hidden">
                          <div id="strength-bar" class="h-full bg-red-500 transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <ul id="strength-criteria" class="mt-2 space-y-1 text-xs text-neutral-500">
                          <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-neutral-700" data-criterion="length"></span> Minimal 8 karakter</li>
                          <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-neutral-700" data-criterion="uppercase"></span> Mengandung huruf besar</li>
                          <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-neutral-700" data-criterion="lowercase"></span> Mengandung huruf kecil</li>
                          <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-neutral-700" data-criterion="number"></span> Mengandung angka</li>
                          <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-neutral-700" data-criterion="special"></span> Mengandung simbol khusus</li>
                        </ul>
                      </div>
                    </div>

                    <div>
                      <label for="confirm-password" class="block text-sm font-medium text-neutral-400 mb-2">Confirm Kata Sandi Baru</label>
                      <input type="password" id="confirm-password" name="confirm_password" maxlength="100" required
                        class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 placeholder-neutral-500 input-focus outline-none focus:border-neutral-600"
                        placeholder="Masukkan ulang kata sandi baru"
                        autocomplete="new-password"
                        aria-describedby="confirm-password-hint"
                      >
                      <p id="confirm-password-hint" class="text-xs text-neutral-500 mt-2">Harus sama dengan kata sandi baru di atas.</p>
                    </div>
                  </div>

                  <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-800">
                    <button type="button" class="px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg text-sm font-medium text-neutral-300 hover:bg-neutral-700 transition-colors">
                      Batal
                    </button>
                    <button type="submit" class="px-6 py-2 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors">
                      Perbarui Kata Sandi
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </section>
          <section id="appearance" class="settings-section">
            <div class="w-full">
              <h2 class="text-2xl font-bold text-white font-serif mb-2">Tampilan</h2>
              <p class="text-neutral-400 text-sm mb-8">Atur tampilan LensCraft sesuai preferensi Anda.</p>

              <!-- Preferensi Aplikasi (now first) -->
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Preferensi Aplikasi</h3>
                <div class="space-y-5">
                  <div>
                    <label for="language" class="block text-sm font-medium text-neutral-400 mb-2">Bahasa</label>
                    <select id="language"
                      class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:border-neutral-600 transition-colors">
                      <option value="en" selected>Inggris</option>
                      <option value="id">Bahasa Indonesia</option>
                    </select>
                    <p class="text-xs text-neutral-500 mt-2">Pilih bahasa yang Anda gunakan untuk antarmuka aplikasi.</p>
                  </div>
                  <div>
                    <label for="timezone" class="block text-sm font-medium text-neutral-400 mb-2">Zona Waktu</label>
                    <select id="timezone"
                      class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg text-neutral-100 focus:outline-none focus:border-neutral-600 transition-colons">
                      <option value="Asia/Jakarta" selected>(GMT+7) Asia/Jakarta</option>
                      <option value="Asia/Makassar">(GMT+8) Asia/Makassar</option>
                      <option value="Asia/Jayapura">(GMT+9) Asia/Jayapura</option>
                    </select>
                    <p class="text-xs text-neutral-500 mt-2">Semua tanggal dan waktu akan ditampilkan menggunakan zona waktu ini.</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-neutral-400 mb-2">Tema</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" id="theme-toggle" class="sr-only peer">
                      <span class="w-11 h-6 bg-neutral-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-800 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></span>
                      <span class="ml-3 text-sm font-medium text-neutral-300">Mode Terang</span>
                    </label>
                    <p class="text-xs text-neutral-500 mt-2">Ganti antara mode gelap dan mode terang.</p>
                  </div>
                </div>
              </div>

               <!-- Save Button -->
               <div class="flex justify-end">
                 <button type="button" class="px-6 py-2 bg-white text-black font-semibold rounded-lg hover:bg-neutral-200 transition-colors"
                   onclick="submitAppearanceSettings()">
                   Simpan Preferensi
                 </button>
               </div>
             </div>
          </section>

        </div>
      </div>
    </main>

    <footer class="border-t border-neutral-800 py-12 bg-neutral-900/50">
      <div class="px-6 md:px-8 text-center text-sm text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Semua rental memerlukan registrasi akun dan persetujuan admin.</p>
      </div>
    </footer>

    <!-- Floating Middle Navigation -->
    <!-- Floating Middle Navigation -->
    <nav class="floating-nav" role="navigation" aria-label="Quick navigation">
      <button class="floating-nav-btn" data-nav="home" aria-label="Beranda">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Beranda</span>
      </button>
      <button class="floating-nav-btn" data-nav="rentals" aria-label="My Rental">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
        </svg>
        <span>Rental</span>
      </button>
      <button class="floating-nav-btn active" data-nav="settings" aria-label="Pengaturan">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Pengaturan</span>
      </button>
    </nav>
    <script>
       // Password visibility toggles
       document.querySelectorAll('.toggle-password').forEach(button => {
         button.addEventListener('click', function() {
           const input = this.parentElement.querySelector('input');
           const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
           input.setAttribute('type', type);
           // Toggle eye icon
           if (type === 'password') {
             this.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>`;
             this.setAttribute('aria-label', 'Tampilkan kata sandi');
           } else {
             this.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.058 10.058 0 013.083-4.082M15 12a3 3 0 11-6 0 3 3 0 016 0zm-4.5 4.5l-1.5-1.5M19.5 12c0 2.755-2.245 5-5 5s-5-2.245-5-5 2.245-5 5-5 5 2.245 5 5z" /></svg>`;
             this.setAttribute('aria-label', 'Sembunyikan kata sandi');
           }
         });
       });

       // Kekuatan kata sandi meter
       const newPassword = document.getElementById('new-password');
       const passwordStrength = document.getElementById('password-strength');
       const strengthBar = document.getElementById('strength-bar');
       const strengthLabel = document.getElementById('strength-label');
       const strengthCriteria = document.querySelectorAll('#strength-criteria span[data-criterion]');

       if (newPassword && passwordStrength) {
         newPassword.addEventListener('input', function() {
           const password = this.value;
           if (password.length === 0) {
             passwordStrength.classList.add('hidden');
             return;
           }
           passwordStrength.classList.remove('hidden');

           // Check criteria
           const criteria = {
             length: password.length >= 8,
             uppercase: /[A-Z]/.test(password),
             lowercase: /[a-z]/.test(password),
             number: /[0-9]/.test(password),
             special: /[^A-Za-z0-9]/.test(password)
           };

           // Update UI for each criterion
           strengthCriteria.forEach(span => {
             const criterion = span.getAttribute('data-criterion');
             const circle = span.parentElement.querySelector('span:first-child');
             if (criteria[criterion]) {
               circle.classList.remove('bg-neutral-700');
               circle.classList.add('bg-green-500');
             } else {
               circle.classList.remove('bg-green-500');
               circle.classList.add('bg-neutral-700');
             }
           });

           // Calculate strength score (0-5)
           const score = Object.values(criteria).filter(Boolean).length;
           let strength, color, width;

           if (score <= 2) {
             strength = 'Lemah';
             color = '#ef4444'; // red-500
             width = (score / 5) * 100;
           } else if (score === 3) {
             strength = 'Cukup';
             color = '#f97316'; // orange-500
             width = 60;
           } else if (score === 4) {
             strength = 'Baik';
             color = '#eab308'; // yellow-500
             width = 80;
           } else {
             strength = 'Kuat';
             color = '#22c55e'; // green-500
             width = 100;
           }

           strengthBar.style.width = width + '%';
           strengthBar.style.backgroundColor = color;
           strengthLabel.textContent = strength;
           strengthLabel.style.color = color;
         });
       }

       // Password confirmation validation
       const confirmPassword = document.getElementById('confirm-password');
       if (confirmPassword && newPassword) {
         confirmPassword.addEventListener('input', function() {
           if (this.value !== newPassword.value) {
             this.setCustomValidity('Passwords do not match');
           } else {
             this.setCustomValidity('');
           }
         });
       }
        // Tema management
        const themeToggle = document.getElementById('theme-toggle');
        function setTema(isLight) {
          if (isLight) {
            document.body.classList.add('light-mode');
          } else {
            document.body.classList.remove('light-mode');
          }
        }

        // Load theme from saved user settings
        const savedTema = (window.currentSettings && window.currentSettings.theme) || 'dark';
        setTema(savedTema === 'light');
        if (themeToggle) {
          themeToggle.checked = savedTema === 'light';
        }

        // Listen for theme changes
        if (themeToggle) {
          themeToggle.addEventListener('change', function() {
            setTema(this.checked);
          });
        }

       function syncFloatingNavFooterState() {
         const floatingNav = document.querySelector('.floating-nav');
         const footer = document.querySelector('footer');
         if (!floatingNav || !footer) return;

         const footerRect = footer.getBoundingClientRect();
         const threshold = floatingNav.offsetHeight + 48;
         const isNearFooter = footerRect.top <= window.innerHeight - threshold;
         floatingNav.classList.toggle('footer-near', isNearFooter);
       }

       // Floating navigation functionality
       const floatingNavButtons = document.querySelectorAll('.floating-nav-btn');
       floatingNavButtons.forEach(btn => {
         btn.addEventListener('click', function() {
           // Hapus active class from all buttons
           floatingNavButtons.forEach(b => b.classList.remove('active'));
           // Add active class to clicked button
           this.classList.add('active');

           // Navigate based on data-nav attribute
           const navType = this.dataset.nav;
           switch(navType) {
             case 'home':
               window.location.href = 'index.php';
               break;
             case 'rentals':
               window.location.href = 'rentals.php';
               break;
             case 'settings':
               window.location.href = 'profile.php';
               break;
           }
         });
       });

       syncFloatingNavFooterState();
       window.addEventListener('scroll', syncFloatingNavFooterState, { passive: true });
       window.addEventListener('resize', syncFloatingNavFooterState);
     </script>
  <script>
      window.currentUser = <?= $user_json ?>;
      window.currentSettings = <?= $settings_json ?>;
      window.csrfToken = <?= json_encode(csrf_token()) ?>;

      window.addEventListener('DOMContentLoaded', function () {
        if (window.currentUser) {
          const parts = String(window.currentUser.fullname || '').split(' ');
          const firstName = parts.shift() || '';
          const lastName = parts.join(' ');

          const setValue = function (id, value) {
            const element = document.getElementById(id);
            if (element && value !== null && value !== undefined) {
              element.value = value;
            }
          };

          setValue('first-name', firstName);
          setValue('last-name', lastName);
          setValue('email', window.currentUser.email || '');
          setValue('phone', window.currentUser.phone || '');
          setValue('address-line1', window.currentUser.address_line1 || '');
          setValue('address-line2', window.currentUser.address_line2 || '');
          setValue('city', window.currentUser.city || '');
          setValue('province', window.currentUser.province || '');
          setValue('zip-code', window.currentUser.zip_code || '');
          const countryMap = {
            Indonesia: 'ID',
            'United States': 'US',
            'United Kingdom': 'GB',
            Canada: 'CA',
            Australia: 'AU',
            Germany: 'DE',
            France: 'FR',
            Japan: 'JP',
            'South Korea': 'KR',
            Singapore: 'SG',
            Malaysia: 'MY',
            Thailand: 'TH',
            Vietnam: 'VN',
            Philippines: 'PH'
          };
          setValue('country', countryMap[window.currentUser.country || ''] || window.currentUser.country || 'ID');
        }

        const avatarFileInput = document.getElementById('profile-avatar-file');
        const avatarPreview = document.getElementById('profile-avatar-preview');
        const avatarFallback = document.getElementById('profile-avatar-fallback');
        const existingAvatarPath = document.getElementById('existing-avatar-path');
        const removeAvatarButton = document.getElementById('remove-profile-avatar');

        if (avatarFileInput && avatarPreview && avatarFallback) {
          avatarFileInput.addEventListener('change', function () {
            const [file] = this.files || [];
            if (!file) {
              return;
            }
            avatarPreview.src = URL.createObjectURL(file);
            avatarPreview.classList.remove('hidden');
            avatarFallback.style.display = 'none';
          });
        }

        if (removeAvatarButton && avatarPreview && avatarFallback && existingAvatarPath && avatarFileInput) {
          removeAvatarButton.addEventListener('click', function () {
            existingAvatarPath.value = '';
            avatarFileInput.value = '';
            avatarPreview.src = '';
            avatarPreview.classList.add('hidden');
            avatarFallback.style.display = 'block';
          });
        }

        if (window.currentSettings) {
          const setValue = function (id, value) {
            const element = document.getElementById(id);
            if (element && value !== null && value !== undefined) {
              element.value = value;
            }
          };

          setValue('language', window.currentSettings.language || 'id');
          setValue('timezone', window.currentSettings.timezone || 'Asia/Jakarta');

          const toggle = document.getElementById('theme-toggle');
          if (toggle) {
            toggle.checked = (window.currentSettings.theme || 'dark') === 'light';
          }
        }
      });

      function postSettingsPayload(payload) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../process/profile-update-process.php';
        payload.csrf_token = window.csrfToken;

        Object.keys(payload).forEach(function (key) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          input.value = payload[key];
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
      }

      function submitAppearanceSettings() {
        postSettingsPayload({
          settings_only: '1',
          language: document.getElementById('language')?.value || 'id',
          timezone: document.getElementById('timezone')?.value || 'Asia/Jakarta',
          theme: document.getElementById('theme-toggle')?.checked ? 'light' : 'dark'
        });
      }

    </script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>

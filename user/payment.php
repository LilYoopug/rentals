<?php
require_once __DIR__ . '/../includes/customer-check.php';
require_once __DIR__ . '/../data/rentals-data.php';
require_once __DIR__ . '/../includes/flash.php';

$customer_session_user = current_user();
$avatar_url = !empty($customer_session_user['avatar_path']) ? '../' . ltrim((string) $customer_session_user['avatar_path'], '/') : '';
$current_user_json = json_encode(current_user(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$rental_code = trim((string) ($_GET['rental'] ?? ''));

if ($rental_code === '') {
    set_flash('error', 'Tagihan pembayaran tidak ditemukan.');
    redirect_root_to('user/rentals.php');
}

$payment_rental = get_customer_rental_detail((int) current_user()['id'], $rental_code);
$payment_status = (string) ($payment_rental['payment']['status'] ?? '');

if (
    !$payment_rental
    || (string) ($payment_rental['status'] ?? '') !== 'disetujui'
    || $payment_status !== 'pending'
) {
    set_flash('error', 'Rental ini tidak dapat dibayar.');
    redirect_root_to('user/rentals.php');
}

$payment_rental_json = json_encode($payment_rental, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LensCraft - Pembayaran Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --accent-brass: #c7a65a;
        --accent-brass-deep: #8f6421;
        --accent-brass-soft: rgba(199, 166, 90, 0.18);
        --panel-border: rgba(255, 255, 255, 0.08);
      }
      body { font-family: "Inter", sans-serif; }
      .font-serif { font-family: "Playfair Display", serif; }
      .nav-blur { background: rgba(5, 5, 5, 0.86); backdrop-filter: blur(18px); }
      .filter-shell {
        background: linear-gradient(180deg, rgba(18, 18, 18, 0.9), rgba(11, 11, 11, 0.82));
        border: 1px solid var(--panel-border);
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.24);
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
      .control-focus:focus {
        border-color: var(--accent-brass);
        box-shadow: 0 0 0 3px var(--accent-brass-soft);
      }
      .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        border: 1px solid;
      }
      .badge-info {
        background-color: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
      }
      .method-option input:checked + div {
        border-color: var(--accent-brass);
        box-shadow: 0 0 0 3px var(--accent-brass-soft);
        background: rgba(199, 166, 90, 0.08);
      }
      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
      }
      .animate-fade-in { opacity: 0; animation: fadeInUp 0.8s ease-out forwards; }
    </style>
  </head>
  <body class="bg-neutral-950 text-neutral-100 min-h-screen">
    <nav class="fixed top-0 left-0 right-0 z-50 nav-blur border-b border-neutral-800 h-16">
      <div class="flex items-center justify-between h-full px-6">
        <div class="flex items-center gap-4">
          <a href="../index.php" class="text-2xl font-bold font-serif text-white tracking-tight">LensCraft</a>
          <span class="hidden md:inline-block text-sm text-neutral-500 border-l border-neutral-800 pl-4">Pembayaran Rental</span>
        </div>

        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 border-l border-neutral-800 pl-4">
            <div class="text-right hidden sm:block">
              <div class="text-sm font-medium text-white"><?= e((string) ($customer_session_user['fullname'] ?? 'User Name')) ?></div>
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

    <main class="pt-20 pb-12 px-6">
      <div class="max-w-7xl mx-auto animate-fade-in">
        <div class="mb-8 space-y-3">
          <a href="rentals.php" class="inline-flex items-center gap-2 text-sm text-neutral-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Rental Saya
          </a>
          <h1 class="text-3xl md:text-4xl font-serif text-white mb-2">Pembayaran Rental</h1>
          <p class="text-neutral-400">Selesaikan pembayaran untuk mengaktifkan rental Anda.</p>
        </div>

        <div id="payment-success" class="hidden flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 filter-shell rounded-[1.6rem] p-4">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-full bg-green-900/30 border border-green-800/50 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div>
              <h2 class="text-lg font-semibold text-white mb-1">Pembayaran berhasil</h2>
              <p class="text-neutral-400 text-sm">Halaman ini akan mengarahkan Anda kembali ke Rental Saya dalam beberapa detik.</p>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <section class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
            <div class="p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
              Ringkasan Rental
            </div>
            <div class="p-4 md:p-6">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                <div class="w-full lg:w-28 flex-shrink-0">
                  <div class="rounded-2xl overflow-hidden bg-neutral-800 border border-neutral-700">
                    <img src="<?= e((string) ($payment_rental['product']['image'] ?? '../images/gear-placeholder.svg')) ?>" alt="<?= e((string) ($payment_rental['product']['name'] ?? 'Rental')) ?>" class="w-full h-full object-cover" onerror="this.src='../images/gear-placeholder.svg'">
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                      <h2 class="text-lg font-semibold text-white"><?= e((string) ($payment_rental['product']['name'] ?? 'Rental')) ?></h2>
                      <p class="text-sm text-neutral-400 mt-1"><?= e((string) ($payment_rental['product']['brand'] ?? '')) ?> • <?= e(ucfirst((string) ($payment_rental['product']['category'] ?? ''))) ?></p>
                      <p class="text-xs text-neutral-500 mt-1"><?= e((string) $payment_rental['id']) ?></p>
                    </div>
                    <span class="badge badge-info self-start">Menunggu Pembayaran</span>
                  </div>

                  <div class="grid gap-4 md:grid-cols-2 mt-4 text-sm">
                    <div>
                      <div class="text-neutral-500">Periode</div>
                      <div class="text-neutral-100 mt-1"><?= e((string) $payment_rental['startDate']) ?> sampai <?= e((string) $payment_rental['endDate']) ?></div>
                    </div>
                    <div>
                      <div class="text-neutral-500">Durasi</div>
                      <div class="text-neutral-100 mt-1"><?= e((string) $payment_rental['totalDays']) ?> hari</div>
                    </div>
                    <div>
                      <div class="text-neutral-500">Metode Pengiriman</div>
                      <div class="text-neutral-100 mt-1"><?= e((string) $payment_rental['deliveryMethod']) ?></div>
                    </div>
                    <div>
                      <div class="text-neutral-500">Harga per Hari</div>
                      <div class="text-neutral-100 mt-1"><?= e(format_currency((float) ($payment_rental['dailyRate'] ?? 0))) ?></div>
                    </div>
                    <div>
                      <div class="text-neutral-500">Biaya Pengiriman</div>
                      <div class="text-neutral-100 mt-1"><?= e(format_currency((float) ($payment_rental['deliveryFee'] ?? 0))) ?></div>
                    </div>
                    <div>
                      <div class="text-neutral-500">Total Pembayaran</div>
                      <div class="text-neutral-100 font-semibold mt-1"><?= e(format_currency((float) ($payment_rental['total'] ?? 0))) ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
            <section class="space-y-6">
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
                <div class="p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
                  Metode Pembayaran
                </div>

                <form id="payment-form" class="p-4 md:p-6 space-y-6">
                  <input type="hidden" name="rental_code" value="<?= e((string) $payment_rental['id']) ?>">

                  <div class="grid gap-3 md:grid-cols-3">
                    <label class="method-option block cursor-pointer">
                      <input id="payment-method-transfer-bank" type="radio" name="method" value="transfer_bank" class="sr-only" checked>
                      <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition-all h-full">
                        <div class="text-sm font-semibold text-white">Transfer Bank</div>
                        <div class="text-xs text-neutral-500 mt-1">Virtual account simulasi</div>
                      </div>
                    </label>

                    <label class="method-option block cursor-pointer">
                      <input id="payment-method-qris" type="radio" name="method" value="qris" class="sr-only">
                      <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition-all h-full">
                        <div class="text-sm font-semibold text-white">QRIS</div>
                        <div class="text-xs text-neutral-500 mt-1">Scan QR simulasi</div>
                      </div>
                    </label>

                    <label class="method-option block cursor-pointer">
                      <input id="payment-method-kartu-kredit" type="radio" name="method" value="kartu_kredit" class="sr-only">
                      <div class="rounded-xl border border-neutral-700 bg-neutral-800 p-4 transition-all h-full">
                        <div class="text-sm font-semibold text-white">Kartu Kredit</div>
                        <div class="text-xs text-neutral-500 mt-1">Input kartu simulasi</div>
                      </div>
                    </label>
                  </div>

                  <div id="payment-panel-transfer-bank" class="bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 space-y-4">
                    <div>
                      <h3 class="text-base font-semibold text-white">Transfer Bank</h3>
                      <p class="text-sm text-neutral-400 mt-1">Gunakan virtual account simulasi berikut.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                      <div>
                        <label for="transfer-bank-name" class="block text-sm text-neutral-300 mb-2">Bank Tujuan</label>
                        <select id="transfer-bank-name" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 focus:outline-none control-focus">
                          <option>BCA Virtual Account</option>
                          <option>Mandiri Virtual Account</option>
                          <option>BNI Virtual Account</option>
                        </select>
                      </div>
                      <div>
                        <label for="transfer-account-number" class="block text-sm text-neutral-300 mb-2">Nomor Virtual Account</label>
                        <input id="transfer-account-number" type="text" value="<?= e('8808' . preg_replace('/\D+/', '', (string) $payment_rental['id'])) ?>" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 focus:outline-none control-focus" readonly>
                      </div>
                    </div>
                    <div class="bg-neutral-900 border border-neutral-700 rounded-xl p-4">
                      <div class="text-xs text-neutral-500 mb-2">Catatan Pembayaran</div>
                      <p class="text-sm text-neutral-200">Salin nomor virtual account di atas lalu lanjutkan. Sistem akan langsung menerima simulasi pembayaran setelah Anda menekan tombol bayar.</p>
                    </div>
                  </div>

                  <div id="payment-panel-qris" class="hidden bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 space-y-5">
                    <div>
                      <h3 class="text-base font-semibold text-white">QRIS</h3>
                      <p class="text-sm text-neutral-400 mt-1">Scan kode QR simulasi berikut untuk melanjutkan pembayaran.</p>
                    </div>
                    <div class="grid gap-5 md:grid-cols-[220px_minmax(0,1fr)] md:items-center">
                      <div class="rounded-2xl border border-neutral-700 bg-white p-4">
                        <img
                          src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQHhF-CpgeTS_MLS4zibhWbnO1K28aqI175euutV238WQ&s=10"
                          alt="Kode QR pembayaran QRIS"
                          class="w-full h-auto object-contain"
                          referrerpolicy="no-referrer"
                          onerror="this.src='../images/gear-placeholder.svg'"
                        >
                      </div>
                      <div class="space-y-4">
                        <p class="text-sm text-neutral-300">Scan kode QR ini dengan aplikasi pembayaran apa pun yang mendukung QRIS. Setelah itu tekan tombol bayar untuk menyelesaikan simulasi checkout.</p>
                        <div class="bg-neutral-900 border border-neutral-700 rounded-xl p-4">
                          <div class="text-xs text-neutral-500 mb-2">QRIS Merchant</div>
                          <div class="text-sm font-semibold text-white">LensCraft Rental Checkout</div>
                          <div class="text-xs text-neutral-500 mt-1">Nominal <?= e(format_currency((float) ($payment_rental['total'] ?? 0))) ?></div>
                        </div>
                      </div>
                    </div>
                    <div class="bg-neutral-900 border border-neutral-700 rounded-xl p-4">
                      <div class="text-xs text-neutral-500 mb-2">Instruksi</div>
                      <p class="text-sm text-neutral-200">Tidak ada data tambahan yang diperlukan untuk simulasi QRIS. Pilih metode ini lalu tekan tombol bayar.</p>
                    </div>
                  </div>

                  <div id="payment-panel-kartu-kredit" class="hidden bg-neutral-800/50 border border-neutral-700 rounded-xl p-4 space-y-4">
                    <div>
                      <h3 class="text-base font-semibold text-white">Kartu Kredit</h3>
                      <p class="text-sm text-neutral-400 mt-1">Isi data kartu simulasi untuk menyelesaikan pembayaran.</p>
                    </div>
                    <div>
                      <label for="credit-card-number" class="block text-sm text-neutral-300 mb-2">Nomor Kartu</label>
                      <input id="credit-card-number" data-payment-field data-required-field type="text" inputmode="numeric" name="card_number" maxlength="19" autocomplete="cc-number" placeholder="4111 1111 1111 1111" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 focus:outline-none control-focus">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                      <div>
                        <label for="credit-card-expiry" class="block text-sm text-neutral-300 mb-2">Masa Berlaku</label>
                        <input id="credit-card-expiry" data-payment-field data-required-field type="text" inputmode="numeric" name="card_expiry" maxlength="5" autocomplete="cc-exp" placeholder="12/30" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 focus:outline-none control-focus">
                      </div>
                      <div>
                        <label for="credit-card-cvv" class="block text-sm text-neutral-300 mb-2">CVV</label>
                        <input id="credit-card-cvv" data-payment-field data-required-field type="text" inputmode="numeric" name="card_cvv" maxlength="3" autocomplete="cc-csc" placeholder="123" class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-xl text-neutral-100 placeholder-neutral-500 focus:outline-none control-focus">
                      </div>
                    </div>
                    <div class="bg-neutral-900 border border-neutral-700 rounded-xl p-4">
                      <div class="text-xs text-neutral-500 mb-2">Catatan Kartu</div>
                      <p class="text-sm text-neutral-200">Karena ini checkout simulasi, data kartu tidak diproses ke gateway mana pun. Field ini hanya dipakai untuk validasi alur.</p>
                    </div>
                  </div>
                </form>
              </div>
            </section>

            <aside class="space-y-6">
              <div class="bg-neutral-900 border border-neutral-800 rounded-2xl overflow-hidden">
                <div class="p-4 bg-neutral-800/50 border-b border-neutral-800 text-xs font-medium text-neutral-400 uppercase tracking-wider">
                  Konfirmasi Pembayaran
                </div>
                <div class="p-4 md:p-6 space-y-3 text-sm">
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-neutral-500">Order</span>
                    <span class="text-neutral-100"><?= e((string) $payment_rental['id']) ?></span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-neutral-500">Produk</span>
                    <span class="text-neutral-100 text-right"><?= e((string) ($payment_rental['product']['name'] ?? 'Rental')) ?></span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-neutral-500">Biaya Rental</span>
                    <span class="text-neutral-100"><?= e(format_currency((float) ($payment_rental['dailyRate'] ?? 0) * (float) ($payment_rental['totalDays'] ?? 0))) ?></span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <span class="text-neutral-500">Biaya Pengiriman</span>
                    <span class="text-neutral-100"><?= e(format_currency((float) ($payment_rental['deliveryFee'] ?? 0))) ?></span>
                  </div>
                  <div class="pt-5 mt-5 border-t border-neutral-800">
                    <div class="flex items-center justify-between gap-4">
                      <span class="text-base font-medium text-white">Total Pembayaran</span>
                      <span class="text-2xl font-semibold text-white"><?= e(format_currency((float) ($payment_rental['total'] ?? 0))) ?></span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-3">Semua input pembayaran bersifat simulasi. Sistem akan langsung menerima input yang valid sesuai metode yang dipilih.</p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 filter-shell rounded-[1.6rem] p-4">
                <button id="submit-payment" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-white text-black hover:bg-neutral-200 rounded-2xl text-sm font-semibold transition-colors button-primary">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a1 1 0 011 1v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a1 1 0 011-1z" />
                  </svg>
                  Bayar Sekarang
                </button>
              </div>
            </aside>
          </div>
        </div>
      </div>
    </main>
    <footer class="border-t border-neutral-800 py-12 bg-neutral-900/50">
      <div class="max-w-7xl mx-auto px-6 text-center text-sm text-neutral-500">
        <p>© 2026 LensCraft. Sistem Rental Kamera.</p>
        <p class="mt-1">Butuh bantuan? Hubungi tim dukungan kami.</p>
      </div>
    </footer>

    <script>
      const paymentRental = <?= $payment_rental_json ?>;
      const paymentForm = document.getElementById('payment-form');
      const submitButton = document.getElementById('submit-payment');
      const successPanel = document.getElementById('payment-success');
      const paymentMethodInputs = Array.from(document.querySelectorAll('input[name="method"]'));
      const creditCardNumberInput = document.getElementById('credit-card-number');
      const creditCardExpiryInput = document.getElementById('credit-card-expiry');
      const creditCardCvvInput = document.getElementById('credit-card-cvv');
      const paymentMethodPanels = {
        transfer_bank: document.getElementById('payment-panel-transfer-bank'),
        qris: document.getElementById('payment-panel-qris'),
        kartu_kredit: document.getElementById('payment-panel-kartu-kredit')
      };

      function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
      }

      function formatCardNumberValue(value) {
        const digits = digitsOnly(value).slice(0, 16);
        return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
      }

      function formatCardExpiryValue(value) {
        const digits = digitsOnly(value).slice(0, 4);
        if (digits.length <= 2) {
          return digits;
        }
        return `${digits.slice(0, 2)}/${digits.slice(2)}`;
      }

      creditCardNumberInput?.addEventListener('input', function () {
        this.value = formatCardNumberValue(this.value);
      });

      creditCardExpiryInput?.addEventListener('input', function () {
        this.value = formatCardExpiryValue(this.value);
      });

      creditCardCvvInput?.addEventListener('input', function () {
        this.value = digitsOnly(this.value).slice(0, 3);
      });

      function togglePaymentMethodPanels(selectedMethod) {
        Object.entries(paymentMethodPanels).forEach(function ([method, panel]) {
          if (!panel) {
            return;
          }

          const isActive = method === selectedMethod;
          panel.classList.toggle('hidden', !isActive);

          panel.querySelectorAll('[data-payment-field]').forEach(function (field) {
            if (field.hasAttribute('name')) {
              field.disabled = !isActive;
            }

            if (field.hasAttribute('data-required-field')) {
              field.required = isActive;
            }
          });
        });
      }

      paymentMethodInputs.forEach(function (input) {
        input.addEventListener('change', function () {
          togglePaymentMethodPanels(this.value);
        });
      });

      togglePaymentMethodPanels(paymentMethodInputs.find((input) => input.checked)?.value || 'transfer_bank');

      submitButton?.addEventListener('click', async function () {
        if (!paymentForm.reportValidity()) {
          return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Memproses Pembayaran...';

        const payload = new URLSearchParams(new FormData(paymentForm));

        try {
          const response = await fetch('../process/rental-payment-process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: payload.toString()
          });

          const result = await response.json();
          if (!result.success) {
            throw new Error(result.message || 'Pembayaran gagal diproses.');
          }

          successPanel.classList.remove('hidden');
          paymentForm.classList.add('pointer-events-none', 'opacity-60');
          submitButton.classList.add('hidden');
          window.showToast('Pembayaran berhasil. Rental Anda sudah aktif.');

          setTimeout(function () {
            window.location.href = 'rentals.php';
          }, 1800);
        } catch (error) {
          submitButton.disabled = false;
          submitButton.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a1 1 0 011 1v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8a1 1 0 011-1z" />
            </svg>
            Bayar Sekarang
          `;
          window.showToast(error.message || 'Pembayaran gagal diproses.');
        }
      });
    </script>
    <script>window.currentUser = <?= $current_user_json ?>;</script>
    <?= page_runtime_bundle($flash_script) ?>
  </body>
</html>

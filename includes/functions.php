<?php

require_once __DIR__ . '/../config/base_url.php';
require_once __DIR__ . '/../config/koneksi.php';

function app_db()
{
    global $koneksi;

    return $koneksi;
}

function db_ready()
{
    return app_db() instanceof mysqli;
}

function db_error_message()
{
    global $db_error;

    return $db_error;
}

function ensure_db()
{
    if (!db_ready()) {
        throw new RuntimeException('Koneksi database gagal. ' . db_error_message());
    }
}

function db_types_from_params($params)
{
    $types = '';

    foreach ($params as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }

    return $types;
}

function db_prepare_and_execute($sql, $params = [])
{
    ensure_db();

    try {
        $statement = mysqli_prepare(app_db(), $sql);
        if (!$statement) {
            return false;
        }

        if (!empty($params)) {
            $types = db_types_from_params($params);
            $refs = [$statement, $types];
            foreach ($params as $key => $value) {
                $params[$key] = $value;
                $refs[] = &$params[$key];
            }
            call_user_func_array('mysqli_stmt_bind_param', $refs);
        }

        if (!mysqli_stmt_execute($statement)) {
            mysqli_stmt_close($statement);
            return false;
        }

        return $statement;
    } catch (mysqli_sql_exception $exception) {
        if (isset($statement) && $statement instanceof mysqli_stmt) {
            mysqli_stmt_close($statement);
        }
        return false;
    }
}

function db_all($sql, $params = [])
{
    $statement = db_prepare_and_execute($sql, $params);
    if ($statement === false) {
        return [];
    }

    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
        mysqli_stmt_close($statement);
        return [];
    }

    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    mysqli_stmt_close($statement);

    return $rows;
}

function db_one($sql, $params = [])
{
    $rows = db_all($sql, $params);

    return empty($rows) ? null : $rows[0];
}

function db_execute($sql, $params = [])
{
    $statement = db_prepare_and_execute($sql, $params);
    if ($statement === false) {
        return false;
    }

    mysqli_stmt_close($statement);

    return true;
}

function db_insert_id()
{
    return db_ready() ? mysqli_insert_id(app_db()) : 0;
}

function db_execute_count($sql, $params = [])
{
    $statement = db_prepare_and_execute($sql, $params);
    if ($statement === false) {
        return false;
    }

    $count = mysqli_stmt_affected_rows($statement);
    mysqli_stmt_close($statement);

    return $count;
}

function db_column_type($table_name, $column_name)
{
    static $cache = [];

    $cache_key = $table_name . '.' . $column_name;
    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    if (!db_ready()) {
        $cache[$cache_key] = '';
        return $cache[$cache_key];
    }

    $row = db_one(
        'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
        [$table_name, $column_name]
    );

    $cache[$cache_key] = strtolower((string) ($row['COLUMN_TYPE'] ?? ''));

    return $cache[$cache_key];
}

function db_begin_transaction()
{
    ensure_db();

    return mysqli_begin_transaction(app_db());
}

function db_commit_transaction()
{
    ensure_db();

    return mysqli_commit(app_db());
}

function db_rollback_transaction()
{
    ensure_db();

    return mysqli_rollback(app_db());
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url_path($path = '')
{
    global $base_url;

    $prefix = rtrim((string) $base_url, '/');
    $suffix = ltrim((string) $path, '/');

    if ($prefix === '' && $suffix === '') {
        return '/';
    }

    if ($prefix === '') {
        return '/' . $suffix;
    }

    if ($suffix === '') {
        return $prefix;
    }

    return $prefix . '/' . $suffix;
}

function root_base_url_path($path = '')
{
    global $base_url;

    $root_prefix = rtrim((string) $base_url, '/');
    $root_prefix = preg_replace('#/(admin|staff|user)$#', '', $root_prefix) ?? $root_prefix;
    $suffix = ltrim((string) $path, '/');

    if ($root_prefix === '' && $suffix === '') {
        return '/';
    }

    if ($root_prefix === '') {
        return '/' . $suffix;
    }

    if ($suffix === '') {
        return $root_prefix;
    }

    return $root_prefix . '/' . $suffix;
}

function public_media_path($path, $fallback = 'images/gear-placeholder.svg')
{
    $image_path = trim((string) $path);
    if ($image_path === '') {
        $image_path = trim((string) $fallback);
    }

    if ($image_path === '') {
        return '';
    }

    if (preg_match('/^(?:https?:)?\/\//', $image_path) === 1 || strpos($image_path, '/') === 0) {
        return $image_path;
    }

    return root_base_url_path(ltrim($image_path, '/'));
}

function redirect_to($path)
{
    header('Location: ' . base_url_path($path));
    exit;
}

function redirect_root_to($path)
{
    header('Location: ' . root_base_url_path($path));
    exit;
}

function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function flash_alert_script()
{
    $flash = pull_flash();
    if (!$flash) {
        return '';
    }

    return '<script>window.addEventListener("DOMContentLoaded",function(){alert(' . json_encode((string) $flash['message']) . ');});</script>';
}

function motion_runtime_script()
{
    return <<<'HTML'
<script>
(function () {
  if (window.__lenscraftMotionReady) return;
  window.__lenscraftMotionReady = true;

  const motionMedia = window.matchMedia('(prefers-reduced-motion: reduce)');
  const EASE_OUT = 'cubic-bezier(0.22, 1, 0.36, 1)';
  const selectorList = [
    'body > nav',
    'body > section',
    'body > footer',
    'main > *',
    '#content-area > section',
    '#content-area > section > *',
    '#product-detail-container > *',
    '.auth-panel',
    '.helper-panel',
    '.filter-shell',
    '.card-hover',
    '.floating-nav',
    '#related-grid > *'
  ];

  const style = document.createElement('style');
  style.setAttribute('data-lenscraft-motion-runtime', 'true');
  style.textContent = `
    :root {
      --lenscraft-motion-ease-out: ${EASE_OUT};
      --lenscraft-motion-ease-soft: cubic-bezier(0.25, 1, 0.5, 1);
      --lenscraft-motion-fast: 180ms;
      --lenscraft-motion-base: 280ms;
    }
    html {
      scroll-behavior: smooth;
    }
    body.lenscraft-motion-ready [data-lenscraft-motion] {
      backface-visibility: hidden;
      transform-origin: center top;
    }
    body.lenscraft-motion-ready .card-hover,
    body.lenscraft-motion-ready .auth-panel,
    body.lenscraft-motion-ready .helper-panel,
    body.lenscraft-motion-ready .filter-shell,
    body.lenscraft-motion-ready .floating-nav,
    body.lenscraft-motion-ready .nav-item,
    body.lenscraft-motion-ready .tab-btn,
    body.lenscraft-motion-ready .modal-panel,
    body.lenscraft-motion-ready [id$="-modal-content"] {
      transition:
        transform var(--lenscraft-motion-base) var(--lenscraft-motion-ease-soft),
        box-shadow var(--lenscraft-motion-base) var(--lenscraft-motion-ease-soft),
        background-color var(--lenscraft-motion-fast) ease,
        border-color var(--lenscraft-motion-fast) ease,
        color var(--lenscraft-motion-fast) ease,
        opacity var(--lenscraft-motion-fast) ease;
    }
    body.lenscraft-motion-ready button,
    body.lenscraft-motion-ready input,
    body.lenscraft-motion-ready select,
    body.lenscraft-motion-ready textarea {
      transition:
        transform var(--lenscraft-motion-fast) var(--lenscraft-motion-ease-soft),
        box-shadow var(--lenscraft-motion-base) var(--lenscraft-motion-ease-soft),
        background-color var(--lenscraft-motion-fast) ease,
        border-color var(--lenscraft-motion-fast) ease,
        color var(--lenscraft-motion-fast) ease;
    }
    body.lenscraft-motion-ready .card-hover:hover,
    body.lenscraft-motion-ready .auth-panel:hover,
    body.lenscraft-motion-ready .helper-panel:hover,
    body.lenscraft-motion-ready .filter-shell:hover {
      transform: translate3d(0, -4px, 0);
      box-shadow: 0 24px 52px rgba(0, 0, 0, 0.22);
    }
    body.lenscraft-motion-ready .nav-item:hover,
    body.lenscraft-motion-ready .tab-btn:hover,
    body.lenscraft-motion-ready button:hover {
      transform: translate3d(0, -1px, 0);
    }
    body.lenscraft-motion-ready .nav-item:active,
    body.lenscraft-motion-ready .tab-btn:active,
    body.lenscraft-motion-ready button:active {
      transform: translate3d(0, 0, 0) scale(0.985);
    }
    body.lenscraft-motion-ready .floating-nav {
      box-shadow: 0 22px 48px rgba(0, 0, 0, 0.28);
    }
    @media (prefers-reduced-motion: reduce) {
      html {
        scroll-behavior: auto;
      }
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
      }
      [data-lenscraft-motion] {
        opacity: 1 !important;
        transform: none !important;
      }
    }
  `;
  document.head.appendChild(style);

  if (motionMedia.matches) {
    return;
  }
  // Timestamp marking when the motion runtime was initialized. Used to
  // suppress reveal animations for elements that are already visible on
  // first paint (prevents a perceived "double-refresh").
  const LENSCRAFT_PRIME_TS = Date.now();

  const revealedElements = new WeakSet();
  const modalAnimationTimestamps = new WeakMap();

  function isPaginationTarget(element) {
    if (!(element instanceof HTMLElement)) return false;
    if (element.matches('[id$="-pagination"], [id$="-page-numbers"], #pagination-container, #page-numbers, [id$="-prev"], [id$="-next"]')) {
      return true;
    }
    if (element.closest('[id$="-pagination"], [id$="-page-numbers"], #pagination-container, #page-numbers')) {
      return true;
    }
    return Boolean(
      element.querySelector('[id$="-page-numbers"], #page-numbers') ||
      (element.querySelector('[id$="-prev"]') && element.querySelector('[id$="-next"]'))
    );
  }

  function isElementVisible(element) {
    if (!(element instanceof HTMLElement)) return false;
    if (element.classList.contains('hidden')) return false;
    if (element.closest('.hidden')) return false;
    const computedStyle = window.getComputedStyle(element);
    if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden') {
      return false;
    }
    if (Number.parseFloat(computedStyle.opacity) === 0 && !element.matches('.modal-panel, [id$="-modal-content"]')) {
      return false;
    }
    return true;
  }

  function countVisibleSiblings(element) {
    let delay = 0;
    let sibling = element.previousElementSibling;
    while (sibling && delay < 240) {
      if (sibling instanceof HTMLElement && isElementVisible(sibling)) {
        delay += 48;
      }
      sibling = sibling.previousElementSibling;
    }
    return delay;
  }

  function buildAnimationSpec(element) {
    if (element.matches('.modal-panel, [id$="-modal-content"]')) {
      return { duration: 340, distance: 20, scale: 0.98 };
    }
    if (element.matches('body > nav, .floating-nav')) {
      return { duration: 420, distance: 6, scale: 0.998 };
    }

    // Default: smaller, faster reveals to make the effect feel subtle and
    // avoid giving the impression of page refresh when data is inserted.
    return { duration: 420, distance: 8, scale: 0.995 };
  }

  function playReveal(element, options) {
    if (!(element instanceof HTMLElement)) return;
    const animationSpec = options || buildAnimationSpec(element);
    element.dataset.lenscraftMotion = 'reveal';

    if (typeof element.animate !== 'function') {
      return;
    }

    // Preserve horizontal centering for elements that rely on translateX(-50%)
    const preserveCenterX = element.matches && element.matches('.floating-nav');
    const baseX = preserveCenterX ? '-50%' : '0';

    const animation = element.animate(
      [
        {
          opacity: 0.01,
          transform: `translate3d(${baseX}, ${animationSpec.distance}px, 0) scale(${animationSpec.scale})`,
          filter: 'saturate(0.92)'
        },
        {
          opacity: 1,
          transform: `translate3d(${baseX}, 0, 0) scale(1)`,
          filter: 'saturate(1)'
        }
      ],
      {
        duration: animationSpec.duration,
        delay: animationSpec.delay || 0,
        easing: EASE_OUT,
        fill: 'both'
      }
    );

    animation.addEventListener('finish', function () {
      animation.cancel();
    });
  }

  function registerRevealTarget(element) {
    if (!(element instanceof HTMLElement)) return;
    if (revealedElements.has(element)) return;
    if (element.classList.contains('animate-fade-in')) return;
    if (element.matches('.modal-panel, [id$="-modal-content"]')) return;
    if (isPaginationTarget(element)) return;
    if (!isElementVisible(element)) return;
    if (element.closest('[data-lenscraft-motion="skip"]')) return;

    // If this element is already visible during the very early prime phase
    // of the runtime (i.e. just after the script loaded), treat it as
    // already revealed so it won't animate and cause a perceived repaint.
    try {
      const rect = element.getBoundingClientRect();
      const now = Date.now();
      const isInViewport = rect.top < window.innerHeight && rect.bottom > 0;
      if (isInViewport && (now - LENSCRAFT_PRIME_TS) < 600) {
        revealedElements.add(element);
        element.dataset.lenscraftMotion = 'revealed';
        return;
      }
    } catch (e) {
      // ignore measurement errors and fall through to normal reveal
    }

    revealedElements.add(element);
    element.dataset.lenscraftMotion = 'queued';
    revealObserver.observe(element);
  }

  const revealObserver = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        revealObserver.unobserve(entry.target);
        playReveal(entry.target, Object.assign(buildAnimationSpec(entry.target), {
          delay: countVisibleSiblings(entry.target)
        }));
      });
    },
    {
      threshold: 0.14,
      rootMargin: '0px 0px -10% 0px'
    }
  );

  function collectTargets(root) {
    const searchRoot = root instanceof Element || root instanceof Document ? root : document;
    selectorList.forEach(function (selector) {
      if (searchRoot instanceof Element && searchRoot.matches(selector)) {
        registerRevealTarget(searchRoot);
      }

      if (typeof searchRoot.querySelectorAll === 'function') {
        searchRoot.querySelectorAll(selector).forEach(registerRevealTarget);
      }
    });
  }

  function maybeAnimateModal(target) {
    if (!(target instanceof HTMLElement)) return;

    const modalPanel = target.matches('.modal-panel')
      ? target
      : target.querySelector('.modal-panel');

    if (!modalPanel || !isElementVisible(modalPanel)) return;

    const modalBackdrop = modalPanel.closest('.modal-overlay');
    if (!modalBackdrop || !isElementVisible(modalBackdrop)) return;

    const now = Date.now();
    const lastAnimated = modalAnimationTimestamps.get(modalPanel) || 0;
    if (now - lastAnimated < 140) return;

    modalAnimationTimestamps.set(modalPanel, now);
    playReveal(modalPanel, {
      duration: 320,
      distance: 20,
      scale: 0.98,
      delay: 0
    });

    if (modalBackdrop && typeof modalBackdrop.animate === 'function' && isElementVisible(modalBackdrop)) {
      const backdropAnimation = modalBackdrop.animate(
        [
          { opacity: 0.01 },
          { opacity: 1 }
        ],
        {
          duration: 200,
          easing: EASE_OUT,
          fill: 'both'
        }
      );

      backdropAnimation.addEventListener('finish', function () {
        backdropAnimation.cancel();
      });
    }
  }

  function primeMotionSystem() {
    // Defer adding the runtime-ready class to the next paint frame so
    // initial styles render without the runtime transitions. This
    // prevents a visible 'second refresh' effect when the class
    // toggles transition rules after first paint.
    requestAnimationFrame(function () {
      try {
        document.body.classList.add('lenscraft-motion-ready');
      } catch (e) {
        // ignore if body isn't available for some reason
      }

      collectTargets(document);
      requestAnimationFrame(function () {
        collectTargets(document);
      });
    });
  }

  const mutationObserver = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.type === 'attributes' && mutation.target instanceof HTMLElement) {
        collectTargets(mutation.target);
        maybeAnimateModal(mutation.target);
      }

      mutation.addedNodes.forEach(function (node) {
        if (!(node instanceof HTMLElement)) return;
        collectTargets(node);
        maybeAnimateModal(node);
      });
    });
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', primeMotionSystem, { once: true });
  } else {
    primeMotionSystem();
  }

  mutationObserver.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['class', 'style']
  });
})();
</script>
HTML;
}

function toast_runtime_script()
{
    return <<<'HTML'
<script>
(function () {
  if (window.__lenscraftToastReady) return;
  window.__lenscraftToastReady = true;

  const style = document.createElement('style');
  style.textContent = `
    .lenscraft-toast-stack {
      position: fixed;
      right: 1rem;
      bottom: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      z-index: 9999;
      width: min(24rem, calc(100vw - 2rem));
      pointer-events: none;
    }
    .lenscraft-toast {
      pointer-events: auto;
      background: rgba(17, 17, 17, 0.94);
      color: #f5f5f5;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-left: 4px solid #c7a65a;
      border-radius: 1rem;
      padding: 0.95rem 1rem;
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.34);
      transform: translateY(16px);
      opacity: 0;
      transition: transform 0.22s ease, opacity 0.22s ease;
      font: 500 0.92rem/1.45 "Inter", sans-serif;
    }
    .lenscraft-toast.is-visible {
      transform: translateY(0);
      opacity: 1;
    }
  `;
  document.head.appendChild(style);

  const stack = document.createElement('div');
  stack.className = 'lenscraft-toast-stack';

  function ensureStack() {
    if (!document.body.contains(stack)) {
      document.body.appendChild(stack);
    }
  }

  function hideToast(toast) {
    if (!toast) return;
    toast.classList.remove('is-visible');
    setTimeout(function () {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 220);
  }

  window.showToast = function (message) {
    ensureStack();
    const toast = document.createElement('div');
    toast.className = 'lenscraft-toast';
    toast.textContent = String(message || '');
    stack.appendChild(toast);
    requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });
    setTimeout(function () {
      hideToast(toast);
    }, 4000);
  };

  window.nativeAlert = window.alert.bind(window);
  window.alert = function (message) {
    window.showToast(message);
  };
  window.formatCurrencyIDR = function (amount) {
    const value = Number(amount || 0);
    return 'Rp' + value.toLocaleString('id-ID', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };
})();
</script>
HTML;
}

function page_runtime_bundle($flash_script = '')
{
    return motion_runtime_script() . toast_runtime_script() . $flash_script;
}

function current_user()
{
    return $_SESSION['current_user'] ?? null;
}

function is_logged_in()
{
    return current_user() !== null;
}

function is_admin_user()
{
    return normalize_role_value(current_user()['role'] ?? '') === 'admin';
}

function is_staff_user()
{
    $role = normalize_role_value(current_user()['role'] ?? '');

    return $role === 'petugas' || $role === 'admin';
}

function is_customer_user()
{
    return normalize_role_value(current_user()['role'] ?? '') === 'pelanggan';
}

function redirect_logged_in_user_home()
{
    $role = normalize_role_value(current_user()['role'] ?? 'pelanggan');

    if ($role === 'admin') {
        redirect_to('admin/index.php');
    }

    if ($role === 'petugas') {
        redirect_to('staff/index.php');
    }

    redirect_to('products.php');
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_input()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_request()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }

    $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $session_token = (string) ($_SESSION['csrf_token'] ?? '');

    return $token !== '' && $session_token !== '' && hash_equals($session_token, $token);
}

function format_currency($amount)
{
    return 'Rp' . number_format((float) $amount, 2, ',', '.');
}

function normalize_role_value($role)
{
    $role = trim((string) $role);

    $map = [
        'admin' => 'admin',
        'staff' => 'petugas',
        'petugas' => 'petugas',
        'user' => 'pelanggan',
        'pelanggan' => 'pelanggan',
        'customer' => 'pelanggan',
    ];

    return $map[$role] ?? 'pelanggan';
}

function normalize_user_status_value($status)
{
    $status = trim((string) $status);

    $map = [
        'active' => 'aktif',
        'aktif' => 'aktif',
        'inactive' => 'nonaktif',
        'nonaktif' => 'nonaktif',
        'pending' => 'menunggu',
        'menunggu' => 'menunggu',
    ];

    return $map[$status] ?? 'menunggu';
}

function normalize_product_status_value($status)
{
    return normalize_user_status_value($status);
}

function normalize_rental_status_value($status)
{
    $status = trim((string) $status);

    $map = [
        'pending' => 'menunggu',
        'menunggu' => 'menunggu',
        'upcoming' => 'mendatang',
        'mendatang' => 'mendatang',
        'approved' => 'disetujui',
        'disetujui' => 'disetujui',
        'active' => 'aktif',
        'aktif' => 'aktif',
        'completed' => 'selesai',
        'selesai' => 'selesai',
        'cancelled' => 'dibatalkan',
        'dibatalkan' => 'dibatalkan',
        'rejected' => 'ditolak',
        'ditolak' => 'ditolak',
    ];

    return $map[$status] ?? 'menunggu';
}

function normalize_return_status_value($status)
{
    $status = trim((string) $status);

    $map = [
        'pending' => 'menunggu',
        'menunggu' => 'menunggu',
        'completed' => 'selesai',
        'selesai' => 'selesai',
    ];

    return $map[$status] ?? 'menunggu';
}

function storage_return_status_value($status)
{
    $normalized = normalize_return_status_value($status);
    $column_type = db_column_type('returns', 'status');

    if (strpos($column_type, "'pending'") !== false || strpos($column_type, "'completed'") !== false) {
        $map = [
            'menunggu' => 'pending',
            'selesai' => 'completed',
        ];

        return $map[$normalized] ?? 'pending';
    }

    $map = [
        'menunggu' => 'menunggu',
        'selesai' => 'selesai',
    ];

    return $map[$normalized] ?? 'menunggu';
}

function present_borrowing_workflow_status($status)
{
    $normalized = normalize_rental_status_value($status);
    $allowed = ['menunggu', 'mendatang', 'disetujui', 'aktif', 'selesai', 'dibatalkan', 'ditolak'];

    return in_array($normalized, $allowed, true) ? $normalized : 'menunggu';
}

function present_return_workflow_status($status, $allow_borrowed = true)
{
    $raw = trim((string) $status);

    if ($allow_borrowed && in_array($raw, ['borrowed', 'dipinjam'], true)) {
        return 'borrowed';
    }

    if ($raw === 'overdue') {
        return 'overdue';
    }

    $normalized = normalize_return_status_value($raw);
    $map = [
        'selesai' => 'returned',
        'menunggu' => 'menunggu',
    ];

    return $map[$normalized] ?? 'menunggu';
}

function normalize_delivery_method_value($method)
{
    $method = trim((string) $method);

    $map = [
        'pickup' => 'ambil_sendiri',
        'ambil_sendiri' => 'ambil_sendiri',
        'delivery' => 'diantar',
        'diantar' => 'diantar',
    ];

    return $map[$method] ?? 'ambil_sendiri';
}

function normalize_category_slug_value($slug)
{
    $slug = trim((string) $slug);

    $map = [
        'mirrorless' => 'kamera-mirrorless',
        'kamera-mirrorless' => 'kamera-mirrorless',
        'lens' => 'lensa',
        'lensa' => 'lensa',
        'video' => 'video',
    ];

    return $map[$slug] ?? $slug;
}

function normalize_login_identifier($login)
{
    $login = trim((string) $login);

    $map = [
        'petugas' => 'staff',
        'pelanggan' => 'user',
        'petugas@lenscraft.local' => 'staff@lenscraft.local',
        'pelanggan@example.com' => 'user@example.com',
    ];

    return $map[$login] ?? $login;
}

function build_rental_code()
{
    return 'RENT-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
}

function build_return_code()
{
    return 'RET-' . date('Y') . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
}

function product_daily_rate($product)
{
    $price = (float) ($product['price_per_day'] ?? $product['price'] ?? 0);
    $discount = (float) ($product['discount_percentage'] ?? $product['discount'] ?? 0);

    if ($discount <= 0) {
        return $price;
    }

    return round($price - (($discount / 100) * $price), 2);
}

function apply_common_template_transforms($html)
{
    return str_replace('.html', '.php', $html);
}

function replace_script_array($html, $const_name, $data)
{
    $pattern = '/const\s+' . preg_quote($const_name, '/') . '\s*=\s*\[[\s\S]*?\];/';
    $replacement = 'const ' . $const_name . ' = ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';';

    return preg_replace($pattern, $replacement, $html, 1) ?? $html;
}

function replace_script_object($html, $const_name, $data)
{
    $pattern = '/const\s+' . preg_quote($const_name, '/') . '\s*=\s*\{[\s\S]*?\};/';
    $replacement = 'const ' . $const_name . ' = ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';';

    return preg_replace($pattern, $replacement, $html, 1) ?? $html;
}

function inject_before_body_end($html, $snippet)
{
    if ($snippet === '') {
        return $html;
    }

    return str_replace('</body>', $snippet . '</body>', $html);
}

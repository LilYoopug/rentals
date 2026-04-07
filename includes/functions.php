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
})();
</script>
HTML;
}

function page_runtime_bundle($flash_script = '')
{
    return toast_runtime_script() . $flash_script;
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
    return (current_user()['role'] ?? '') === 'admin';
}

function is_staff_user()
{
    $role = current_user()['role'] ?? '';

    return $role === 'staff' || $role === 'admin';
}

function is_customer_user()
{
    return (current_user()['role'] ?? '') === 'user';
}

function redirect_logged_in_user_home()
{
    $role = current_user()['role'] ?? 'user';

    if ($role === 'admin') {
        redirect_to('admin/index.php');
    }

    if ($role === 'staff') {
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
    return '$' . number_format((float) $amount, 2);
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

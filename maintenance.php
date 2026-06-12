<?php
/**
 * Standalone maintenance page — Omahjong
 *
 * Usage:
 *   - Open directly: https://your-domain/maintenance.php
 *   - Or at the top of index.php (before Laravel boots), add:
 *       require __DIR__ . '/maintenance.php';
 *       exit;
 *   - Or point your web server document root / vhost to this file temporarily.
 */

http_response_code(503);
header('Content-Type: text/html; charset=utf-8');
header('Retry-After: 3600');

$siteName = 'Omahjong';
$title = $siteName . ' — Sedang dalam perbaikan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    :root {
      --brand: #006131;
      --brand-dark: #004d27;
      --text: #1a1a1a;
      --muted: #5c6b7a;
      --bg: #f4f6f8;
      --card: #ffffff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--text);
      padding: 1.5rem;
      line-height: 1.5;
    }
    .card {
      max-width: 480px;
      width: 100%;
      background: var(--card);
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
      padding: 2.5rem 2rem;
      text-align: center;
    }
    .icon {
      width: 72px;
      height: 72px;
      margin: 0 auto 1.25rem;
      background: var(--brand);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .icon svg {
      width: 36px;
      height: 36px;
      fill: #fff;
    }
    h1 {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--brand-dark);
      margin-bottom: 0.5rem;
    }
    .brand {
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--brand);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 1rem;
    }
    p {
      color: var(--muted);
      font-size: 1rem;
      margin-bottom: 0.75rem;
    }
    p:last-of-type { margin-bottom: 0; }
    .hint {
      margin-top: 1.5rem;
      padding-top: 1.25rem;
      border-top: 1px solid #e8ecef;
      font-size: 0.8125rem;
      color: #8a96a3;
    }
  </style>
</head>
<body>
  <main class="card" role="main">
    <div class="icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
      </svg>
    </div>
    <p class="brand"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></p>
    <h1>Situs sedang dalam perbaikan</h1>
    <p>
      Kami sedang melakukan pemeliharaan untuk meningkatkan layanan.
      Silakan kembali lagi nanti.
    </p>
    <p>Terima kasih atas pengertian Anda.</p>
    <p class="hint">Halaman ini bersifat sementara. Jika Anda adalah pengelola situs, nonaktifkan mode maintenance setelah selesai.</p>
  </main>
</body>
</html>

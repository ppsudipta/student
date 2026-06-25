<?php
$publicUrl = 'pages/';
$adminUrl = 'admin/';
$apiUrl = 'laravel-api/public/api/health';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Launcher</title>
  <style>
    :root {
      color-scheme: light;
      --bg: #f5efe6;
      --card: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --accent: #b45309;
      --accent-dark: #92400e;
      --border: #eadfce;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top left, #fff6e8 0, transparent 30%),
        linear-gradient(180deg, var(--bg) 0%, #f8fafc 100%);
      display: grid;
      place-items: center;
      padding: 24px;
    }

    .card {
      width: min(720px, 100%);
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
    }

    h1 {
      margin: 0 0 12px;
      font-size: 32px;
    }

    p {
      margin: 0 0 16px;
      line-height: 1.6;
      color: var(--muted);
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin: 24px 0;
    }

    .button {
      display: inline-block;
      padding: 14px 18px;
      border-radius: 12px;
      text-decoration: none;
      font-weight: bold;
      border: 1px solid var(--accent);
      color: #fff;
      background: var(--accent);
    }

    .button.secondary {
      background: #fff;
      color: var(--accent-dark);
    }

    code {
      background: #f3f4f6;
      padding: 2px 6px;
      border-radius: 6px;
      color: #111827;
    }

    ul {
      margin: 0;
      padding-left: 18px;
      color: var(--muted);
      line-height: 1.7;
    }
  </style>
</head>
<body>
  <main class="card">
    <h1>Project Launcher</h1>
    <p>This old PHP project has two separate entry points. Use the links below after Apache and MySQL are running in XAMPP.</p>

    <div class="actions">
      <a class="button" href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>">Open Public App</a>
      <a class="button secondary" href="<?= htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8'); ?>">Open Admin Panel</a>
      <a class="button secondary" href="<?= htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>">Check API</a>
    </div>

    <ul>
      <li>Public/student app: <code>/admin/pages/</code></li>
      <li>Admin panel: <code>/admin/admin/</code></li>
      <li>Laravel API: <code>/admin/laravel-api/public/api/</code></li>
      <li>Database configured in code: <code>a1773756_app</code></li>
      <li>Local setup notes: <code>SETUP.md</code></li>
    </ul>
  </main>
</body>
</html>

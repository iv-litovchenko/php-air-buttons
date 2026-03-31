<?php

use IvLitovchenko\PhpAirButtons\PhpAirButtons;

error_reporting(E_ALL);
ini_set('display_startup_errors', 1);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/PhpAirButtons.php';

$error    = null;
$success  = false;

// Главная страница для ссылки после успешного входа.
$cfg = require __DIR__ . '/phpair.demo.config.php';
$cfg = PhpAirButtons::applyRouteExpansionFromConfig($cfg);
$homeUrl = $cfg['routes']['home'] ?? 'demo-landing.php';

// По умолчанию после логина/логаута ведём на демо‑страницу.
$redirect = $homeUrl;

if (!empty($_SERVER['HTTP_REFERER'])) {
    // Простейшая защита: не уходим на другой домен.
    $ref  = $_SERVER['HTTP_REFERER'];
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '' && strpos($ref, $host) !== false) {
        $redirect = $ref;
    }
}

// Обработка выхода.
if (isset($_GET['logout'])) {
    PhpAirButtons::clearAdminCookie();
    header('Location: ' . $redirect);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($login === PhpAirButtons::LOGIN && $password === PhpAirButtons::PASSWORD) {
        PhpAirButtons::setAdminCookie();
        $success = true;
        $error = null;
    }

    $error = 'Неверный логин или пароль.';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>PHPAirButtons — вход</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php PhpAirButtons::ensureCssInjectedOnce(); ?>
    <style>
        :root {
            color-scheme: dark;
        }
        .phpair-login-page {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background:
                radial-gradient(circle at -10% -10%, rgba(56, 189, 248, 0.24), transparent 55%),
                radial-gradient(circle at 110% 110%, rgba(249, 115, 22, 0.22), transparent 55%),
                radial-gradient(circle at 80% 0%, rgba(129, 140, 248, 0.26), transparent 55%),
                #020617;
        }
        .phpair-login-card {
            max-width: 400px;
            width: 100%;
            border-radius: 22px;
            padding: 22px 22px 18px;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background:
                radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.16), transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(248, 250, 252, 0.07), transparent 60%),
                rgba(15, 23, 42, 0.98);
            box-shadow:
                0 22px 60px rgba(15, 23, 42, 0.95),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }
        .phpair-login-noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.08;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 160 160' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='noStitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.7'/%3E%3C/svg%3E");
            mix-blend-mode: soft-light;
        }
        .phpair-login-card h1 {
            margin-bottom: 8px;
            font-size: 20px;
        }
        .phpair-login-card p {
            font-size: 13px;
        }
        .phpair-login-card code {
            font-size: 11px;
        }
        .phpair-field {
            margin-bottom: 12px;
        }
        .phpair-input {
            border-radius: 10px;
        }
        .phpair-login-actions {
            margin-top: 14px;
        }
        .phpair-login-btn {
            width: 100%;
            justify-content: center;
        }
    </style>
</head>
<body class="phpair-login-page">
    <div class="phpair-login-noise"></div>
    <phpair-login-shell class="phpair-login-card">
        <h1>PHPAirButton — вход</h1>
        <p>
            Введи логин и пароль, чтобы включить панели редактирования на этом сайте.
            Для демо‑стенда используются значения
            <code>airadmin / airsecret</code>.
        </p>

        <?php if ($error !== null): ?>
            <phpair-alert class="phpair-login-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </phpair-alert>
        <?php endif; ?>

        <?php if ($success): ?>
            <phpair-alert class="phpair-login-success">
                <strong>Добро пожаловать - приятной работы</strong><br>
                <a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>" style="color: inherit; text-decoration: underline;">
                    Перейти на главную
                </a>
            </phpair-alert>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="">
            <phpair-field-block class="phpair-field">
                <label for="phpair-login">Логин</label>
                <input type="text" id="phpair-login" name="login" class="phpair-input" autocomplete="username">
            </phpair-field-block>
            <phpair-field-block class="phpair-field">
                <label for="phpair-password">Пароль</label>
                <input type="password" id="phpair-password" name="password" class="phpair-input" autocomplete="current-password">
            </phpair-field-block>
            <phpair-actions-row class="phpair-login-actions">
                <button type="submit" class="phpair-button-row phpair-login-btn">
                    <phpair-tile class="phpair-btn phpair-btn--options" aria-hidden="true"><phpair-glyph>⏎</phpair-glyph></phpair-tile>
                    <phpair-caption class="phpair-btn-label">Войти</phpair-caption>
                </button>
            </phpair-actions-row>
        </form>
        <?php endif; ?>
    </phpair-login-shell>
</body>
</html>


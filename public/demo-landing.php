<?php

use IvLitovchenko\PhpAirButtons\PhpAirButtons;

require_once __DIR__ . '/../src/PhpAirButtons.php';

/** @var array $cfg */
$cfg = require __DIR__ . '/phpair.demo.config.php';
$cfg = PhpAirButtons::applyRouteExpansionFromConfig($cfg);

// До любого HTML: cookie + redirect для ?toggleEditing=…
PhpAirButtons::handleEditingToggleRequest();

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($cfg['page']['title'] ?? 'Demo', ENT_QUOTES, 'UTF-8') ?></title>
    <!-- CSS overlay вставляется inline при рендере верхней панели (adminPanelCreate()->render()). -->
    <style>
        :root {
            color-scheme: dark;
            --bg-deep: #020617;
            --bg-glass: rgba(15, 23, 42, 0.88);
            --bg-glass-soft: rgba(15, 23, 42, 0.7);
            --border-subtle: rgba(148, 163, 184, 0.35);
            --accent-cyan: #22d3ee;
            --accent-cyan-soft: rgba(34, 211, 238, 0.2);
            --accent-amber-soft: rgba(245, 158, 11, 0.2);
            --accent-purple-soft: rgba(168, 85, 247, 0.28);
            --text-main: #e5e7eb;
            --text-muted: #94a3b8;
        }
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at -10% -10%, rgba(56, 189, 248, 0.24), transparent 55%),
                radial-gradient(circle at 110% 110%, rgba(249, 115, 22, 0.22), transparent 55%),
                radial-gradient(circle at 80% 0%, rgba(129, 140, 248, 0.32), transparent 55%),
                var(--bg-deep);
            color: var(--text-main);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
        }
        a {
            color: #38bdf8;
            text-decoration: none;
        }
        a:hover {
            text-decoration: none;
        }
        .noise-overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: 0.09;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 160 160' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='noStitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.7'/%3E%3C/svg%3E");
            mix-blend-mode: soft-light;
        }
        .wrap {
            position: relative;
            z-index: 1;
            max-width: 1120px;
            margin: 0 auto;
            padding: 32px 18px 72px;
        }
        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 22px 22px 20px;
            border: 1px solid var(--border-subtle);
            background:
                radial-gradient(circle at 0% 0%, var(--accent-cyan-soft), transparent 60%),
                radial-gradient(circle at 100% 100%, var(--accent-amber-soft), transparent 60%),
                linear-gradient(145deg, rgba(15, 23, 42, 0.97), rgba(15, 23, 42, 0.86));
            box-shadow:
                0 22px 60px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(15, 23, 42, 0.9);
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(0, 1.4fr);
            gap: 22px;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(15, 23, 42, 0.95);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .hero-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent-cyan);
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.25);
        }
        .hero h1 {
            margin: 10px 0 8px;
            font-size: 30px;
            letter-spacing: 0.02em;
        }
        .hero p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.55;
            font-size: 14px;
        }
        .hero-meta {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .hero-meta code {
            padding: 1px 5px;
            border-radius: 999px;
            border: 1px solid rgba(51, 65, 85, 0.9);
            background: rgba(15, 23, 42, 0.9);
            font-size: 11px;
        }
        .hero-right {
            position: relative;
            padding: 10px 0 0;
        }
        .hero-right-shell {
            position: relative;
            border-radius: 18px;
            padding: 12px 12px 10px;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background:
                radial-gradient(circle at 0% 0%, rgba(56, 189, 248, 0.18), transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(248, 250, 252, 0.09), transparent 60%),
                rgba(15, 23, 42, 0.92);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.95);
        }
        .hero-right-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .hero-right-title strong {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #cbd5e1;
        }
        .hero-steps {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .hero-step {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hero-step-index {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            background: rgba(15, 23, 42, 0.9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #e5e7eb;
        }
        .hero-step-main {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .hero-step-main code {
            padding: 2px 6px;
            border-radius: 999px;
            border: 1px solid rgba(55, 65, 81, 0.95);
            background: rgba(15, 23, 42, 0.98);
        }
        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid rgba(56, 189, 248, 0.7);
            background: rgba(8, 47, 73, 0.85);
            font-size: 11px;
            color: #e0f2fe;
        }
        .hero-chip-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #22c55e;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 18px;
            margin-top: 20px;
        }
        .card {
            border-radius: 18px;
            padding: 14px 14px 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background:
                radial-gradient(circle at 0% 0%, rgba(148, 163, 184, 0.18), transparent 60%),
                rgba(2, 6, 23, 0.78);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.9);
        }
        .card h2 {
            margin: 0 0 6px;
            font-size: 15px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .card-tag {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--text-muted);
        }
        .muted {
            color: var(--text-muted);
            font-size: 13px;
        }
        /* Block + Alert custom tags */
        phpair-block-shell,
        phpair-block-header,
        phpair-block-body,
        phpair-alert-block,
        phpair-alert-icon {
            display: block;
        }
        /* Капсулы и кнопки в стиле BUTTON.NEW (button-row / btn) */
        .button-row {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 10px;
            background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.35), transparent 55%),
                        rgba(17, 24, 39, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.9);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.8);
            text-decoration: none;
            color: inherit;
        }
        a.button-row:hover {
            border-color: rgba(248, 250, 252, 0.95);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.95);
            background: radial-gradient(circle at top left, rgba(248, 250, 252, 0.55), transparent 55%),
                        rgba(17, 24, 39, 0.98);
        }
        a.button-row,
        a.btn {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            border-radius: 10px;
            background: #111827;
            border: 1px solid rgba(148, 163, 184, 0.7);
            color: #e5e7eb;
            font-size: 12px;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.9);
            user-select: none;
        }
        .btn .icon {
            font-size: 14px;
            line-height: 1;
        }
        .btn:hover {
            filter: brightness(1.08);
            border-color: #f9fafb;
            box-shadow:
                0 0 0 1px rgba(248, 250, 252, 0.85),
                inset 0 0 0 1px rgba(15, 23, 42, 0.9);
        }
        .btn--edit    { border-color: rgba(59, 130, 246, 0.95); box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.4), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn--new     { border-color: rgba(16, 185, 129, 0.9); box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.4), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn--hide    { border-color: rgba(148, 163, 184, 0.9); }
        .btn--delete  { border-color: rgba(239, 68, 68, 0.9); box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.4), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn--move    { border-color: rgba(249, 115, 22, 0.9); box-shadow: 0 0 0 1px rgba(234, 88, 12, 0.4), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn--cache   { border-color: rgba(168, 85, 247, 0.9); box-shadow: 0 0 0 1px rgba(147, 51, 234, 0.35), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn--options { border-color: rgba(56, 189, 248, 0.9); box-shadow: 0 0 0 1px rgba(14, 165, 233, 0.35), inset 0 0 0 1px rgba(15, 23, 42, 0.9); }
        .btn-label {
            padding: 0 6px;
            font-size: 12px;
            color: #e5e7eb;
            white-space: nowrap;
        }
        .flash {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid rgba(56, 189, 248, 0.35);
            background: rgba(56, 189, 248, 0.08);
            color: #e5e7eb;
            font-size: 13px;
        }
        /* Popup / iframe demo */
        .phpair-overlay {
            position: fixed;
            inset: 0;
            background: rgba(3, 7, 18, 0.75);
            backdrop-filter: blur(6px);
            display: none;
            z-index: 1200;
        }
        .phpair-overlay.is-open {
            display: block;
        }
        .phpair-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(980px, calc(100vw - 32px));
            height: min(640px, calc(100vh - 32px));
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(15, 23, 42, 0.98);
            box-shadow: 0 35px 90px rgba(0,0,0,0.55);
            overflow: hidden;
        }
        .phpair-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            background: radial-gradient(circle at 20% 10%, rgba(56, 189, 248, 0.18), transparent 45%),
                        rgba(17, 24, 39, 0.85);
        }
        .phpair-popup-title {
            font-size: 12px;
            color: #e5e7eb;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .phpair-popup-close {
            min-width: 24px;
            height: 24px;
        }
        .phpair-popup-close-row {
            padding: 2px 6px;
        }
        .phpair-popup-body {
            padding: 14px 22px 18px;
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.45;
        }
        .phpair-popup-body p {
            margin: 0 0 6px;
        }
        .phpair-popup-body p:first-child {
            margin-top: 2px;
        }
        .phpair-popup-body p:last-child {
            margin-bottom: 0;
        }
        @media (max-width: 860px) {
            .hero {
                grid-template-columns: minmax(0, 1fr);
            }
            .grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="noise-overlay"></div>
<?php
// Верхняя панель: показывается только если включён “админ” (cookie phpair_admin=1).
PhpAirButtons::adminPanelCreate()
    ->brand('DEMO SITE', $cfg['routes']['home'] ?? 'demo-landing.php')
    ->user($cfg['user']['name'] ?? 'Editor', ($cfg['routes']['settings'] ?? 'demo-landing.php?settings=1'))
    ->pageId((string)($cfg['page']['id'] ?? '0'), '#page-info')
    ->clearCache($cfg['routes']['clearCache'] ?? 'demo-landing.php?clearCache=1')
    ->colors($cfg['routes']['colors'] ?? 'demo-landing.php?colors=1')
    ->settings($cfg['routes']['settings'] ?? 'demo-landing.php?settings=1')
    ->materialsRows([
        [
            'name'    => 'Новость',
            'listUrl' => '#list-news',
            'count'   => 128,
            'newUrl'  => '#new-news',
            'newTitle'=> 'Создать запись «Новость»',
        ],
        [
            'name'    => 'Статья',
            'listUrl' => '#list-article',
            'count'   => 54,
            'newUrl'  => '#new-article',
            'newTitle'=> 'Создать запись «Статья»',
        ],
    ])
    ->materialsTotal(182)
    ->editing(null, $cfg['routes']['toggleEditing'] ?? 'demo-landing.php?toggleEditing=1')
    ->backend($cfg['routes']['backend'] ?? 'demo-landing.php?backend=1')
    ->logout($cfg['routes']['logout'] ?? 'airlogin.php?logout=1')
    ->render();
?>

<div class="wrap">
    <div class="hero">
        <div>
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                <span>Frontend editing overlay</span>
            </div>
            <h1>PHPAirButtons — живой стенд кнопок редактирования</h1>
            <p>
                Этот лендинг показывает, как верхняя панель и плашки редактирования встраиваются прямо в обычную
                страницу сайта. Включи режим администратора через
                <a href="<?= htmlspecialchars($cfg['routes']['login'] ?? 'airlogin.php', ENT_QUOTES, 'UTF-8') ?>">airlogin.php</a>,
                а затем вернись сюда и посмотри на реальные теги <code>&lt;phpair-* &gt;</code> поверх контента.
            </p>
            <div class="hero-meta" id="page-info">
                <span>Page ID: <code><?= htmlspecialchars((string)($cfg['page']['id'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></code></span>
                <span>Editing cookie: <code><?= htmlspecialchars(PhpAirButtons::EDITING_COOKIE_NAME, ENT_QUOTES, 'UTF-8') ?>=<?= PhpAirButtons::isEditing() ? '1' : '0' ?></code></span>
                <span>Admin cookie: <code><?= htmlspecialchars(PhpAirButtons::COOKIE_NAME, ENT_QUOTES, 'UTF-8') ?>=<?= htmlspecialchars($_COOKIE[PhpAirButtons::COOKIE_NAME] ?? '0', ENT_QUOTES, 'UTF-8') ?></code></span>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-right-shell">
                <div class="hero-right-title">
                    <strong>Как смотреть демо</strong>
                    <span class="hero-chip">
                        <span class="hero-chip-dot"></span>
                        Панели рендерятся только для админа
                    </span>
                </div>
                <div class="hero-steps">
                    <div class="hero-step">
                        <div class="hero-step-index">1</div>
                        <div class="hero-step-main">
                            <span>Зайди на логин:</span>
                            <code>airlogin.php</code>
                        </div>
                    </div>
                    <div class="hero-step">
                        <div class="hero-step-index">2</div>
                        <div class="hero-step-main">
                            <span>Введи</span>
                            <code>airadmin / airsecret</code>
                        </div>
                    </div>
                    <div class="hero-step">
                        <div class="hero-step-index">3</div>
                        <div class="hero-step-main">
                            <span>Обнови эту страницу и пощёлкай иконки Edit / New.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h2>
                Пример “существующей записи”
                <span class="card-tag">(EditIcon)</span>
            </h2>
            <p class="muted">Плашка и кнопки действий появляются только в режиме админа.</p>
            <?php
            PhpAirButtons::editIcons()
                    ->name('Новость')
                    ->recordId('1993')
                    ->editorUrl('#edit-record-1993')
                ->hideUrl('#hide-record-1993')
                ->deleteUrl('#delete-record-1993')
                ->createSiblingUrl('#clone-record-1993')
                ->tooltip('Тип записи / заголовок элемента')
                ->render();
            ?>
        </div>
        <div class="card">
            <h2>
                Block + Alert
                <span class="card-tag">(Info blocks)</span>
            </h2>
            <p class="muted">Современная версия твоих Block/block-*.css и blockAlert-*.css.</p>

            <phpair-block-shell style="margin-top:10px; border-radius:14px; border:1px solid rgba(148,163,184,0.5); overflow:hidden; background:rgba(15,23,42,0.96); box-shadow:0 18px 45px rgba(15,23,42,0.9);">
                <phpair-block-header style="display:flex; align-items:center; gap:8px; padding:6px 10px; background:linear-gradient(90deg, rgba(56,189,248,0.24), rgba(129,140,248,0.24)); border-bottom:1px solid rgba(148,163,184,0.5);">
                    <phpair-block-icon class="btn btn--options" style="min-width:22px; height:22px;">
                        i
                    </phpair-block-icon>
                    <phpair-block-title style="font-size:13px; font-weight:500;">Заголовок блока</phpair-block-title>
                </phpair-block-header>
                <phpair-block-body style="padding:12px 14px; font-size:13px; color:#e5e7eb; line-height:1.5;">
                    Это демонстрация Block/block-*.css в новом стиле: скругления, отдельная шапка, слегка подсвеченный фон и более читаемый текст.
                </phpair-block-body>
            </phpair-block-shell>

            <?php PhpAirButtons::renderIfAdminAndEditing(function () { ?>
                <phpair-alert-block style="margin-top:10px;
                            padding:8px 10px 8px 30px;
                            border-radius:12px;
                            background:linear-gradient(90deg, #facc15, #f97316);
                            color:#1f2937;
                            font-size:13px;
                            position:relative;
                            box-shadow:0 15px 35px rgba(0,0,0,0.45);">
                    <phpair-alert-icon style="position:absolute;
                                 left:10px;
                                 top:50%;
                                 transform:translateY(-50%);
                                 font-size:14px;
                                 font-weight:bold;">!</phpair-alert-icon>
                    Alert‑плашка (blockAlert): короткое важное сообщение для редактора.
                </phpair-alert-block>
            <?php }); ?>
        </div>

        <div class="card">
            <h2>
                Пример “создать здесь”
                <span class="card-tag">(NewIcon)</span>
            </h2>
            <p class="muted">Удобно ставить в колонках/блоках контента как placeholder.</p>
            <?php
            PhpAirButtons::newIcon()
                ->name('Новость')
                ->createLink('#new-tt-content-here')
                ->tooltip('Создать контент в этой колонке')
                ->render();
            ?>
        </div>
        <div class="card">
            <h2>
                Popup container
                <span class="card-tag">(Popup)</span>
            </h2>
            <p class="muted">
                Пример всплывающего окна: сюда можно встроить форму редактирования в iframe или любую админскую страницу.
            </p>
            <?php PhpAirButtons::renderIfAdminAndEditing(function () { ?>
                <a href="#popup-demo" class="phpair-button-row" id="phpair-open-popup" title="Открыть popup без ухода со страницы">
                    <phpair-tile class="phpair-btn phpair-btn--edit" aria-hidden="true"><phpair-glyph>▣</phpair-glyph></phpair-tile>
                    <phpair-caption class="phpair-btn-label">Открыть popup</phpair-caption>
                </a>
            <?php }); ?>
        </div>
    </div>
</div>
<?php PhpAirButtons::renderIfAdminAndEditing(function () { ?>
    <phpair-overlay class="phpair-overlay" id="phpair-overlay">
        <phpair-popup-shell class="phpair-popup" role="dialog" aria-modal="true" aria-label="Popup / iframe demo">
            <phpair-popup-header class="phpair-popup-header">
                <phpair-popup-title class="phpair-popup-title">Popup / iframe demo</phpair-popup-title>
                <a href="#close-popup" class="phpair-button-row phpair-popup-close-row" id="phpair-close-popup" title="Закрыть popup">
                    <phpair-tile class="phpair-btn phpair-btn--delete phpair-popup-close" aria-hidden="true"><phpair-glyph>✖</phpair-glyph></phpair-tile>
                    <phpair-caption class="phpair-btn-label">Закрыть</phpair-caption>
                </a>
            </phpair-popup-header>
            <phpair-popup-body class="phpair-popup-body">
                <p>
                    В реальном проекте сюда можно встроить <code>&lt;iframe&gt;</code> с формой редактирования или отдельной админской страницей.
                    После закрытия popup можно обновить только нужный фрагмент страницы через AJAX.
                </p>
                <p style="margin-top: 10px;">
                    Сейчас это просто статический пример, повторяющий поведение из <code>BUTTON.NEW.html</code>.
                </p>
            </phpair-popup-body>
        </phpair-popup-shell>
    </phpair-overlay>
    <script>
        (function () {
            const overlay = document.getElementById('phpair-overlay');
            const openBtn = document.getElementById('phpair-open-popup');
            const closeBtn = document.getElementById('phpair-close-popup');

            function openPopup() {
                if (overlay) {
                    overlay.classList.add('is-open');
                }
            }

            function closePopup() {
                if (overlay) {
                    overlay.classList.remove('is-open');
                }
            }

            if (openBtn) {
                openBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    openPopup();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    closePopup();
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        closePopup();
                    }
                });
            }

            window.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePopup();
                }
            });
        })();
    </script>
<?php }); ?>
</body>
</html>


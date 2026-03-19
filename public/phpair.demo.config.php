<?php
/**
 * Демо-конфиг PHPAirButtons (пример для `public/demo-landing.php` и встраивания в шаблон).
 *
 * Полное описание всех ключей и примеры для продакшена: **README.md** → раздел «Конфигурация».
 *
 * Структура массива:
 *
 * - `routes` — URL для кнопок верхней панели и ссылок демо. Значения должны соответствовать
 *   твоему развёртыванию. Для локального запуска `php -S localhost:8080 -t public` удобны
 *   относительные пути (`demo-landing.php`, `airlogin.php`). На боевом сайте обычно переопределяют
 *   часть ключей: `home` → `/`, `toggleEditing` → `/?toggleEditing=1`, а `login` / `logout` →
 *   абсолютный путь к `airlogin.php` в твоей веб-корневой структуре.
 *
 * - `page` — условные поля «как из CMS» (id/title страницы для подписей в демо).
 *
 * - `user` — имя пользователя для блока «Editor» в панели.
 *
 * - `expand_routes_non_default_port` — `true`/`false`: при `true` раскрывать относительные `routes`
 *   в абсолютные URL (см. ниже). Ключ не указан — считается `true`.
 *
 * - `routes_public_port` — `int|null`: **номер порта во внешних ссылках панели** (`8080`, `443`, …).
 *   `null` — порт берётся из запроса (`$_SERVER['SERVER_PORT']`). Задай число, если URL снаружи
 *   с другим портом, чем видит PHP (прокси), или чтобы явно зафиксировать порт в конфиге.
 *
 * Пример переопределения в шаблоне основного сайта (после `require`):
 *
 *   $cfg = require __DIR__ . '/path/to/phpair.demo.config.php';
 *   $cfg['routes']['home'] = '/';
 *   $cfg['routes']['toggleEditing'] = '/?toggleEditing=1';
 *   $cfg['routes']['login'] = '/PHPAirButton/public/airlogin.php';
 *   $cfg['routes']['logout'] = '/PHPAirButton/public/airlogin.php?logout=1';
 *
 * ---------------------------------------------------------------------------
 * Две настройки для ссылок панели (см. README → «Конфигурация»):
 *
 *   expand_routes_non_default_port … true|false — включить раскрытие относительных `routes`
 *   routes_public_port … integer|null — порт во внешних URL (8080, 3000…) или null (= $_SERVER)
 * ---------------------------------------------------------------------------
 */
return [
    /*
     * === URL панели: порт и абсолютные ссылки ===
     * expand_routes_non_default_port — false отключает любое раскрытие routes.
     * routes_public_port — ЗДЕСЬ задаётся номер порта для ссылок:
     *   null  — взять порт из текущего запроса;
     *   8080  — явно (как у `php -S localhost:8080 -t public`).
     */
    'expand_routes_non_default_port' => true,
    'routes_public_port'             => 8080,

    'routes' => [
        'home'           => 'demo-landing.php',
        'toggleEditing'  => 'demo-landing.php?toggleEditing=1',
        'clearCache'     => 'demo-landing.php?clearCache=1',
        'colors'         => 'demo-landing.php?colors=1',
        'settings'       => 'demo-landing.php?settings=1',
        'backend'        => 'demo-landing.php?backend=1',
        'logout'         => 'airlogin.php?logout=1',
        'login'          => 'airlogin.php',
    ],

    'page' => [
        'id'    => '42',
        'title' => 'Демо‑лендинг',
    ],

    'user' => [
        'name' => 'Editor',
    ],
];

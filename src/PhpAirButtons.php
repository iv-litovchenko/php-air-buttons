<?php

namespace IvLitovchenko\PhpAirButtons;

/**
 * Главная точка входа PHPAirButtons (класс `PhpAirButtons`).
 *
 * Использование в шаблонах:
 *
 *   require_once __DIR__ . '/src/PhpAirButtons.php'; // корень пакета
 *   require_once __DIR__ . '/../src/PhpAirButtons.php'; // из public/*.php
 *
 *   use IvLitovchenko\PhpAirButtons\PhpAirButtons;
 *
 *   if (PhpAirButtons::isAdmin()) {
 *       PhpAirButtons::adminPanelCreate()
 *           ->brand('MyBrand', '/')
 *           ->user($currentUserName ?? 'Editor')
 *           ->pageId((string)($currentPageId ?? 0))
 *           ->editing($isEditingOn ?? true)
 *           ->backend('/admin/')
 *           ->logout('/logout')
 *           ->render();
 *
 *       PhpAirButtons::editIcons()
 *           ->name('Новость')
 *           ->recordId('1993')
 *           ->editorUrl('#edit-record-1993')
 *           ->hideUrl('#hide-record-1993')
 *           ->deleteUrl('#delete-record-1993')
 *           ->tooltip('Тип записи / заголовок элемента')
 *           ->render();
 *
 *       PhpAirButtons::newIcon()
 *           ->name('Новость')
 *           ->createLink('#new-tt-content-here')
 *           ->tooltip('Создать контент в этой колонке')
 *           ->render();
 *   }
 */
final class PhpAirButtons
{
    /** Был ли уже выведен CSS (через <style>) в текущем запросе. */
    private static $cssInjected = false;

    /** Имя куки, управляющей показом панелей. */
    public const COOKIE_NAME = 'phpair_admin';

    /** Имя куки, управляющей режимом редактирования. */
    public const EDITING_COOKIE_NAME = 'phpair_editing';

    /** GET-параметр для переключения режима редактирования. */
    public const TOGGLE_EDITING_PARAM = 'toggleEditing';

    /** Жёстко заданные логин и пароль для /airlogin (можно поменять в проекте). */
    public const LOGIN    = 'airadmin';
    public const PASSWORD = 'airsecret';

    /**
     * Проверка, включён ли режим администратора.
     */
    public static function isAdmin(): bool
    {
        if (php_sapi_name() === 'cli') {
            return false;
        }

        return isset($_COOKIE[self::COOKIE_NAME]) && $_COOKIE[self::COOKIE_NAME] === '1';
    }

    /**
     * Установка куки администратора (вызывается из /airlogin).
     */
    public static function setAdminCookie(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        // 7 дней.
        setcookie(self::COOKIE_NAME, '1', time() + 7 * 24 * 3600, '/', '', false, true);
        $_COOKIE[self::COOKIE_NAME] = '1';
    }

    /**
     * Сброс куки администратора.
     */
    public static function clearAdminCookie(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', false, true);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Текущее состояние режима редактирования по куки.
     */
    public static function isEditing(): bool
    {
        if (php_sapi_name() === 'cli') {
            return false;
        }

        return isset($_COOKIE[self::EDITING_COOKIE_NAME]) && $_COOKIE[self::EDITING_COOKIE_NAME] === '1';
    }

    /**
     * Показ редактируемого UI только когда:
     * - пользователь вошёл в backend (admin cookie)
     * - режим редактирования включён (editing cookie)
     */
    public static function isAdminAndEditing(): bool
    {
        return self::isAdmin() && self::isEditing();
    }

    /**
     * Удобный helper для демо/шаблонов: рендерить блок только в admin+editing.
     */
    public static function renderIfAdminAndEditing(callable $renderer): void
    {
        if (!self::isAdminAndEditing()) {
            return;
        }

        $renderer();
    }

    /**
     * Вставляет CSS inline (через <style>) один раз за запрос.
     *
     * По требованию: когда верхняя панель подключается/рендерится, CSS
     * подтягивается автоматически из файла пакета.
     */
    public static function ensureCssInjectedOnce(): void
    {
        if (self::$cssInjected) {
            return;
        }
        self::$cssInjected = true;

        // CSS лежит в `public/phpairbuttons.css` (рядом с демо), не внутри src.
        $cssPath = __DIR__ . '/../public/phpairbuttons.css';
        if (!is_file($cssPath)) {
            return;
        }

        $css = file_get_contents($cssPath);
        if ($css === false || $css === '') {
            return;
        }

        // CSS считается доверенным (лежит в репозитории пакета).
        echo '<style id="php-air-buttons-css">' . $css . '</style>';
    }

    /**
     * Если в запросе пришёл `?toggleEditing=...` — переключает куку и редиректит обратно.
     *
     * Важно: вызывай **до любого вывода** (echo/HTML), иначе `setcookie`/`header` не сработают.
     * В `AdminPanelBuilder::render()` вызов дублируется только если `headers_sent() === false`.
     */
    public static function handleEditingToggleRequest(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (!array_key_exists(self::TOGGLE_EDITING_PARAM, $_GET)) {
            return;
        }

        $newValue = !self::isEditing();

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $expires = time() + 30 * 24 * 3600; // 30 дней.

        setcookie(
            self::EDITING_COOKIE_NAME,
            $newValue ? '1' : '0',
            $expires,
            '/',
            '',
            $secure,
            true
        );
        $_COOKIE[self::EDITING_COOKIE_NAME] = $newValue ? '1' : '0';

        // Важно: редиректим обратно без `toggleEditing`, иначе будет бесконечный flip.
        $redirect = self::buildEditingToggleRedirectUrl();
        if ($redirect === '') {
            $redirect = $_SERVER['SCRIPT_NAME'] ?? '/';
        }
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * Host[:port] для Location: из массива parse_url (порт нельзя терять — иначе :8080 превратится в :80).
     */
    private static function redirectAuthorityFromUrlParts(array $parts, string $httpHostFallback): string
    {
        $host = $parts['host'] ?? '';
        if ($host === '') {
            return $httpHostFallback;
        }

        $scheme      = strtolower($parts['scheme'] ?? 'http');
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port        = isset($parts['port']) ? (int) $parts['port'] : $defaultPort;

        if ($port !== $defaultPort) {
            return $host . ':' . $port;
        }

        return $host;
    }

    private static function buildEditingToggleRedirectUrl(): string
    {
        $param = self::TOGGLE_EDITING_PARAM;
        $host  = $_SERVER['HTTP_HOST'] ?? '';

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && $host !== '' && strpos($referer, $host) !== false) {
            $parts = parse_url($referer);
            if (!is_array($parts)) {
                return '';
            }

            $query = [];
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
            }
            unset($query[$param]);

            $scheme    = $parts['scheme'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
            $authority = self::redirectAuthorityFromUrlParts($parts, $host);
            $path      = $parts['path'] ?? '';
            $qs        = http_build_query($query);

            return $scheme . '://' . $authority . $path . ($qs !== '' ? '?' . $qs : '');
        }

        // Fallback: относительный путь — браузер сохранит тот же host:port, что у текущего запроса.
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($requestUri === '') {
            return $_SERVER['SCRIPT_NAME'] ?? '/';
        }

        $parts = parse_url($requestUri);
        if (!is_array($parts)) {
            return $_SERVER['SCRIPT_NAME'] ?? '/';
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }
        unset($query[$param]);

        $path = $parts['path'] ?? '';
        if ($path === '') {
            $path = '/';
        }
        $qs = http_build_query($query);

        return $path . ($qs !== '' ? '?' . $qs : '');
    }

    /**
     * Превращает относительные значения в `routes` в абсолютные URL с хостом и портом.
     *
     * @param int|null $routesPublicPort Из конфига `routes_public_port`: явный порт внешнего URL
     *                                    (удобно за прокси или для наглядности). `null` — взять порт из запроса.
     * Уже абсолютные `http(s)://...` не трогает.
     */
    public static function expandRoutesForNonDefaultPort(array $routes, ?int $routesPublicPort = null): array
    {
        if (PHP_SAPI === 'cli') {
            return $routes;
        }

        $https       = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $defaultPort = $https ? 443 : 80;
        $detectedPort = (int) ($_SERVER['SERVER_PORT'] ?? $defaultPort);
        $effectivePort = $routesPublicPort !== null ? (int) $routesPublicPort : $detectedPort;

        // Явный порт в конфиге — всегда раскрываем относительные routes; иначе только если порт «нестандартный».
        $shouldExpand = $routesPublicPort !== null || $detectedPort !== $defaultPort;
        if (!$shouldExpand) {
            return $routes;
        }

        $origin    = self::buildPublicOrigin($effectivePort);
        $dirPrefix = self::buildScriptDirectoryUrlPrefix();

        $out = [];
        foreach ($routes as $key => $value) {
            $out[$key] = self::expandRouteToAbsoluteUrl((string) $value, $origin, $dirPrefix);
        }

        return $out;
    }

    /**
     * Учитывает флаги конфига: `expand_routes_non_default_port`, `routes_public_port`.
     * Явно `expand_routes_non_default_port` ⇒ `false` — не менять `routes`.
     */
    public static function applyRouteExpansionFromConfig(array $cfg): array
    {
        $expand = $cfg['expand_routes_non_default_port'] ?? true;
        if (!$expand || empty($cfg['routes']) || !is_array($cfg['routes'])) {
            return $cfg;
        }

        $forcedPort = null;
        if (array_key_exists('routes_public_port', $cfg) && $cfg['routes_public_port'] !== null && $cfg['routes_public_port'] !== '') {
            $forcedPort = (int) $cfg['routes_public_port'];
        }

        $cfg['routes'] = self::expandRoutesForNonDefaultPort($cfg['routes'], $forcedPort);

        return $cfg;
    }

    /**
     * Имя хоста без порта из `HTTP_HOST` / `SERVER_NAME`.
     */
    private static function parseHostnameFromRequest(): string
    {
        $h = $_SERVER['HTTP_HOST'] ?? '';
        if ($h === '') {
            return $_SERVER['SERVER_NAME'] ?? 'localhost';
        }

        if (isset($h[0]) && $h[0] === '[') {
            $end = strpos($h, ']');
            if ($end !== false) {
                return substr($h, 0, $end + 1);
            }
        }

        $colon = strrpos($h, ':');
        if ($colon !== false) {
            $tail = substr($h, $colon + 1);
            if ($tail !== '' && ctype_digit($tail)) {
                return substr($h, 0, $colon);
            }
        }

        return $h;
    }

    /**
     * scheme://host или scheme://host:port для отображаемого порта.
     */
    private static function buildPublicOrigin(int $port): string
    {
        $https       = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme      = $https ? 'https' : 'http';
        $defaultPort = $https ? 443 : 80;
        $host        = self::parseHostnameFromRequest();

        if ($port === $defaultPort) {
            return $scheme . '://' . $host;
        }

        return $scheme . '://' . $host . ':' . $port;
    }

    /**
     * Префикс пути каталога текущего PHP-скрипта (без завершающего /), например "/sub" или "".
     */
    private static function buildScriptDirectoryUrlPrefix(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/';
        $dir    = dirname(str_replace('\\', '/', $script));
        if ($dir === '/' || $dir === '.' || $dir === '') {
            return '';
        }

        return rtrim($dir, '/');
    }

    private static function expandRouteToAbsoluteUrl(string $route, string $origin, string $dirPrefix): string
    {
        $route = trim($route);
        if ($route === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $route)) {
            return $route;
        }

        if (strpos($route, '//') === 0) {
            $scheme = explode(':', $origin, 2)[0];

            return $scheme . ':' . $route;
        }

        if (isset($route[0]) && $route[0] === '/') {
            return $origin . $route;
        }

        if ($dirPrefix !== '') {
            return $origin . $dirPrefix . '/' . $route;
        }

        return $origin . '/' . $route;
    }

    /**
     * Вход для верхней панели (см. спецификацию в BUTTON.md / BUTTON.NEW.html).
     */
    public static function adminPanelCreate(): AdminPanelBuilder
    {
        return new AdminPanelBuilder(self::isAdmin());
    }

    /**
     * Вход для плашек редактирования существующих записей.
     */
    public static function editIcons(): EditIconsBuilder
    {
        return new EditIconsBuilder(self::isAdmin());
    }

    /**
     * Вход для placeholder‑плашки создания новой записи.
     */
    public static function newIcon(): NewIconBuilder
    {
        return new NewIconBuilder(self::isAdmin());
    }
}

/**
 * Билдер верхней панели администратора.
 * Минимальный набор методов соответствует документации.
 */
final class AdminPanelBuilder
{
    private $enabled;

    private $brandLabel = null;
    private $brandUrl   = null;

    private $userName = null;
    private $userUrl  = null;

    private $pageId    = null;
    private $pageIdUrl = null;

    private $editingOn   = null;
    private $editingUrl  = null;

    private $clearCacheUrl = null;
    private $colorsUrl     = null;
    private $settingsUrl   = null;

    private $backendUrl = null;
    private $logoutUrl  = null;

    /** Строки выпадающего меню «Материалы». */
    private $materialsRows = [];
    /** Число «Всего» внизу меню. */
    private $materialsTotal = null;
    /** Заголовки и подпись блока «Материалы». */
    private $materialsGroupTitle   = null;
    private $materialsSummaryTitle = null;
    private $materialsSummaryLabel = null;
    /** Флаг показа блока «Материалы». */
    private $showMaterials = false;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function brand(string $label, string $url = '/'): self
    {
        $this->brandLabel = $label;
        $this->brandUrl   = $url;
        return $this;
    }

    public function user(string $name, string $url = '#admin-user'): self
    {
        $this->userName = $name;
        $this->userUrl  = $url;
        return $this;
    }

    public function pageId(string $id, string $url = '#admin-dev-info'): self
    {
        $this->pageId    = $id;
        $this->pageIdUrl = $url;
        return $this;
    }

    public function editing(?bool $on = null, string $toggleUrl = '#toggle-editing'): self
    {
        // Если $on не передан (NULL) — читаем состояние из cookie.
        $this->editingOn  = $on;
        $this->editingUrl = $toggleUrl;
        return $this;
    }

    public function clearCache(string $url = '#clear-page-cache'): self
    {
        $this->clearCacheUrl = $url;
        return $this;
    }

    public function colors(string $url): self
    {
        $this->colorsUrl = $url;
        return $this;
    }

    public function settings(string $url): self
    {
        $this->settingsUrl = $url;
        return $this;
    }

    /**
     * Строки выпадающего меню «Материалы».
     *
     * Каждый элемент массива:
     *  - name (string)    — подпись слева
     *  - listUrl (string) — ссылка на список записей
     *  - count (int|string) — счётчик справа
     *  - newUrl (string)  — ссылка на создание записи
     *  - newTitle (string, опционально) — title у кнопки «＋».
     */
    public function materialsRows(array $rows): self
    {
        $this->materialsRows = $rows;
        $this->showMaterials = true;
        return $this;
    }

    /**
     * Число «Всего» внизу меню «Материалы».
     *
     * @param int|string $n
     */
    public function materialsTotal($n): self
    {
        $this->materialsTotal = $n;
        $this->showMaterials  = true;
        return $this;
    }

    public function materialsGroupTitle(string $title): self
    {
        $this->materialsGroupTitle = $title;
        return $this;
    }

    public function materialsSummaryTitle(string $title): self
    {
        $this->materialsSummaryTitle = $title;
        return $this;
    }

    public function materialsSummaryLabel(string $label): self
    {
        $this->materialsSummaryLabel = $label;
        return $this;
    }

    public function showMaterials(bool $on = true): self
    {
        $this->showMaterials = $on;
        return $this;
    }

    public function backend(string $url): self
    {
        $this->backendUrl = $url;
        return $this;
    }

    public function logout(string $url): self
    {
        $this->logoutUrl = $url;
        return $this;
    }

    /**
     * Выводит HTML‑панель (если включен режим администратора).
     */
    public function render(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Переключение редактирования: если заголовки ещё не ушли (панель в начале страницы).
        // Иначе вызывай `PhpAirButtons::handleEditingToggleRequest()` в bootstrap до вывода HTML.
        if (!headers_sent()) {
            PhpAirButtons::handleEditingToggleRequest();
        }

        // Inline CSS, чтобы consumer не обязан был вручную подключать `<link>`.
        PhpAirButtons::ensureCssInjectedOnce();

        $brandLabel = $this->brandLabel !== null ? htmlspecialchars($this->brandLabel, ENT_QUOTES, 'UTF-8') : '';
        $brandUrl   = $this->brandUrl !== null ? htmlspecialchars($this->brandUrl, ENT_QUOTES, 'UTF-8') : '#';

        $userName = $this->userName !== null ? htmlspecialchars($this->userName, ENT_QUOTES, 'UTF-8') : '';
        $userUrl  = $this->userUrl !== null ? htmlspecialchars($this->userUrl, ENT_QUOTES, 'UTF-8') : '#admin-user';

        $pageId    = $this->pageId !== null ? htmlspecialchars($this->pageId, ENT_QUOTES, 'UTF-8') : '';
        $pageIdUrl = $this->pageIdUrl !== null ? htmlspecialchars($this->pageIdUrl, ENT_QUOTES, 'UTF-8') : '#admin-dev-info';

        $backendUrl = $this->backendUrl !== null ? htmlspecialchars($this->backendUrl, ENT_QUOTES, 'UTF-8') : '#admin';
        $logoutUrl  = $this->logoutUrl !== null ? htmlspecialchars($this->logoutUrl, ENT_QUOTES, 'UTF-8') : '#logout';

        $editingOn  = $this->editingOn !== null ? $this->editingOn : PhpAirButtons::isEditing();
        $editingUrl = $this->editingUrl !== null ? htmlspecialchars($this->editingUrl, ENT_QUOTES, 'UTF-8') : '#toggle-editing';

        $clearCacheUrl = $this->clearCacheUrl !== null ? htmlspecialchars($this->clearCacheUrl, ENT_QUOTES, 'UTF-8') : '';
        $colorsUrl     = $this->colorsUrl !== null ? htmlspecialchars($this->colorsUrl, ENT_QUOTES, 'UTF-8') : '';
        $settingsUrl   = $this->settingsUrl !== null ? htmlspecialchars($this->settingsUrl, ENT_QUOTES, 'UTF-8') : '';

        $materialsRows = [];
        foreach ($this->materialsRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name     = isset($row['name']) ? (string)$row['name'] : '';
            $listUrl  = isset($row['listUrl']) ? (string)$row['listUrl'] : '';
            $count    = $row['count'] ?? '';
            $newUrl   = isset($row['newUrl']) ? (string)$row['newUrl'] : '';
            $newTitle = isset($row['newTitle']) ? (string)$row['newTitle'] : '';

            if ($name === '' || $listUrl === '') {
                continue;
            }

            $materialsRows[] = [
                'name'     => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                'listUrl'  => htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8'),
                'count'    => is_string($count) || is_int($count)
                    ? htmlspecialchars((string)$count, ENT_QUOTES, 'UTF-8')
                    : '',
                'newUrl'   => $newUrl !== '' ? htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8') : '',
                'newTitle' => $newTitle !== '' ? htmlspecialchars($newTitle, ENT_QUOTES, 'UTF-8') : '',
            ];
        }

        $materialsTotal = null;
        if ($this->materialsTotal !== null) {
            $materialsTotal = htmlspecialchars((string)$this->materialsTotal, ENT_QUOTES, 'UTF-8');
        }

        $materialsGroupTitle   = $this->materialsGroupTitle !== null
            ? htmlspecialchars($this->materialsGroupTitle, ENT_QUOTES, 'UTF-8')
            : 'Группа материалов';
        $materialsSummaryTitle = $this->materialsSummaryTitle !== null
            ? htmlspecialchars($this->materialsSummaryTitle, ENT_QUOTES, 'UTF-8')
            : 'Итого';
        $materialsSummaryLabel = $this->materialsSummaryLabel !== null
            ? htmlspecialchars($this->materialsSummaryLabel, ENT_QUOTES, 'UTF-8')
            : 'Всего';

        // Уникальные теги phpair-* (без span/div внутри панели).
        echo '<phpair-admin-panel class="phpair-admin-root">';
        echo '<phpair-topbar>';

        // Левая часть: бренд.
        echo '<phpair-side-left>';
        if ($brandLabel !== '') {
            echo '<a href="' . $brandUrl . '" class="phpair-logo" title="На главную">';
            echo '<phpair-brand-label>' . $brandLabel . '</phpair-brand-label>';
            echo '</a>';
        }
        echo '</phpair-side-left>';

        // Правая часть: группы кнопок.
        echo '<phpair-side-right>';

        // User.
        if ($userName !== '') {
            echo '<a href="' . $userUrl . '" class="phpair-button-row" title="Пользователь">';
            echo '<phpair-tile class="phpair-btn phpair-btn--options" aria-hidden="true"><phpair-glyph>👤</phpair-glyph></phpair-tile>';
            echo '<phpair-caption class="phpair-btn-label">' . $userName . '</phpair-caption>';
            echo '</a>';
        }

        // Dev / pageId.
        if ($pageId !== '') {
            echo '<a href="' . $pageIdUrl . '" class="phpair-button-row" title="ID страницы">';
            echo '<phpair-tile class="phpair-btn phpair-btn--options" aria-hidden="true"><phpair-glyph>#</phpair-glyph></phpair-tile>';
            echo '<phpair-caption class="phpair-btn-label">ID: ' . $pageId . '</phpair-caption>';
            echo '</a>';
        }

        // Editing toggle (должна идти первой в “служебном” блоке).
        $editingClass = $editingOn ? 'phpair-btn--editing-on' : 'phpair-btn--editing-off';
        echo '<a href="' . $editingUrl . '" class="phpair-button-row" title="Переключить режим редактирования">';
        echo '<phpair-tile class="phpair-btn ' . $editingClass . '" aria-hidden="true"><phpair-glyph>✎</phpair-glyph></phpair-tile>';
        echo '<phpair-caption class="phpair-btn-label">Editing: ' . ($editingOn ? 'ON' : 'OFF') . '</phpair-caption>';
        echo '</a>';

        // Clear cache (опционально, сразу после Editing).
        if ($clearCacheUrl !== '') {
            echo '<a href="' . $clearCacheUrl . '" class="phpair-button-row" title="Сбросить кэш">';
            echo '<phpair-tile class="phpair-btn phpair-btn--cache" aria-hidden="true"><phpair-glyph>⟳</phpair-glyph></phpair-tile>';
            echo '<phpair-caption class="phpair-btn-label">Cache</phpair-caption>';
            echo '</a>';
        }

        // Materials dropdown (опционально, после Cache, перед блоком настроек / админкой).
        if ($this->showMaterials && $materialsRows !== []) {
            echo '<phpair-materials class="phpair-materials-root">';
            echo '<details class="phpair-button-row phpair-materials-dropdown">';
            echo '<summary class="phpair-materials-summary">';
            echo '<phpair-tile class="phpair-btn phpair-btn--options" aria-hidden="true"><phpair-glyph>▾</phpair-glyph></phpair-tile>';
            echo '<phpair-caption class="phpair-btn-label">Материалы</phpair-caption>';
            echo '</summary>';

            echo '<phpair-materials-menu class="phpair-materials-menu">';
            echo '<phpair-materials-group class="phpair-materials-group">';
            echo '<phpair-materials-group-title>' . $materialsGroupTitle . '</phpair-materials-group-title>';

            foreach ($materialsRows as $row) {
                echo '<phpair-materials-item class="phpair-materials-item">';
                echo '<a href="' . $row['listUrl'] . '" class="phpair-materials-name">' . $row['name'] . '</a>';
                echo '<phpair-materials-meta class="phpair-materials-meta">';
                if ($row['count'] !== '') {
                    echo '<phpair-materials-count>' . $row['count'] . '</phpair-materials-count>';
                }
                if ($row['newUrl'] !== '') {
                    $titleAttr = $row['newTitle'] !== '' ? ' title="' . $row['newTitle'] . '"' : '';
                    echo '<a href="' . $row['newUrl'] . '" class="phpair-btn phpair-btn--new"' . $titleAttr . '>';
                    echo '<phpair-glyph>＋</phpair-glyph>';
                    echo '</a>';
                }
                echo '</phpair-materials-meta>';
                echo '</phpair-materials-item>';
            }

            echo '</phpair-materials-group>';

            if ($materialsTotal !== null) {
                echo '<phpair-materials-group class="phpair-materials-group phpair-materials-group--summary">';
                echo '<phpair-materials-group-title>' . $materialsSummaryTitle . '</phpair-materials-group-title>';
                echo '<phpair-materials-item class="phpair-materials-item">';
                echo '<phpair-materials-name>' . $materialsSummaryLabel . '</phpair-materials-name>';
                echo '<phpair-materials-count>' . $materialsTotal . '</phpair-materials-count>';
                echo '</phpair-materials-item>';
                echo '</phpair-materials-group>';
            }

            echo '</phpair-materials-menu>';
            echo '</details>';
            echo '</phpair-materials>';
        }

        // Colors / settings (опционально, объединены в одну группу).
        if ($colorsUrl !== '' || $settingsUrl !== '') {
            echo '<phpair-settings-group class="phpair-button-row">';
            if ($colorsUrl !== '') {
                echo '<a href="' . $colorsUrl . '" class="phpair-btn phpair-btn--options" title="Цвета кнопок">';
                echo '<phpair-glyph>🎨</phpair-glyph>';
                echo '</a>';
            }
            if ($settingsUrl !== '') {
                echo '<a href="' . $settingsUrl . '" class="phpair-btn phpair-btn--options" title="Настройки">';
                echo '<phpair-glyph>⚙</phpair-glyph>';
                echo '</a>';
            }
            echo '</phpair-settings-group>';
        }

        // Backend.
        echo '<a href="' . $backendUrl . '" class="phpair-button-row" title="Админка">';
        echo '<phpair-tile class="phpair-btn phpair-btn--options" aria-hidden="true"><phpair-glyph>🏛</phpair-glyph></phpair-tile>';
        echo '<phpair-caption class="phpair-btn-label">Admin</phpair-caption>';
        echo '</a>';

        // Logout.
        echo '<a href="' . $logoutUrl . '" class="phpair-button-row" title="Выход">';
        echo '<phpair-tile class="phpair-btn phpair-btn--delete" aria-hidden="true"><phpair-glyph>⎋</phpair-glyph></phpair-tile>';
        echo '<phpair-caption class="phpair-btn-label">Logout</phpair-caption>';
        echo '</a>';

        echo '</phpair-side-right>';
        echo '</phpair-topbar>';
        echo '</phpair-admin-panel>';
    }
}

/**
 * Билдер для плашки существующей записи (EditIcon).
 */
final class EditIconsBuilder
{
    private $enabled;

    private $type      = null;
    private $id        = null;
    private $chipUrl   = null;
    private $editUrl   = null;
    private $hideUrl   = null;
    private $deleteUrl = null;
    private $siblingUrl = null;
    private $tooltip   = null;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Установить название (тип записи), отображается в плашке.
     */
    public function name(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Установить ID записи (если есть).
     */
    public function recordId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Установить ссылку на редактирование (если есть).
     * Если `chipUrl` не задан — используем её же и для клика по плашке.
     */
    public function editorUrl(string $editUrl): self
    {
        $this->editUrl = $editUrl;
        if ($this->chipUrl === null) {
            $this->chipUrl = $editUrl;
        }
        return $this;
    }

    public function hideUrl(string $url): self
    {
        $this->hideUrl = $url;
        return $this;
    }

    public function deleteUrl(string $url): self
    {
        $this->deleteUrl = $url;
        return $this;
    }

    public function createSiblingUrl(string $url): self
    {
        $this->siblingUrl = $url;
        return $this;
    }

    public function tooltip(string $text): self
    {
        $this->tooltip = $text;
        return $this;
    }

    public function render(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Требование: скрывать плашку EditIcon, когда редактирование выключено.
        if (!PhpAirButtons::isEditing()) {
            return;
        }
        if ($this->type === null || $this->editUrl === null) {
            return;
        }

        $type    = htmlspecialchars($this->type, ENT_QUOTES, 'UTF-8');
        $editUrl = htmlspecialchars($this->editUrl, ENT_QUOTES, 'UTF-8');
        $id      = $this->id !== null ? htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') : null;
        $chipUrl = htmlspecialchars($this->chipUrl !== null ? $this->chipUrl : $this->editUrl, ENT_QUOTES, 'UTF-8');

        $hideUrl    = $this->hideUrl !== null ? htmlspecialchars($this->hideUrl, ENT_QUOTES, 'UTF-8') : null;
        $deleteUrl  = $this->deleteUrl !== null ? htmlspecialchars($this->deleteUrl, ENT_QUOTES, 'UTF-8') : null;
        $siblingUrl = $this->siblingUrl !== null ? htmlspecialchars($this->siblingUrl, ENT_QUOTES, 'UTF-8') : null;
        $tooltip    = $this->tooltip !== null ? htmlspecialchars($this->tooltip, ENT_QUOTES, 'UTF-8') : null;

        $label = $type . ($id !== null ? ' №' . $id : '');

        echo '<phpair-edit-icons class="phpair-edit-root">';
        // Одна «капсула»: рамка обводит и плашку записи, и кнопки действий при наведении.
        echo '<phpair-edit-scope class="phpair-button-row phpair-button-row--editicon">';

        $titleAttr = $tooltip !== null ? ' title="' . $tooltip . '"' : '';
        echo '<a href="' . $chipUrl . '" class="phpair-edit-chip"' . $titleAttr . '>';
        echo '<phpair-tile class="phpair-btn phpair-btn--move" aria-hidden="true"><phpair-glyph>▤</phpair-glyph></phpair-tile>';
        echo '<phpair-caption class="phpair-btn-label">' . $label . '</phpair-caption>';
        echo '</a>';

        echo '<phpair-edit-actions>';
        echo '<a href="' . $editUrl . '" class="phpair-btn phpair-btn--edit" title="Редактировать запись"><phpair-glyph>✎</phpair-glyph></a>';
        if ($hideUrl !== null) {
            echo '<a href="' . $hideUrl . '" class="phpair-btn phpair-btn--hide" title="Скрыть / показать"><phpair-glyph>☼</phpair-glyph></a>';
        }
        if ($deleteUrl !== null) {
            echo '<a href="' . $deleteUrl . '" class="phpair-btn phpair-btn--delete" title="Удалить / восстановить"><phpair-glyph>✖</phpair-glyph></a>';
        }
        if ($siblingUrl !== null) {
            echo '<a href="' . $siblingUrl . '" class="phpair-btn phpair-btn--new" title="Создать ещё одну запись"><phpair-glyph>＋</phpair-glyph></a>';
        }
        echo '</phpair-edit-actions>';

        echo '</phpair-edit-scope>';
        echo '</phpair-edit-icons>';
    }
}

/**
 * Билдер для placeholder‑плашки (создать новую запись).
 */
final class NewIconBuilder
{
    private $enabled;

    private $type      = null;
    private $createUrl = null;
    private $label     = null;
    private $tooltip   = null;
    private $iconGlyph = null;
    private $disabled   = false;
    private $disabledReason = null;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Имя/подпись на кнопке “создать”.
     */
    public function name(string $name): self
    {
        $this->label = $name;
        return $this;
    }

    /**
     * Ссылка на создание.
     */
    public function createLink(string $url): self
    {
        $this->createUrl = $url;
        return $this;
    }

    public function placeholder(string $type, string $createUrl, ?string $label = null): self
    {
        $this->type      = $type;
        $this->createUrl = $createUrl;
        $this->label     = $label;
        return $this;
    }

    public function tooltip(string $text): self
    {
        $this->tooltip = $text;
        return $this;
    }

    public function icon(string $glyph): self
    {
        $this->iconGlyph = $glyph;
        return $this;
    }

    public function disabled(bool $on, string $reason = ''): self
    {
        $this->disabled       = $on;
        $this->disabledReason = $reason !== '' ? $reason : null;
        return $this;
    }

    public function render(): void
    {
        if (!$this->enabled) {
            return;
        }

        // Требование: скрывать NewIcon, когда редактирование выключено.
        if (!PhpAirButtons::isEditing()) {
            return;
        }
        if ($this->createUrl === null) {
            return;
        }

        $createUrl = htmlspecialchars($this->createUrl, ENT_QUOTES, 'UTF-8');
        $label     = $this->label !== null
            ? htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8')
            : ($this->type !== null
                ? 'Create ' . htmlspecialchars($this->type, ENT_QUOTES, 'UTF-8')
                : 'Create');
        $tooltip   = $this->tooltip !== null ? htmlspecialchars($this->tooltip, ENT_QUOTES, 'UTF-8') : null;
        $glyph     = $this->iconGlyph !== null ? htmlspecialchars($this->iconGlyph, ENT_QUOTES, 'UTF-8') : '＋';

        $disabledClass = $this->disabled ? ' phpair-button-row--disabled' : '';
        $titleParts    = [];
        if ($tooltip !== null) {
            $titleParts[] = $tooltip;
        }
        if ($this->disabled && $this->disabledReason !== null) {
            $titleParts[] = $this->disabledReason;
        }
        $titleAttr = '';
        if ($titleParts !== []) {
            $titleAttr = ' title="' . htmlspecialchars(implode(' — ', $titleParts), ENT_QUOTES, 'UTF-8') . '"';
        }

        echo '<phpair-new-icon class="phpair-edit-root">';
        echo '<a href="' . $createUrl . '" class="phpair-button-row phpair-button-row--newicon' . $disabledClass . '"' . $titleAttr . '>';
        echo '<phpair-tile class="phpair-btn phpair-btn--new" aria-hidden="true"><phpair-glyph>' . $glyph . '</phpair-glyph></phpair-tile>';
        echo '<phpair-caption class="phpair-btn-label">' . $label . '</phpair-caption>';
        echo '</a>';
        echo '</phpair-new-icon>';
    }
}


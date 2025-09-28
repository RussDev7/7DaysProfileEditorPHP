<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','ja'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + 365*24*60*60, '/');
}

function detect_browser_lang() {
    $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (stripos($al, 'ja') === 0) return 'ja';
    return 'en';
}

function get_locale() {
    if (!empty($_SESSION['lang'])) return $_SESSION['lang'];
    if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['en','ja'], true)) {
        $_SESSION['lang'] = $_COOKIE['lang'];
        return $_SESSION['lang'];
    }
    $_SESSION['lang'] = detect_browser_lang();
    return $_SESSION['lang'];
}

function load_i18n() {
    static $i18n = null;
    if ($i18n !== null) return $i18n;

    $locale = get_locale();
    $path = __DIR__ . '/lang/' . $locale . '.php';
    if (!file_exists($path)) {
        $path = __DIR__ . '/lang/en.php';
    }
    $i18n = require $path;
    $i18n['_fallback_locale'] = ($locale === 'en') ? 'ja' : 'en';
    return $i18n;
}

function t($key, array $vars = [], bool $escape = true) {
    $i18n = load_i18n();
    $text = $i18n[$key] ?? null;

    if ($text === null) {
        $fallbackPath = __DIR__ . '/lang/' . $i18n['_fallback_locale'] . '.php';
        if (file_exists($fallbackPath)) {
            $fallback = require $fallbackPath;
            $text = $fallback[$key] ?? null;
        }
    }
    if ($text === null) {
        $text = $key;
    }

    if (!empty($vars)) {
        $text = strtr($text, $vars);
    }
    return $escape ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : $text;
}

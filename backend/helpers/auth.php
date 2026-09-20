<?php
require_once __DIR__ . '/session.php';
if (!function_exists('uknBaseUrl')) {
    function uknBaseUrl(): string
    {
        if (defined('UKN_BASE_URL')) {
            return rtrim((string) UKN_BASE_URL, '/');
        }
        $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));
        $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            return rtrim(substr($appRoot, strlen($docRoot)), '/');
        }
        return '';
    }
}
if (!function_exists('uknRouteUrl')) {
    function uknRouteUrl(string $route): string
    {
        return uknBaseUrl() . '/index.php?page=' . rawurlencode($route);
    }
}
if (!function_exists('uknRedirectToRoute')) {
    function uknRedirectToRoute(string $route): void
    {
        if (!headers_sent()) {
            header('Location: ' . uknRouteUrl($route), true, 302);
        }
        exit;
    }
}
if (!function_exists('requireLogin')) {
    function requireLogin(): void
    {
        if (isLoggedIn()) {
            return;
        }
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = (string) $_SERVER['REQUEST_URI'];
        }
        uknRedirectToRoute('login');
    }
}
if (!function_exists('redirectIfLoggedIn')) {

    function redirectIfLoggedIn(): void
    {
        if (!isLoggedIn()) {
            return;
        }
        uknRedirectToRoute(
            getActiveRole() === 'mentor' ? 'mentor-dashboard' : 'learner-dashboard'
        );
    }
}
if (!function_exists('requireAdmin')) {
    function requireAdmin(): void
    {
        if (!isLoggedIn()) {
            if (!empty($_SERVER['REQUEST_URI'])) {
                $_SESSION['redirect_after_login'] = (string) $_SERVER['REQUEST_URI'];
            }
            uknRedirectToRoute('login');
        }
        if (!isCurrentUserAdmin()) {
            uknRedirectToRoute('403');
        }
    }
}

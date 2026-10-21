<?php
declare(strict_types=1);

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    start_session();
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return $sent !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function csrf_check(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>Platnost formuláře vypršela</title>'
            . '<p style="font-family:sans-serif;padding:40px">Platnost formuláře vypršela. Vraťte se zpět, obnovte stránku a zkuste to znovu.</p>';
        exit;
    }
}

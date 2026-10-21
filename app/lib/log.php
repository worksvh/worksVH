<?php
declare(strict_types=1);

function log_write(string $channel, string $msg): void
{
    $dir = STORAGE_PATH . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents("$dir/$channel-" . date('Y-m') . '.log', '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND | LOCK_EX);
}

function log_error(string $msg): void
{
    log_write('app', 'ERROR ' . $msg);
}

function log_info(string $msg): void
{
    log_write('app', 'INFO ' . $msg);
}

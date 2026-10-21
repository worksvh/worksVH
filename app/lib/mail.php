<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function smtp_config(): array
{
    $s = setting('mail');
    $env = config('smtp');
    return [
        'host' => trim((string) $s['smtp_host']) ?: $env['host'],
        'port' => (int) ($s['smtp_port'] ?: $env['port'] ?: 587),
        'secure' => (string) $s['smtp_secure'],
        'user' => trim((string) $s['smtp_user']) ?: $env['user'],
        'pass' => (string) $s['smtp_pass'] !== '' ? (string) $s['smtp_pass'] : $env['pass'],
        'from' => trim((string) $s['smtp_from']) ?: $env['from'],
    ];
}

/**
 * Pošle textový e-mail přes SMTP (PHPMailer). Když SMTP není nastavené nebo PHPMailer chybí,
 * celý e-mail se uloží do storage/logs/mail/ (lokální vývoj). Vrací true při úspěchu.
 */
function send_mail(string $to, string $subject, string $text, array $attachments = [], ?string $replyTo = null, string $replyName = ''): bool
{
    $cfg = smtp_config();
    $fromName = (string) (setting('company')['name'] ?? 'Web');
    $host = (string) parse_url((string) config('app_url'), PHP_URL_HOST);
    $from = $cfg['from'] !== '' ? $cfg['from'] : 'web@' . (str_contains($host, '.') ? preg_replace('/^www\./', '', $host) : 'localhost.localdomain');

    if (!class_exists(PHPMailer::class)) {
        return mail_to_log($to, $subject, $text, $attachments, 'PHPMailer není nainstalovaný (composer install)');
    }
    $m = new PHPMailer(true);
    try {
        $m->CharSet = PHPMailer::CHARSET_UTF8;
        $m->setFrom($from, $fromName);
        $m->addAddress($to);
        if ($replyTo) {
            $m->addReplyTo($replyTo, $replyName);
        }
        $m->Subject = $subject;
        $m->Body = $text;
        foreach ($attachments as $a) {
            $m->addAttachment($a['path'], $a['name']);
        }
        if ($cfg['host'] === '') {
            $m->preSend();
            return mail_to_log($to, $subject, $m->getSentMIMEMessage(), [], 'SMTP není nastavené', true);
        }
        $m->isSMTP();
        $m->Host = $cfg['host'];
        $m->Port = $cfg['port'];
        $m->SMTPAuth = $cfg['user'] !== '';
        $m->Username = $cfg['user'];
        $m->Password = $cfg['pass'];
        $m->SMTPSecure = $cfg['secure'] !== '' ? $cfg['secure'] : ($cfg['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS);
        $m->Timeout = 15;
        $m->send();
        log_write('mail', "OK -> $to | $subject");
        return true;
    } catch (Throwable $e) {
        log_error("E-mail pro $to se nepodařilo odeslat: " . $m->ErrorInfo . ' ' . $e->getMessage());
        return false;
    }
}

function mail_to_log(string $to, string $subject, string $body, array $attachments, string $reason, bool $isMime = false): bool
{
    $dir = STORAGE_PATH . '/logs/mail';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $file = $dir . '/' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . ($isMime ? '.eml' : '.txt');
    $content = $isMime ? $body : "To: $to\nSubject: $subject\n\n$body\n\nPřílohy: " . implode(', ', array_column($attachments, 'name'));
    file_put_contents($file, $content);
    log_write('mail', "LOG ($reason) -> $to | $subject | " . basename($file));
    return true;
}

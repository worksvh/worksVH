<?php
declare(strict_types=1);

const INQUIRY_STATUSES = ['new' => 'Nová', 'progress' => 'Vyřizuje se', 'done' => 'Hotovo'];

function ip_hash(string $ip): string
{
    return hash_hmac('sha256', $ip, app_key());
}

function inquiry_rate_exceeded(string $ipHash): bool
{
    return (int) db_val('SELECT COUNT(*) FROM inquiries WHERE ip_hash = ? AND created_at > ?',
        [$ipHash, date('Y-m-d H:i:s', time() - 3600)]) >= INQ_MAX_PER_HOUR;
}

function inquiry_dir(int $id): string
{
    return STORAGE_PATH . "/inquiries/$id";
}

/** Uloží poptávku a přílohy. Vrací id. */
function inquiry_store(array $v, array $files, string $ipHash): int
{
    db()->beginTransaction();
    try {
        db_exec('INSERT INTO inquiries (created_at, company, name, email, phone, quantity, deadline, message, status, note, ip_hash, consent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'new\', \'\', ?, ?)',
            [now(), $v['company'], $v['name'], $v['email'], $v['phone'], $v['quantity'], $v['deadline'], $v['message'], $ipHash, now()]);
        $id = (int) db()->lastInsertId();
        if ($files) {
            $dir = inquiry_dir($id);
            mkdir($dir, 0770, true);
            foreach ($files as $f) {
                $stored = bin2hex(random_bytes(16)) . '.' . $f['ext'];
                if (!move_uploaded_file($f['tmp_name'], "$dir/$stored")) {
                    throw new RuntimeException('Přílohu se nepodařilo uložit');
                }
                db_exec('INSERT INTO inquiry_files (inquiry_id, stored_name, original_name, mime, size) VALUES (?, ?, ?, ?, ?)',
                    [$id, $stored, mb_substr($f['name'], 0, 200), $f['mime'], $f['size']]);
            }
        }
        db()->commit();
        return $id;
    } catch (Throwable $e) {
        db()->rollBack();
        if (isset($id)) {
            inquiry_delete_files($id);
        }
        throw $e;
    }
}

function inquiry_files(int $id): array
{
    return db_all('SELECT * FROM inquiry_files WHERE inquiry_id = ? ORDER BY id', [$id]);
}

function inquiry_delete_files(int $id): void
{
    $dir = inquiry_dir($id);
    if (is_dir($dir)) {
        foreach (glob("$dir/*") ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
    db_exec('DELETE FROM inquiry_files WHERE inquiry_id = ?', [$id]);
}

/** Anonymizuje poptávky starší než lhůta z Nastavení a smaže jejich přílohy. Vrací počet. */
function inquiries_anonymize_old(): int
{
    $months = max(1, (int) (setting('mail')['retention_months'] ?? 24));
    $cutoff = date('Y-m-d H:i:s', strtotime("-$months months"));
    $ids = array_column(db_all('SELECT id FROM inquiries WHERE created_at < ? AND anonymized_at IS NULL', [$cutoff]), 'id');
    foreach ($ids as $id) {
        inquiry_delete_files((int) $id);
        db_exec("UPDATE inquiries SET company = 'anonymizováno', name = 'anonymizováno', email = '', phone = '', message = 'Obsah poptávky byl po uplynutí lhůty anonymizován.',
            note = '', ip_hash = '', anonymized_at = ? WHERE id = ?", [now(), $id]);
    }
    if ($ids) {
        log_info('Anonymizováno poptávek: ' . count($ids));
    }
    return count($ids);
}

/** Odešle e-mail firmě a potvrzení zákazníkovi. Chyby jen loguje. */
function inquiry_send_mails(int $id): void
{
    $q = db_one('SELECT * FROM inquiries WHERE id = ?', [$id]);
    $files = inquiry_files($id);
    $mail = setting('mail');
    $company = setting('company');
    $to = trim((string) $mail['inquiry_to']) ?: (string) config('inquiry_to');

    $lines = [
        'Firma' => $q['company'], 'Jméno' => $q['name'], 'E-mail' => $q['email'], 'Telefon' => $q['phone'],
        'Počet kusů' => $q['quantity'], 'Potřebný termín' => $q['deadline'],
    ];
    $text = "Nová poptávka z webu (č. {$q['id']}, " . format_date($q['created_at']) . ")\n\n";
    foreach ($lines as $k => $v) {
        $text .= str_pad($k . ':', 18) . ($v !== '' ? $v : '–') . "\n";
    }
    $text .= "\nPopis:\n{$q['message']}\n\n";
    $total = array_sum(array_column($files, 'size'));
    $attach = [];
    if ($files) {
        $text .= "Přílohy:\n";
        foreach ($files as $f) {
            $text .= ' - ' . $f['original_name'] . ' (' . format_bytes((int) $f['size']) . ")\n";
            $attach[] = ['path' => inquiry_dir($id) . '/' . $f['stored_name'], 'name' => $f['original_name']];
        }
        if ($total > INQ_MAIL_ATTACH_BYTES) {
            $attach = [];
            $text .= "(Přílohy jsou dohromady větší než 10 MB, stáhněte je v administraci.)\n";
        }
    }
    $text .= "\nDetail v administraci: " . config('app_url') . "/admin/poptavky/$id\n";

    $subject = str_replace('{firma}', $q['company'], (string) $mail['company_subject']);
    if ($to === '' || has_placeholder($to)) {
        log_error("Poptávka $id: není nastavený e-mail pro poptávky (Nastavení › E-mail a SMTP).");
    } else {
        send_mail($to, $subject, $text, $attach, $q['email'], $q['name']);
    }

    $body = str_replace(['{jmeno}', '{telefon}'], [$q['name'], (string) $company['phone']], (string) $mail['customer_body']);
    send_mail($q['email'], (string) $mail['customer_subject'], $body, [], ($to !== '' && !has_placeholder($to)) ? $to : null);
}

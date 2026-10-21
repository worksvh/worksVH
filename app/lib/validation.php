<?php
declare(strict_types=1);

/* Pravidla poptávkového formuláře (sdílí je server i JS přes data-atributy). */

const INQ_MAX_FILES = 5;
const INQ_MAX_FILE_BYTES = 20 * 1024 * 1024;
const INQ_MAX_TOTAL_BYTES = 50 * 1024 * 1024;
const INQ_MAIL_ATTACH_BYTES = 10 * 1024 * 1024;
const INQ_MIN_SECONDS = 3;
const INQ_MAX_PER_HOUR = 5;

/** Povolené přípony → povolené MIME typy (CAD formáty finfo často hlásí jako text/plain nebo octet-stream). */
const INQ_FILE_TYPES = [
    'pdf' => ['application/pdf'],
    'step' => ['text/plain', 'application/octet-stream', 'model/step', 'application/step', 'application/x-step'],
    'stp' => ['text/plain', 'application/octet-stream', 'model/step', 'application/step', 'application/x-step'],
    'igs' => ['text/plain', 'application/octet-stream', 'model/iges', 'application/iges', 'application/x-iges'],
    'iges' => ['text/plain', 'application/octet-stream', 'model/iges', 'application/iges', 'application/x-iges'],
    'dwg' => ['image/vnd.dwg', 'image/x-dwg', 'application/acad', 'application/x-acad', 'application/autocad_dwg', 'application/dwg', 'application/x-dwg', 'application/octet-stream'],
    'dxf' => ['text/plain', 'image/vnd.dxf', 'image/x-dxf', 'application/dxf', 'application/x-dxf', 'application/octet-stream'],
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'zip' => ['application/zip', 'application/x-zip-compressed', 'application/x-zip'],
];

function inq_msg(string $key, array $vars = []): string
{
    $msg = (string) (setting('form_msgs')[$key] ?? $key);
    foreach ($vars as $k => $v) {
        $msg = str_replace('{' . $k . '}', (string) $v, $msg);
    }
    return $msg;
}

/** Převede $_FILES['soubory'] (multiple) na seznam souborů. */
function inq_files_from_request(string $field = 'soubory'): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !is_array($f['name'])) {
        return [];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if ((int) $f['error'][$i] === UPLOAD_ERR_NO_FILE || $name === '') {
            continue;
        }
        $out[] = ['name' => basename((string) $name), 'tmp_name' => $f['tmp_name'][$i], 'size' => (int) $f['size'][$i], 'error' => (int) $f['error'][$i]];
    }
    return $out;
}

/** Ověří přílohy. Vrací [soubory s doplněným ext a mime, chyba|null]. */
function inq_validate_files(array $files): array
{
    if (count($files) > INQ_MAX_FILES) {
        return [[], inq_msg('files_count', ['max' => INQ_MAX_FILES])];
    }
    $total = 0;
    $allowed = strtoupper(implode(', ', array_keys(INQ_FILE_TYPES)));
    foreach ($files as &$file) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return [[], inq_msg('files_size', ['soubor' => $file['name'], 'max' => format_bytes(INQ_MAX_FILE_BYTES)])];
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            return [[], inq_msg('files_upload', ['soubor' => $file['name']])];
        }
        if ($file['size'] > INQ_MAX_FILE_BYTES) {
            return [[], inq_msg('files_size', ['soubor' => $file['name'], 'max' => format_bytes(INQ_MAX_FILE_BYTES)])];
        }
        $total += $file['size'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = detect_mime($file['tmp_name']);
        if (!isset(INQ_FILE_TYPES[$ext]) || !in_array($mime, INQ_FILE_TYPES[$ext], true)) {
            return [[], inq_msg('files_type', ['soubor' => $file['name'], 'povolene' => $allowed])];
        }
        $file['ext'] = $ext;
        $file['mime'] = $mime;
    }
    unset($file);
    if ($total > INQ_MAX_TOTAL_BYTES) {
        return [[], inq_msg('files_total', ['max' => format_bytes(INQ_MAX_TOTAL_BYTES)])];
    }
    return [$files, null];
}

/** Ověří textová pole poptávky. Vrací [hodnoty, chyby]. */
function inq_validate_fields(array $in): array
{
    $v = [];
    $limits = ['company' => 200, 'name' => 200, 'email' => 200, 'phone' => 50, 'quantity' => 100, 'deadline' => 100, 'message' => 10000];
    foreach ($limits as $k => $max) {
        $s = trim(str_replace("\r\n", "\n", (string) (is_scalar($in[$k] ?? null) ? $in[$k] : '')));
        $v[$k] = mb_substr($s, 0, $max);
    }
    $v['consent'] = !empty($in['consent']);
    $err = [];
    foreach (['company', 'name', 'email', 'message'] as $k) {
        if ($v[$k] === '') {
            $err[$k] = inq_msg('required');
        }
    }
    if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $err['email'] = inq_msg('email');
    }
    if (!$v['consent']) {
        $err['consent'] = inq_msg('consent');
    }
    return [$v, $err];
}

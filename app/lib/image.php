<?php
declare(strict_types=1);

const IMAGE_VARIANT_WIDTHS = [480, 960, 1600];

function media_get(int $id): ?array
{
    static $cache = [];
    if (!array_key_exists($id, $cache)) {
        $row = db_one('SELECT * FROM media WHERE id = ?', [$id]);
        if ($row) {
            $row['variants'] = json_decode($row['variants'], true) ?: [];
        }
        $cache[$id] = $row;
    }
    return $cache[$id];
}

function media_url(string $filename): string
{
    return '/media/' . $filename;
}

/**
 * Vygeneruje WebP varianty (480, 960, 1600 px). Bez GD vrátí [] a zapíše to do logu.
 * Vrací [[w, h, filename], …].
 */
function image_make_variants(string $path, string $mime, string $baseName): array
{
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagewebp')) {
        log_info('GD s podporou WebP chybí, ukládám jen originál: ' . basename($path));
        return [];
    }
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => @imagecreatefromwebp($path),
        default => false,
    };
    if (!$src) {
        log_error('Obrázek se nepodařilo načíst pro varianty: ' . basename($path));
        return [];
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($path);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle !== 0 && ($rot = imagerotate($src, $angle, 0))) {
            $src = $rot;
        }
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $out = [];
    $widths = array_values(array_filter(IMAGE_VARIANT_WIDTHS, static fn($vw) => $vw < $w));
    $widths[] = min($w, 1600); // největší varianta = originál (max. 1600 px)
    foreach (array_unique($widths) as $vw) {
        $vh = (int) round($h * $vw / $w);
        $dst = imagecreatetruecolor($vw, $vh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $vw, $vh, $w, $h);
        $file = "$baseName-$vw.webp";
        if (imagewebp($dst, PUBLIC_PATH . '/media/' . $file, 82)) {
            $out[] = [$vw, $vh, $file];
        }
    }
    usort($out, static fn($a, $b) => $a[0] <=> $b[0]);
    return $out;
}

/** Rozměry obrázku (u JPEG s ohledem na otočení z EXIF). */
function image_dimensions(string $path, string $mime): array
{
    if ($mime === 'image/svg+xml') {
        $svg = (string) file_get_contents($path);
        if (preg_match('/viewBox\s*=\s*"[\d.\-]+[\s,]+[\d.\-]+[\s,]+([\d.]+)[\s,]+([\d.]+)"/i', $svg, $m)) {
            return [(int) round((float) $m[1]), (int) round((float) $m[2])];
        }
        return [null, null];
    }
    $info = @getimagesize($path);
    if (!$info) {
        return [null, null];
    }
    [$w, $h] = $info;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        if (in_array($o, [5, 6, 7, 8], true)) {
            [$w, $h] = [$h, $w];
        }
    }
    return [$w, $h];
}

/**
 * <img> pro hodnotu pole typu image. Když obrázek chybí, vrátí šedé zástupné místo
 * s textem z alt (popis toho, co má být na fotce).
 * $opts: sizes, class, lazy (bool), placeholder_class
 */
function img(array $value, array $opts = []): string
{
    $m = !empty($value['media']) ? media_get((int) $value['media']) : null;
    $alt = (string) ($value['alt'] ?? '');
    $class = $opts['class'] ?? '';
    if (!$m) {
        $label = ph($alt);
        return '<div class="photo-ph ' . e($class) . '" role="img" aria-label="' . e($label !== '' ? $label : 'Místo pro fotografii') . '"><span>' . e($label) . '</span></div>';
    }
    $lazy = $opts['lazy'] ?? true;
    $attrs = ['alt' => ph($alt !== '' ? $alt : $m['alt'])];
    if ($m['variants']) {
        $largest = end($m['variants']);
        $attrs['src'] = media_url($largest[2]);
        $attrs['srcset'] = implode(', ', array_map(static fn($v) => media_url($v[2]) . ' ' . $v[0] . 'w', $m['variants']));
        $attrs['sizes'] = $opts['sizes'] ?? '100vw';
        $attrs['width'] = $largest[0];
        $attrs['height'] = $largest[1];
    } else {
        $attrs['src'] = media_url($m['filename']);
        if ($m['width']) {
            $attrs['width'] = $m['width'];
            $attrs['height'] = $m['height'];
        }
    }
    if ($lazy) {
        $attrs['loading'] = 'lazy';
    } else {
        $attrs['fetchpriority'] = 'high';
    }
    $attrs['decoding'] = 'async';
    if ($class !== '') {
        $attrs['class'] = $class;
    }
    $html = '<img';
    foreach ($attrs as $k => $v) {
        $html .= ' ' . $k . '="' . e($v) . '"';
    }
    return $html . '>';
}

/** Absolutní URL obrázku (pro og:image). */
function image_abs_url(array $value): ?string
{
    $m = !empty($value['media']) ? media_get((int) $value['media']) : null;
    if (!$m) {
        return null;
    }
    $file = $m['variants'] ? end($m['variants'])[2] : $m['filename'];
    return config('app_url') . media_url($file);
}

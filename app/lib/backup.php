<?php
declare(strict_types=1);

/** Vytvoří ZIP s databází a složkou media/ (bez příloh poptávek). Vrací cestu k souboru. */
function backup_create(string $targetDir): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('Na serveru chybí rozšíření PHP zip.');
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0770, true);
    }
    $stamp = date('Y-m-d-His');
    $dbCopy = "$targetDir/db-$stamp.sqlite";
    // konzistentní kopie i za běhu
    if (db_is_turso()) {
        db()->exportTo($dbCopy);
    } else {
        db()->exec('VACUUM INTO ' . db()->quote($dbCopy));
    }
    $zipPath = "$targetDir/vhworks-zaloha-$stamp.zip";
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('ZIP se nepodařilo vytvořit.');
    }
    $zip->addFile($dbCopy, 'storage/database.sqlite');
    foreach (glob(PUBLIC_PATH . '/media/*') ?: [] as $f) {
        if (is_file($f) && basename($f) !== '.htaccess') {
            $zip->addFile($f, 'public/media/' . basename($f));
        }
    }
    $zip->addFromString('README.txt', "Záloha webu VH Works z " . date('j. n. Y H:i') . ".\n"
        . "Obnova: storage/database.sqlite a public/media/ nahrajte na stejná místa na serveru.\n"
        . "Přílohy poptávek záloha záměrně neobsahuje.\n");
    $zip->close();
    @unlink($dbCopy);
    return $zipPath;
}

/** Ponechá posledních $keep záloh ve složce. */
function backup_prune(string $dir, int $keep = 14): void
{
    $files = glob("$dir/vhworks-zaloha-*.zip") ?: [];
    rsort($files);
    foreach (array_slice($files, $keep) as $f) {
        @unlink($f);
    }
}

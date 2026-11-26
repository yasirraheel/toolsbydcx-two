<?php
$sourceDir = realpath(__DIR__ . '/../toolsbydcx-social-extension');
$baseDir   = realpath(__DIR__ . '/..');
$zipFile   = $baseDir . '/toolsbydcx-ext.zip';

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot open $zipFile\n");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);
        $zipPath = str_replace('\\', '/', $relativePath);
        $zip->addFile($filePath, $zipPath);
    }
}
$zip->close();
echo "Packed $zipFile (" . filesize($zipFile) . " bytes)\n";

$dests = [
    $baseDir . '/core/storage/app/public/extension/toolsbydcx-ext.zip',
    $baseDir . '/download/toolsbydcx-ext.zip',
    $baseDir . '/public/toolsbydcx-ext.zip',
];

foreach ($dests as $dest) {
    if (file_exists(dirname($dest))) {
        copy($zipFile, $dest);
        echo "Copied to $dest\n";
    }
}

<?php
$sourceDir = realpath(__DIR__ . '/../toolsbydcx-social-extension');
$baseDir   = realpath(__DIR__ . '/..');
$zipFile   = $baseDir . '/toolsbydcx-ext.zip';

$manifestPath = $sourceDir . '/manifest.json';
$manifest = json_decode(file_get_contents($manifestPath), true);
$version = preg_replace('/[^0-9.]/', '', $manifest['version'] ?? '2.8.16') ?: '2.8.16';
$versionedName = 'toolsbydcx-ext-v' . $version . '.zip';

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
echo "Packed $zipFile (" . filesize($zipFile) . " bytes) v$version\n";

$storageDir = $baseDir . '/core/storage/app/public/extension';
$dests = [
    $storageDir . '/toolsbydcx-ext.zip',
    $storageDir . '/' . $versionedName,
    $baseDir . '/download/toolsbydcx-ext.zip',
    $baseDir . '/public/toolsbydcx-ext.zip',
];

foreach ($dests as $dest) {
    if (file_exists(dirname($dest))) {
        copy($zipFile, $dest);
        echo "Copied to $dest\n";
    }
}

if (in_array('--sync-db', $argv ?? [], true)) {
    require __DIR__ . '/vendor/autoload.php';
    $app = require __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $finalPath = $storageDir . '/' . $versionedName;
    $fileSize = file_exists($finalPath) ? round(filesize($finalPath) / 1024, 1) . ' KB' : '0 KB';

    \App\Models\ExtensionHistory::where('type', 'social')->update(['is_current' => false]);
    \App\Models\ExtensionHistory::create([
        'version'    => $version,
        'filename'   => $versionedName,
        'file_path'  => 'storage/extension/' . $versionedName,
        'file_size'  => $fileSize,
        'type'       => 'social',
        'is_current' => true,
    ]);

    $general = gs();
    if ($general) {
        $general->min_extension_version = $version;
        $general->save();
    }

    echo "Synced ExtensionHistory and min_extension_version to v$version ($fileSize)\n";
}

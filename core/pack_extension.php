<?php
$src = realpath(__DIR__ . '/../toolsbydcx-extension');
$targets = [
    realpath(__DIR__ . '/..') . '/toolsbydcx-extension.zip',
    realpath(__DIR__ . '/storage/app/public/extension') . '/toolsbydcx-flow-v1.0.3.zip',
    realpath(__DIR__ . '/storage/app/public/extension') . '/toolsbydcx-flow.zip',
    realpath(__DIR__ . '/../public') . '/toolsbydcx-extension.zip'
];

foreach ($targets as $target) {
    if (file_exists($target)) {
        unlink($target);
    } else {
        @mkdir(dirname($target), 0755, true);
    }
    $zip = new ZipArchive();
    if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo "Failed to create: {$target}\n";
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relPath = substr($filePath, strlen($src) + 1);
            $relPath = str_replace('\\', '/', $relPath);
            $zip->addFile($filePath, $relPath);
        }
    }
    $zip->close();
    echo "Successfully created {$target}\n";
}

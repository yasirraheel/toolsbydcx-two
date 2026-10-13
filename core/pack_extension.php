<?php
// Packaging script for both ToolsByDcx Social and ToolsByDcx Flow extensions
// Ensures standard Unix forward-slash paths (no Windows backslashes) for clean Chromium extraction.

function packExtensionDirectory(string $srcDir, array $targetPaths, string $label): void {
    if (!is_dir($srcDir)) {
        echo "[ERROR] Source directory does not exist for {$label}: {$srcDir}\n";
        return;
    }

    foreach ($targetPaths as $target) {
        if (file_exists($target)) {
            @unlink($target);
        } else {
            @mkdir(dirname($target), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            echo "[ERROR] Failed to create {$target}\n";
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relPath = substr($filePath, strlen($srcDir) + 1);
                $relPath = str_replace('\\', '/', $relPath);
                $zip->addFile($filePath, $relPath);
            }
        }

        $zip->close();
        echo "[SUCCESS] {$label} -> {$target}\n";
    }
}

$rootDir = realpath(__DIR__ . '/..');
$storageExtDir = realpath(__DIR__ . '/storage/app/public/extension') ?: ($rootDir . '/core/storage/app/public/extension');

// 1. ToolsByDcx Social Extension
$socialSrc = realpath($rootDir . '/toolsbydcx-social-extension');
$socialTargets = [
    $rootDir . '/toolsbydcx-ext.zip',
    $storageExtDir . '/toolsbydcx-ext.zip',
    $storageExtDir . '/toolsbydcx-ext-v2.3.3.zip',
    $rootDir . '/download/toolsbydcx-ext.zip',
    $rootDir . '/public/toolsbydcx-ext.zip'
];
packExtensionDirectory($socialSrc, $socialTargets, 'ToolsByDcx Social');

// 2. ToolsByDcx Flow Extension
$flowSrc = realpath($rootDir . '/toolsbydcx-flow-extension');
$flowTargets = [
    $rootDir . '/toolsbydcx-flow.zip',
    $rootDir . '/toolsbydcx-extension.zip',
    $storageExtDir . '/toolsbydcx-flow-v1.0.3.zip',
    $storageExtDir . '/toolsbydcx-flow.zip',
    $rootDir . '/download/toolsbydcx-flow.zip',
    $rootDir . '/public/toolsbydcx-extension.zip',
    $rootDir . '/public/toolsbydcx-flow.zip'
];
packExtensionDirectory($flowSrc, $flowTargets, 'ToolsByDcx Flow');

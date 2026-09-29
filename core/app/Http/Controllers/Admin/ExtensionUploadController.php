<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtensionHistory;
use Illuminate\Http\Request;

class ExtensionUploadController extends Controller
{
    public function index()
    {
        $pageTitle = 'Extension Distribution & Versioning';
        
        $directory = storage_path('app/public/extension');
        $extensionExists = false;
        $lastModified = 'Never';
        if (is_dir($directory)) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    $extensionExists = true;
                    $lastModified = date('F d Y, H:i:s', filemtime($directory . '/' . $file));
                    break;
                }
            }
        }
        
        $downloadUrl = getExtensionDownloadUrl();
        $minVersion = gs('min_extension_version') ?: '1.0.0';
        if (!preg_match('/^\d+(\.\d+)*$/', $minVersion)) {
            $minVersion = '1.0.0';
        }
        $forceUpdate = gs('force_extension_update') ? 1 : 0;
        
        $histories = [];
        try {
            $histories = ExtensionHistory::orderByDesc('id')->get();
        } catch (\Throwable $e) {}

        return view('admin.extension.upload', compact('pageTitle', 'downloadUrl', 'extensionExists', 'lastModified', 'minVersion', 'forceUpdate', 'histories'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'extension_zip'         => 'nullable|file|mimes:zip',
            'min_extension_version' => 'nullable|string',
        ]);

        $detectedVersion = null;

        if ($request->hasFile('extension_zip')) {
            $file = $request->file('extension_zip');
            
            // Define directories
            $directory = storage_path('app/public/extension');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Temp move for inspection
            $tempName = 'temp_' . time() . '.zip';
            $tempPath = $directory . '/' . $tempName;
            $file->move($directory, $tempName);

            // Read version from manifest.json inside zip
            $zip = new \ZipArchive();
            if ($zip->open($tempPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (preg_match('#(^|/)manifest\.json$#i', $name)) {
                        $manifestContent = $zip->getFromIndex($i);
                        $manifestData = json_decode($manifestContent, true);
                        if (!empty($manifestData['version'])) {
                            $detectedVersion = trim($manifestData['version']);
                        }
                        break;
                    }
                }
                $zip->close();
            }

            $version = $detectedVersion ?: ($request->min_extension_version ?: '1.0.0');
            $cleanVersion = preg_replace('/[^0-9.]/', '', $version) ?: '1.0.0';

            // Proper clean filename with version included
            $finalFilename = 'toolsbydcx-flow-v' . $cleanVersion . '.zip';
            $finalPath = $directory . '/' . $finalFilename;

            // Delete existing zip files in storage
            if (is_dir($directory)) {
                foreach (scandir($directory) as $item) {
                    if (pathinfo($item, PATHINFO_EXTENSION) === 'zip' && $item !== $tempName) {
                        @unlink($directory . '/' . $item);
                    }
                }
            }

            rename($tempPath, $finalPath);

            // Auto-flatten ZIP so manifest.json is at root (never folder inside folder)
            $this->flattenExtensionZip($finalPath);

            // Copy to public download destinations so links never break
            try {
                $downloadDir = public_path('download');
                if (!file_exists($downloadDir)) mkdir($downloadDir, 0755, true);
                copy($finalPath, $downloadDir . '/extension.zip');
                copy($finalPath, public_path('toolsbydcx-extension.zip'));
            } catch (\Throwable $e) {}

            $fileSize = file_exists($finalPath) ? round(filesize($finalPath) / 1024, 1) . ' KB' : '0 KB';

            // Maintain Version History automatically in DB
            try {
                ExtensionHistory::where('type', 'flow')->update(['is_current' => false]);
                ExtensionHistory::create([
                    'version'    => $cleanVersion,
                    'filename'   => $finalFilename,
                    'file_path'  => 'storage/extension/' . $finalFilename,
                    'file_size'  => $fileSize,
                    'type'       => 'flow',
                    'is_current' => true,
                ]);
            } catch (\Throwable $e) {}

            $requestMinVersion = $cleanVersion;
        } else {
            $requestMinVersion = $request->min_extension_version ?: '1.0.0';
        }

        $general = gs();
        $general->min_extension_version = $requestMinVersion;
        $general->force_extension_update = $request->force_extension_update ? 1 : 0;
        $general->save();

        $notify[] = ['success', 'Extension distribution settings updated and version history recorded successfully!'];
        return back()->withNotify($notify);
    }

    private function flattenExtensionZip($zipPath)
    {
        if (!file_exists($zipPath) || !class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        $manifestPath = null;
        $prefix = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if ($name === 'manifest.json') {
                $manifestPath = $name;
                $prefix = '';
                break;
            } elseif (preg_match('#(^|/)(manifest\\.json)$#i', $name)) {
                $manifestPath = $name;
                $prefix = substr($name, 0, strlen($name) - strlen('manifest.json'));
                break;
            }
        }

        $tempZipPath = $zipPath . '.clean.tmp.zip';
        $newZip = new \ZipArchive();
        if ($newZip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $zip->close();
            return false;
        }

        $createdDirs = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));

            if (strpos($name, '__MACOSX/') === 0 || basename($name) === '.DS_Store' || basename($name) === 'Thumbs.db') {
                continue;
            }

            if ($prefix === '' || strpos($name, $prefix) === 0) {
                $relName = $prefix !== '' ? substr($name, strlen($prefix)) : $name;
                $relName = ltrim($relName, '/');
                if ($relName === '' || $relName === false) {
                    continue;
                }

                if (substr($relName, -1) === '/') {
                    $dirPath = rtrim($relName, '/');
                    if (!isset($createdDirs[$dirPath])) {
                        $newZip->addEmptyDir($dirPath);
                        $createdDirs[$dirPath] = true;
                    }
                } else {
                    // Ensure all parent directories exist explicitly in the zip
                    $dir = dirname($relName);
                    if ($dir !== '.' && $dir !== '' && !isset($createdDirs[$dir])) {
                        $parts = explode('/', $dir);
                        $acc = '';
                        foreach ($parts as $p) {
                            $acc = $acc === '' ? $p : $acc . '/' . $p;
                            if (!isset($createdDirs[$acc])) {
                                $newZip->addEmptyDir($acc);
                                $createdDirs[$acc] = true;
                            }
                        }
                    }

                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        $newZip->addFromString($relName, $content);
                    }
                }
            }
        }

        $zip->close();
        $newZip->close();

        if (file_exists($tempZipPath)) {
            @unlink($zipPath);
            rename($tempZipPath, $zipPath);
        }

        return true;
    }
}

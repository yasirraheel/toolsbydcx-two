<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtensionHistory;
use Illuminate\Http\Request;

class ExtensionUploadController extends Controller
{
    public function index()
    {
        $pageTitle = 'Extension Distribution & Management';
        
        $directory = storage_path('app/public/extension');
        
        // Social Extension status
        $socialHistory = ExtensionHistory::where('type', 'social')->where('is_current', true)->first()
                      ?: ExtensionHistory::where('type', 'social')->orderByDesc('id')->first();
        $socialExists = file_exists($directory . '/toolsbydcx-ext.zip') 
                     || ($socialHistory && file_exists($directory . '/' . $socialHistory->filename))
                     || count(glob($directory . '/toolsbydcx-ext*.zip') ?: []) > 0;
        $socialLastModified = 'Never';
        if ($socialExists) {
            $socialFile = file_exists($directory . '/toolsbydcx-ext.zip') ? $directory . '/toolsbydcx-ext.zip' : ($socialHistory ? $directory . '/' . $socialHistory->filename : (glob($directory . '/toolsbydcx-ext*.zip')[0] ?? null));
            if ($socialFile && file_exists($socialFile)) {
                $socialLastModified = date('F d Y, H:i:s', filemtime($socialFile));
            }
        }
        $socialDownloadUrl = route('extension.download', ['filename' => 'toolsbydcx-ext.zip']);
        $socialVersion = $socialHistory ? $socialHistory->version : '2.3.3';

        // Flow Extension status
        $flowHistory = ExtensionHistory::where('type', 'flow')->where('is_current', true)->first()
                    ?: ExtensionHistory::where('type', 'flow')->orderByDesc('id')->first();
        $flowExists = file_exists(public_path('toolsbydcx-extension.zip'))
                   || ($flowHistory && file_exists($directory . '/' . $flowHistory->filename))
                   || count(glob($directory . '/toolsbydcx-flow*.zip') ?: []) > 0;
        $flowLastModified = 'Never';
        if ($flowExists) {
            $flowFile = ($flowHistory && file_exists($directory . '/' . $flowHistory->filename)) ? $directory . '/' . $flowHistory->filename : (glob($directory . '/toolsbydcx-flow*.zip')[0] ?? public_path('toolsbydcx-extension.zip'));
            if ($flowFile && file_exists($flowFile)) {
                $flowLastModified = date('F d Y, H:i:s', filemtime($flowFile));
            }
        }
        $flowDownloadUrl = route('extension.download', ['filename' => 'toolsbydcx-flow.zip']);
        $flowVersion = $flowHistory ? $flowHistory->version : '1.0.3';

        $minVersion = gs('min_extension_version') ?: '1.0.0';
        if (!preg_match('/^\d+(\.\d+)*$/', $minVersion)) {
            $minVersion = '1.0.0';
        }
        $forceUpdate = gs('force_extension_update') ? 1 : 0;
        
        $histories = [];
        try {
            $histories = ExtensionHistory::orderByDesc('id')->get();
        } catch (\Throwable $e) {}

        return view('admin.extension.upload', compact(
            'pageTitle',
            'socialExists',
            'socialLastModified',
            'socialDownloadUrl',
            'socialVersion',
            'socialHistory',
            'flowExists',
            'flowLastModified',
            'flowDownloadUrl',
            'flowVersion',
            'flowHistory',
            'minVersion',
            'forceUpdate',
            'histories'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'extension_type'        => 'required|in:social,flow',
            'extension_zip'         => 'required|file|mimes:zip',
            'min_extension_version' => 'nullable|string',
        ]);

        $extType = $request->extension_type;
        $file = $request->file('extension_zip');
        $detectedVersion = null;

        // Define storage directory
        $directory = storage_path('app/public/extension');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Temp move for inspection
        $tempName = 'temp_' . time() . '_' . $extType . '.zip';
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

        $defaultVer = $extType === 'social' ? '2.3.3' : '1.0.3';
        $version = $detectedVersion ?: ($request->min_extension_version ?: $defaultVer);
        $cleanVersion = preg_replace('/[^0-9.]/', '', $version) ?: $defaultVer;

        if ($extType === 'social') {
            $finalFilename = 'toolsbydcx-ext-v' . $cleanVersion . '.zip';
            $finalPath = $directory . '/' . $finalFilename;

            // Delete ONLY older social version files, never touch flow files
            if (is_dir($directory)) {
                foreach (scandir($directory) as $item) {
                    if (str_starts_with($item, 'toolsbydcx-ext-v') && $item !== $finalFilename) {
                        @unlink($directory . '/' . $item);
                    }
                }
            }

            rename($tempPath, $finalPath);
            $this->flattenExtensionZip($finalPath);

            // Copy to canonical public filenames
            try {
                copy($finalPath, $directory . '/toolsbydcx-ext.zip');
                $downloadDir = public_path('download');
                if (!file_exists($downloadDir)) mkdir($downloadDir, 0755, true);
                copy($finalPath, $downloadDir . '/toolsbydcx-ext.zip');
                copy($finalPath, public_path('toolsbydcx-ext.zip'));
            } catch (\Throwable $e) {}

            $fileSize = file_exists($finalPath) ? round(filesize($finalPath) / 1024, 1) . ' KB' : '0 KB';

            try {
                ExtensionHistory::where('type', 'social')->update(['is_current' => false]);
                ExtensionHistory::create([
                    'version'    => $cleanVersion,
                    'filename'   => $finalFilename,
                    'file_path'  => 'storage/extension/' . $finalFilename,
                    'file_size'  => $fileSize,
                    'type'       => 'social',
                    'is_current' => true,
                ]);
            } catch (\Throwable $e) {}

            $notifyMsg = 'ToolsByDcx Social (Cookie) Extension v' . $cleanVersion . ' uploaded successfully!';
        } else {
            // Google Flow Extension
            $finalFilename = 'toolsbydcx-flow-v' . $cleanVersion . '.zip';
            $finalPath = $directory . '/' . $finalFilename;

            // Delete ONLY older flow version files, never touch social files
            if (is_dir($directory)) {
                foreach (scandir($directory) as $item) {
                    if (str_starts_with($item, 'toolsbydcx-flow-v') && $item !== $finalFilename) {
                        @unlink($directory . '/' . $item);
                    }
                }
            }

            rename($tempPath, $finalPath);
            $this->flattenExtensionZip($finalPath);

            // Copy to canonical public filenames
            try {
                copy($finalPath, $directory . '/toolsbydcx-flow.zip');
                $downloadDir = public_path('download');
                if (!file_exists($downloadDir)) mkdir($downloadDir, 0755, true);
                copy($finalPath, $downloadDir . '/extension.zip');
                copy($finalPath, public_path('toolsbydcx-extension.zip'));
            } catch (\Throwable $e) {}

            $fileSize = file_exists($finalPath) ? round(filesize($finalPath) / 1024, 1) . ' KB' : '0 KB';

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

            $notifyMsg = 'ToolsByDcx Flow Extension v' . $cleanVersion . ' uploaded successfully!';
        }

        $general = gs();
        $general->min_extension_version = $cleanVersion;
        $general->force_extension_update = $request->force_extension_update ? 1 : 0;
        $general->save();

        $notify[] = ['success', $notifyMsg];
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

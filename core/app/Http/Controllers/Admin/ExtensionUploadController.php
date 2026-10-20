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
        $socialVersion = $socialHistory ? $socialHistory->version : '2.3.4';

        // Flow Extension status
        $flowHistory = ExtensionHistory::where('type', 'flow')->where('is_current', true)->first()
                    ?: ExtensionHistory::where('type', 'flow')->orderByDesc('id')->first();
        $flowExists = file_exists(public_path('toolsbydcx-flow.zip'))
                   || ($flowHistory && file_exists($directory . '/' . $flowHistory->filename))
                   || count(glob($directory . '/toolsbydcx-flow*.zip') ?: []) > 0;
        $flowLastModified = 'Never';
        if ($flowExists) {
            $flowFile = ($flowHistory && file_exists($directory . '/' . $flowHistory->filename)) ? $directory . '/' . $flowHistory->filename : (glob($directory . '/toolsbydcx-flow*.zip')[0] ?? public_path('toolsbydcx-flow.zip'));
            if ($flowFile && file_exists($flowFile)) {
                $flowLastModified = date('F d Y, H:i:s', filemtime($flowFile));
            }
        }
        $flowDownloadUrl = route('extension.download', ['filename' => 'toolsbydcx-flow.zip']);
        $flowVersion = $flowHistory ? $flowHistory->version : '1.0.3';

        // Kiwi Browser APK/XAPK status
        $kiwiHistory = ExtensionHistory::where('type', 'kiwi')->where('is_current', true)->first()
                    ?: ExtensionHistory::where('type', 'kiwi')->orderByDesc('id')->first();
        $kiwiExists = ($kiwiHistory && file_exists($directory . '/' . $kiwiHistory->filename))
                   || file_exists($directory . '/kiwi-browser.apk')
                   || file_exists($directory . '/kiwi-browser.xapk');
        $kiwiLastModified = 'Never';
        if ($kiwiExists) {
            $kiwiFile = $kiwiHistory && file_exists($directory . '/' . $kiwiHistory->filename)
                ? $directory . '/' . $kiwiHistory->filename
                : (file_exists($directory . '/kiwi-browser.apk') ? $directory . '/kiwi-browser.apk' : $directory . '/kiwi-browser.xapk');
            $kiwiLastModified = date('F d Y, H:i:s', filemtime($kiwiFile));
        }
        $kiwiDownloadUrl = route('extension.download', ['filename' => 'kiwi-browser.apk', 'type' => 'kiwi']);
        $kiwiVersion = $kiwiHistory ? $kiwiHistory->version : 'Not uploaded';

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
            'kiwiExists',
            'kiwiLastModified',
            'kiwiDownloadUrl',
            'kiwiVersion',
            'kiwiHistory',
            'minVersion',
            'forceUpdate',
            'histories'
        ));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'extension_type'        => 'required|in:social,flow,kiwi',
            'extension_zip'         => 'required|file|max:524288',
            'min_extension_version' => 'nullable|string',
        ]);

        $extType = $request->extension_type;
        $file = $request->file('extension_zip');
        $originalExtension = strtolower($file->getClientOriginalExtension());
        if ($extType === 'kiwi' && !in_array($originalExtension, ['apk', 'xapk'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extension_zip' => 'Kiwi Browser must be an APK or XAPK file.',
            ]);
        }
        if ($extType !== 'kiwi' && $originalExtension !== 'zip') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'extension_zip' => 'Extensions must be uploaded as ZIP files.',
            ]);
        }
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

        $defaultVer = $extType === 'social' ? '2.3.4' : ($extType === 'flow' ? '1.0.3' : '1.0.0');
        $version = $detectedVersion ?: ($request->min_extension_version ?: $defaultVer);
        $cleanVersion = preg_replace('/[^0-9.]/', '', $version) ?: $defaultVer;

        if ($extType === 'kiwi') {
            $notifyMsg = $this->storeKiwiRelease($tempPath, $originalExtension, $cleanVersion, $directory);
        } elseif ($extType === 'social') {
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

            $notifyMsg = 'ToolsByDcx Social Extension v' . $cleanVersion . ' uploaded successfully!';
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
                copy($finalPath, $downloadDir . '/toolsbydcx-flow.zip');
                copy($finalPath, public_path('toolsbydcx-flow.zip'));
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

        if ($extType !== 'kiwi') {
            $general = gs();
            $general->min_extension_version = $cleanVersion;
            $general->force_extension_update = $request->force_extension_update ? 1 : 0;
            $general->save();
        }

        $notify[] = ['success', $notifyMsg];
        return back()->withNotify($notify);
    }

    private function storeKiwiRelease(string $sourcePath, string $extension, string $version, string $directory): string
    {
        $finalFilename = 'kiwi-browser-v' . $version . '.' . $extension;
        $finalPath = $directory . '/' . $finalFilename;

        foreach (scandir($directory) ?: [] as $item) {
            if (str_starts_with($item, 'kiwi-browser-v') && $item !== $finalFilename) {
                @unlink($directory . '/' . $item);
            }
        }

        @unlink($finalPath);
        if (!rename($sourcePath, $finalPath)) {
            throw new \RuntimeException('Could not save the assembled Kiwi Browser file.');
        }

        foreach (['apk', 'xapk'] as $oldExtension) {
            @unlink($directory . '/kiwi-browser.' . $oldExtension);
        }
        copy($finalPath, $directory . '/kiwi-browser.' . $extension);

        $fileSize = round(filesize($finalPath) / 1024, 1) . ' KB';
        ExtensionHistory::where('type', 'kiwi')->update(['is_current' => false]);
        ExtensionHistory::create([
            'version'    => $version,
            'filename'   => $finalFilename,
            'file_path'  => 'storage/extension/' . $finalFilename,
            'file_size'  => $fileSize,
            'type'       => 'kiwi',
            'is_current' => true,
        ]);

        return 'Kiwi Browser ' . strtoupper($extension) . ' v' . $version . ' uploaded successfully!';
    }

    /**
     * Receive one small Kiwi Browser chunk. Keeping each request below the
     * PHP/web-server upload limit allows APKs around 250 MB to upload safely.
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'extension_type' => 'required|in:kiwi',
            // Keep the payload near 1 MB so the endpoint also works with
            // conservative hosting defaults such as upload_max_filesize=2M.
            'chunk'         => 'required|file|max:2048',
            'upload_id'     => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,80}$/'],
            'chunk_index'   => 'required|integer|min:0|max:9999',
            'total_chunks'  => 'required|integer|min:1|max:10000',
            'original_name' => 'required|string|max:255',
        ]);

        $extension = strtolower(pathinfo($request->input('original_name'), PATHINFO_EXTENSION));
        if (!in_array($extension, ['apk', 'xapk'], true)) {
            return response()->json(['message' => 'Kiwi Browser must be an APK or XAPK file.'], 422);
        }

        $chunkDirectory = storage_path('app/tmp/kiwi-chunks/' . $request->input('upload_id'));
        if (!is_dir($chunkDirectory)) {
            mkdir($chunkDirectory, 0755, true);
        }

        $chunkName = sprintf('%06d.part', (int) $request->input('chunk_index'));
        $request->file('chunk')->move($chunkDirectory, $chunkName);

        return response()->json([
            'ok' => true,
            'uploaded' => count(glob($chunkDirectory . '/*.part') ?: []),
            'totalChunks' => (int) $request->input('total_chunks'),
        ]);
    }

    /** Assemble a completed Kiwi upload and pass it through the normal release flow. */
    public function completeChunkUpload(Request $request)
    {
        $request->validate([
            'extension_type'        => 'required|in:kiwi',
            'upload_id'             => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{16,80}$/'],
            'total_chunks'          => 'required|integer|min:1|max:10000',
            'original_name'         => 'required|string|max:255',
            'min_extension_version' => 'nullable|string|max:50',
        ]);

        $extension = strtolower(pathinfo($request->input('original_name'), PATHINFO_EXTENSION));
        if (!in_array($extension, ['apk', 'xapk'], true)) {
            return response()->json(['message' => 'Kiwi Browser must be an APK or XAPK file.'], 422);
        }

        $chunkDirectory = storage_path('app/tmp/kiwi-chunks/' . $request->input('upload_id'));
        $totalChunks = (int) $request->input('total_chunks');
        $assembledPath = $chunkDirectory . '/assembled.' . $extension;
        if (!is_dir($chunkDirectory)) {
            return response()->json(['message' => 'Upload session was not found. Please start again.'], 422);
        }

        $output = @fopen($assembledPath, 'wb');
        if (!$output) {
            return response()->json(['message' => 'Could not prepare the server upload.'], 500);
        }

        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $partPath = $chunkDirectory . '/' . sprintf('%06d.part', $index);
                if (!is_file($partPath)) {
                    throw new \RuntimeException('Missing upload chunk ' . ($index + 1) . ' of ' . $totalChunks . '.');
                }
                $input = fopen($partPath, 'rb');
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
            fclose($output);

            $version = preg_replace('/[^0-9.]/', '', (string) $request->input('min_extension_version')) ?: '1.0.0';
            $this->storeKiwiRelease($assembledPath, $extension, $version, storage_path('app/public/extension'));
        } catch (\Throwable $e) {
            if (is_resource($output)) {
                fclose($output);
            }
            return response()->json(['message' => $e->getMessage()], 422);
        } finally {
            foreach (glob($chunkDirectory . '/*') ?: [] as $part) {
                @unlink($part);
            }
            @rmdir($chunkDirectory);
        }

        return response()->json(['ok' => true, 'message' => 'Kiwi Browser uploaded successfully.']);
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

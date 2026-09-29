<?php
// Build the extension's original layered brand icon and distribution archive.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
foreach ([16, 32, 48, 128] as $size) {
    $im = imagecreatetruecolor(512, 512);
    $purple = imagecolorallocate($im, 124, 58, 237);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $purple);
    imagesetthickness($im, 25);
    imagepolygon($im, [256,90,90,173,256,256,422,173], $white);
    imageline($im,90,256,256,339,$white); imageline($im,256,339,422,256,$white);
    imageline($im,90,339,256,422,$white); imageline($im,256,422,422,339,$white);
    $small = imagecreatetruecolor($size,$size);
    imagecopyresampled($small,$im,0,0,0,0,$size,$size,512,512);
    imagepng($small,"$root/toolsbydcx-extension/icons/icon$size.png");
    imagedestroy($small); imagedestroy($im);
}
if (!is_dir("$root/download")) mkdir("$root/download",0755,true);
$zip = new ZipArchive();
if ($zip->open("$root/download/extension.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Cannot create ZIP');
$base = "$root/toolsbydcx-extension";
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file->isFile()) continue;
    $relative = str_replace('\\','/',substr($file->getPathname(),strlen($base)+1));
    $zip->addFile($file->getPathname(),$relative);
}
$zip->close();
echo "Built extension.zip: ".hash_file('sha256',"$root/download/extension.zip").PHP_EOL;

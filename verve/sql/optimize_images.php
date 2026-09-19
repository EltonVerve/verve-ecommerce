<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!function_exists('imagewebp')) { fwrite(STDERR, "Enable GD with WebP support.\n"); exit(1); }
$root = __DIR__ . '/../public/assets/products';
if (!is_dir($root . '/optimized')) mkdir($root . '/optimized', 0755);
$sourceBytes = 0; $outputBytes = 0; $count = 0;
foreach (glob($root . '/*') as $file) {
    if (!preg_match('/\.(png|jpe?g|webp)$/i', $file)) continue;
    $info = getimagesize($file);
    if (!$info || $info[0] * $info[1] > 25000000) continue;
    $source = imagecreatefromstring(file_get_contents($file));
    if (!$source) continue;
    foreach ([600, 1600] as $width) {
        $out = $root . '/optimized/' . pathinfo($file, PATHINFO_FILENAME) . '-' . $width . '.webp';
        if (!is_file($out) || filemtime($out) < filemtime($file)) {
            $ratio = min(1, $width / $info[0]);
            $image = imagecreatetruecolor(max(1, (int) round($info[0] * $ratio)), max(1, (int) round($info[1] * $ratio)));
            imagealphablending($image, false); imagesavealpha($image, true);
            imagecopyresampled($image, $source, 0, 0, 0, 0, imagesx($image), imagesy($image), $info[0], $info[1]);
            if (!imagewebp($image, $out, 85)) throw new RuntimeException('Image conversion failed');
            imagedestroy($image);
        }
        if ($width === 1600) $outputBytes += filesize($out);
    }
    $sourceBytes += filesize($file); $count++; imagedestroy($source);
}
echo json_encode(['images' => $count, 'original_bytes' => $sourceBytes, 'large_webp_bytes' => $outputBytes]) . "\n";

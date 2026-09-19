<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../models/ProductGallery.php';
$existing = productGalleryFiles(['image' => 'a.jpg'], [['filename' => 'b.jpg'], ['filename' => 'a.jpg']]);
if ($existing !== ['a.jpg', 'b.jpg']) throw new RuntimeException('Main image or deduplication failed');
$order = orderedProductGallery($existing, [0 => 'c.jpg'], '["new:0","existing:b.jpg","existing:a.jpg"]');
if ($order !== ['c.jpg', 'b.jpg', 'a.jpg']) throw new RuntimeException('New main image ordering failed');
foreach (['["existing:other.jpg","existing:a.jpg"]', '["existing:a.jpg","existing:a.jpg"]', '[]', '{}', 'invalid'] as $bad) {
    try { orderedProductGallery($existing, [], $bad); }
    catch (RuntimeException $error) { continue; }
    throw new RuntimeException('Invalid or foreign image order accepted');
}
if (orderedProductGallery($existing, [0 => 'c.jpg'], null) !== ['a.jpg', 'b.jpg', 'c.jpg']) throw new RuntimeException('No-JavaScript fallback failed');
echo "PASS: main image, ordering, deduplication, foreign image rejection, duplicate rejection, missing image rejection, fallback.\n";
if (orderedProductGallery($existing, [], '["existing:b.jpg"]', ['a.jpg']) !== ['b.jpg']) throw new RuntimeException('Explicit removal failed');
try { orderedProductGallery($existing, [], '[]', ['other.jpg']); throw new LogicException('Foreign removal accepted'); } catch (RuntimeException $expected) {}
echo "PASS: explicit removal and foreign removal rejection.\n";

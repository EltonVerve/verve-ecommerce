<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
foreach (['CBD'=>0, ' nairobi cbd '=>0, 'Nairobi, CBD'=>0, 'Nairobi Central Business District'=>0, 'Nairobi'=>400, 'Outside Nairobi'=>850, 'Mombasa'=>850] as $name=>$expected) {
    $zone = deliveryZoneForLocation($pdo,$name);
    if (!$zone || deliveryQuote($pdo,(int) $zone['id'],100)['shipping'] !== (float) $expected) throw new RuntimeException('Incorrect fee: ' . $name);
    if (deliveryQuote($pdo,(int) $zone['id'],10000)['shipping'] !== 0.0) throw new RuntimeException('Free delivery threshold failed');
}
if (deliveryZoneForLocation($pdo,'') !== null || deliveryZoneForLocation($pdo,'123') !== null) throw new RuntimeException('Invalid location accepted');
echo "PASS: CBD free, Nairobi/outside rates, free threshold, invalid locations.\n";

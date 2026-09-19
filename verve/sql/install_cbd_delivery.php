<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
$stmt = $pdo->prepare('SELECT id FROM delivery_zones WHERE name = ?');
$stmt->execute(['Nairobi CBD']);
$id = $stmt->fetchColumn();
if ($id) $pdo->prepare('UPDATE delivery_zones SET fee=0, free_over=NULL, active=1 WHERE id=?')->execute([$id]);
else $pdo->prepare('INSERT INTO delivery_zones (name,country,fee,free_over,active) VALUES (?, ?, 0, NULL, 1)')->execute(['Nairobi CBD','Kenya']);
foreach (['Nairobi'=>400,'Outside Nairobi'=>850] as $name=>$fee) {
    $stmt->execute([$name]); $zoneId = $stmt->fetchColumn();
    if ($zoneId) $pdo->prepare('UPDATE delivery_zones SET fee=?, free_over=10000, active=1 WHERE id=?')->execute([$fee,$zoneId]);
    else $pdo->prepare('INSERT INTO delivery_zones (name,country,fee,free_over,active) VALUES (?, ?, ?, 10000, 1)')->execute([$name,'Kenya',$fee]);
}
echo "CBD free; Nairobi 400; outside Nairobi 850; free from 10000.\n";

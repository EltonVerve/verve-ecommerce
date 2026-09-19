<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $country = is_string($_POST['country'] ?? null) ? trim($_POST['country']) : '';
    $fee = filter_var($_POST['fee'] ?? '', FILTER_VALIDATE_FLOAT);
    $free = ($_POST['free_over'] ?? '') === '' ? null : filter_var($_POST['free_over'], FILTER_VALIDATE_FLOAT);
    if ($name === '' || mb_strlen($name) > 120 || $country === '' || mb_strlen($country) > 100 || $fee === false || $fee < 0 || $fee > 999999 || $free === false || ($free !== null && ($free < 0 || $free > 999999))) {
        setFlash('error', 'Enter valid area details and non-negative delivery amounts.');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) $pdo->prepare('UPDATE delivery_zones SET name=?, country=?, fee=?, free_over=?, active=? WHERE id=?')->execute([$name,$country,$fee,$free,isset($_POST['active']) ? 1 : 0,$id]);
        else $pdo->prepare('INSERT INTO delivery_zones (name,country,fee,free_over,active) VALUES (?,?,?,?,?)')->execute([$name,$country,$fee,$free,isset($_POST['active']) ? 1 : 0]);
        setFlash('success', 'Delivery area saved.');
    }
    header('Location: ' . BASE_URL . '/pages/admin/delivery.php'); exit;
}
$pageTitle = 'Delivery areas';
$zones = $pdo->query('SELECT * FROM delivery_zones ORDER BY id')->fetchAll();
$zones[] = ['id'=>0,'name'=>'','country'=>'Kenya','fee'=>'','free_over'=>'','active'=>1];
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1>Delivery areas &amp; fees</h1></div>
<p>Only active areas can be selected at checkout. Confirm actual courier costs before launch. Free delivery uses the item subtotal before discounts.</p>
<?php foreach ($zones as $zone): ?><form method="post" class="admin-card">
<h2><?= $zone['id'] ? h($zone['name']) : 'Add delivery area' ?></h2><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
<div class="form-grid">
<?php foreach (['name'=>'Area name','country'=>'Country','fee'=>'Delivery fee (KES)','free_over'=>'Free from (KES, optional)'] as $field=>$label): ?><div class="field"><label for="<?= $field . (int) $zone['id'] ?>"><?= h($label) ?></label><input id="<?= $field . (int) $zone['id'] ?>" name="<?= $field ?>" value="<?= h((string) $zone[$field]) ?>" <?= in_array($field,['fee','free_over']) ? 'type="number" min="0" max="999999" step="0.01"' : 'type="text"' ?> <?= $field !== 'free_over' ? 'required' : '' ?>></div><?php endforeach; ?>
</div><label><input type="checkbox" name="active" <?= $zone['active'] ? 'checked' : '' ?> style="width:auto"> Available at checkout</label><button class="btn btn-primary" type="submit">Save area</button>
</form><?php endforeach; ?>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>

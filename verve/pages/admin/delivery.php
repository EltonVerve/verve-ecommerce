<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $deleteZone = isset($_POST['delete_zone']);
    $id = (int) ($_POST['id'] ?? 0);
    if ($deleteZone && $id) {
        $pdo->beginTransaction();
        try {
        $stmt=$pdo->prepare('SELECT * FROM delivery_zones WHERE id=? FOR UPDATE'); $stmt->execute([$id]); $before=$stmt->fetch();
        $pdo->prepare('UPDATE delivery_zones SET active = 0 WHERE id = ?')->execute([$id]);
        auditAdmin($pdo,'delivery.deactivated','delivery_zone',$id,['before'=>$before]);
        $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        setFlash('success', 'Delivery area deactivated.');
        header('Location: ' . BASE_URL . '/pages/admin/delivery.php'); exit;
    }

    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $country = is_string($_POST['country'] ?? null) ? trim($_POST['country']) : '';
    if ($country === '') $country = 'Kenya';
    $fee = filter_var($_POST['fee'] ?? '', FILTER_VALIDATE_FLOAT);
    $free = ($_POST['free_over'] ?? '') === '' ? null : filter_var($_POST['free_over'], FILTER_VALIDATE_FLOAT);

    if ($name === '' || mb_strlen($name) > 120 || $country === '' || mb_strlen($country) > 100 || $fee === false || $fee < 0 || $fee > 999999 || $free === false || ($free !== null && ($free < 0 || $free > 999999))) {
        setFlash('error', 'Enter valid area details and non-negative delivery amounts.');
    } elseif ($free !== null && $free < $fee) {
        setFlash('error', 'Free delivery threshold must be greater than or equal to the area fee.');
    } else {
        if ($id) {
            $duplicateStmt = $pdo->prepare('SELECT id FROM delivery_zones WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) AND id != ? LIMIT 1');
            $duplicateStmt->execute([$name, $id]);
        } else {
            $duplicateStmt = $pdo->prepare('SELECT id FROM delivery_zones WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
            $duplicateStmt->execute([$name]);
        }
        if ($duplicateStmt->fetchColumn()) {
            setFlash('error', 'A delivery area with that name already exists.');
        } else {
            $pdo->beginTransaction();
            try {
            $stmt=$pdo->prepare('SELECT * FROM delivery_zones WHERE id=? FOR UPDATE'); $stmt->execute([$id]); $before=$stmt->fetch();
            if ($id) $pdo->prepare('UPDATE delivery_zones SET name=?, country=?, fee=?, free_over=?, active=? WHERE id=?')->execute([$name,$country,$fee,$free,isset($_POST['active']) ? 1 : 0,$id]);
            else $pdo->prepare('INSERT INTO delivery_zones (name,country,fee,free_over,active) VALUES (?,?,?,?,?)')->execute([$name,$country,$fee,$free,isset($_POST['active']) ? 1 : 0]);
            $savedId=$id ?: (int)$pdo->lastInsertId();
            auditAdmin($pdo,'delivery.saved','delivery_zone',$savedId,['before'=>$before,'after'=>['name'=>$name,'country'=>$country,'fee'=>$fee,'free_over'=>$free,'active'=>isset($_POST['active'])]]);
            $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
            setFlash('success', 'Delivery area saved.');
        }
    }
    header('Location: ' . BASE_URL . '/pages/admin/delivery.php'); exit;
}
$pageTitle = 'Delivery areas';
$zones = $pdo->query('SELECT * FROM delivery_zones ORDER BY id')->fetchAll();
$zones[] = ['id'=>0,'name'=>'','country'=>'Kenya','fee'=>'','free_over'=>'','active'=>1];
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar">
  <h1>Delivery areas &amp; fees</h1>
  <button type="button" id="add-delivery-zone" class="btn btn-primary btn-sm">+ Add area</button>
</div>
<p>Only active areas can be selected at checkout. Confirm actual courier costs before launch. Free delivery uses the item subtotal before discounts.</p>
<div id="delivery-zone-list">
<?php foreach ($zones as $zone): ?><form method="post" class="admin-card delivery-zone-form">
<h2><?= $zone['id'] ? h($zone['name']) : 'Add delivery area' ?><?= !$zone['active'] ? ' <span class="muted">(Inactive)</span>' : '' ?></h2><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
<div class="form-grid">
<?php foreach (['name'=>'Area name','country'=>'Country','fee'=>'Delivery fee (KES)','free_over'=>'Free from (KES, optional)'] as $field=>$label): ?><div class="field"><label for="<?= $field . (int) $zone['id'] ?>"><?= h($label) ?></label><input id="<?= $field . (int) $zone['id'] ?>" name="<?= $field ?>" value="<?= h((string) $zone[$field]) ?>" <?= in_array($field,['fee','free_over']) ? 'type="number" min="0" max="999999" step="0.01"' : 'type="text"' ?> <?= $field !== 'free_over' ? 'required' : '' ?>></div><?php endforeach; ?>
</div>
<div class="delivery-form-actions">
  <label class="delivery-toggle"><input type="checkbox" name="active" <?= $zone['active'] ? 'checked' : '' ?>> Available at checkout</label>
  <div class="delivery-button-group">
    <?php if ($zone['id']): ?><button class="btn btn-outline btn-danger delete-delivery-zone" type="button" data-delete-confirm="Deactivate this delivery area?">Remove</button><?php endif; ?>
    <button class="btn btn-primary" type="submit">Save area</button>
  </div>
</div>
</form><?php endforeach; ?>
</div>
<template id="delivery-zone-template">
  <form method="post" class="admin-card delivery-zone-form">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="0">
    <h2>Add delivery area</h2>
    <div class="form-grid">
      <?php foreach (['name'=>'Area name','country'=>'Country','fee'=>'Delivery fee (KES)','free_over'=>'Free from (KES, optional)'] as $field=>$label): ?><div class="field"><label><?= h($label) ?></label><input name="<?= $field ?>" value="<?= $field === 'country' ? 'Kenya' : '' ?>" <?= in_array($field,['fee','free_over']) ? 'type="number" min="0" max="999999" step="0.01"' : 'type="text"' ?> <?= $field !== 'free_over' ? 'required' : '' ?>></div><?php endforeach; ?>
    </div>
    <div class="delivery-form-actions">
      <label class="delivery-toggle"><input type="checkbox" name="active" checked> Available at checkout</label>
      <div class="delivery-button-group">
        <button class="btn btn-outline btn-danger delete-delivery-zone" type="button">Remove</button>
        <button class="btn btn-primary" type="submit">Save area</button>
      </div>
    </div>
  </form>
</template>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const addButton = document.getElementById('add-delivery-zone');
    const list = document.getElementById('delivery-zone-list');
    const template = document.getElementById('delivery-zone-template');
    if (!addButton || !list || !template) return;

    const attachDeleteHandlers = () => {
      document.querySelectorAll('.delete-delivery-zone').forEach((button) => {
        button.addEventListener('click', (event) => {
          const form = button.closest('form');
          if (!form) return;
          const idField = form.querySelector('input[name="id"]');
          const hasSavedId = idField && Number(idField.value) > 0;
          if (hasSavedId) {
            const message = button.dataset.deleteConfirm || 'Delete this delivery area?';
            if (!window.confirm(message)) return;
            button.setAttribute('type', 'submit');
            form.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'delete_zone', value: '1' }));
            form.submit();
            return;
          }
          form.remove();
        });
      });
    };

    addButton.addEventListener('click', () => {
      const form = template.content.firstElementChild.cloneNode(true);
      const baseId = 'delivery-zone-' + Date.now();
      form.querySelectorAll('input[name], label').forEach((node) => {
        if (!node.name) return;
        const fieldName = node.name;
        if (node.tagName === 'INPUT' && fieldName !== 'active') {
          const attrId = baseId + '-' + fieldName;
          node.id = attrId;
          const label = node.closest('.field')?.querySelector('label');
          if (label) label.setAttribute('for', attrId);
        }
      });
      list.appendChild(form);
      attachDeleteHandlers();
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    attachDeleteHandlers();
  });
</script>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>

<?php
/**
 * PRODUCT OPTIONS MODEL
 * ---------------------------------------------------------
 * Handles the "Size", "Colour", etc. variant groups that let
 * a customer pick a specific version of a product before
 * adding it to the cart. See product_option_groups +
 * product_option_values in the schema.
 * ---------------------------------------------------------
 */

// Returns every variant group for a product, with its values
// nested inside, e.g.:
// [
//   ['id'=>1, 'name'=>'Size', 'values'=>[
//        ['id'=>1,'label'=>'M','price_delta'=>0.00], ...
//   ]],
//   ...
// ]
function getOptionGroupsForProduct(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare("SELECT * FROM product_option_groups WHERE product_id = ? ORDER BY id ASC");
    $stmt->execute([$productId]);
    $groups = $stmt->fetchAll();

    foreach ($groups as &$group) {
        $valStmt = $pdo->prepare("SELECT * FROM product_option_values WHERE group_id = ? ORDER BY id ASC");
        $valStmt->execute([$group['id']]);
        $group['values'] = $valStmt->fetchAll();
    }
    unset($group);

    return $groups;
}

/**
 * ADMIN: Replaces ALL variant groups/values for a product in one
 * go. Simpler and safer than diffing "which rows changed" —
 * deletes the old groups (cascades to their values) then inserts
 * fresh ones from the form.
 *
 * $groups looks like:
 * [
 *   ['name' => 'Size', 'values' => [
 *       ['label' => 'S', 'price_delta' => 0],
 *       ['label' => 'L', 'price_delta' => 3],
 *   ]],
 *   ...
 * ]
 */
function replaceOptionGroups(PDO $pdo, int $productId, array $groups): void {
    $stmt = $pdo->prepare("DELETE FROM product_option_groups WHERE product_id = ?");
    $stmt->execute([$productId]);

    $groupStmt = $pdo->prepare("INSERT INTO product_option_groups (product_id, name) VALUES (?, ?)");
    $valueStmt = $pdo->prepare("INSERT INTO product_option_values (group_id, label, price_delta) VALUES (?, ?, ?)");

    foreach ($groups as $group) {
        $groupName = trim($group['name'] ?? '');
        if ($groupName === '') continue;

        $groupStmt->execute([$productId, $groupName]);
        $groupId = (int) $pdo->lastInsertId();

        foreach (($group['values'] ?? []) as $value) {
            $label = trim($value['label'] ?? '');
            if ($label === '') continue;
            $delta = (float) ($value['price_delta'] ?? 0);
            $valueStmt->execute([$groupId, $label, $delta]);
        }
    }
}

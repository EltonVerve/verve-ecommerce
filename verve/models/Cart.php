<?php
/**
 * CART MODEL
 * ---------------------------------------------------------
 * Every database query about the cart lives here. Works the
 * same way whether someone is logged in (rows tagged with
 * user_id) or browsing as a guest (rows tagged with
 * session_id) — see getCartOwner() below.
 * ---------------------------------------------------------
 */

// Figures out how to identify "whose cart is this" for the
// CURRENT visitor, and returns it as [column_name, value] so
// every other function in this file can build the right WHERE
// clause without repeating this logic.
function getCartOwner(): array {
    if (isCustomerLoggedIn()) {
        return ['user_id', $_SESSION['user_id']];
    }
    return ['session_id', getGuestSessionId()];
}

// Adds a product (with its chosen variants) to the cart.
// $selectedOptionValueIds looks like [group_id => value_id, ...]
// e.g. [1 => 3, 2 => 5] — "Size: value #3, Colour: value #5"
function addToCart(PDO $pdo, int $productId, array $selectedOptionValueIds, int $quantity): void {
    $product = getProductById($pdo, $productId);
    if (!$product || !(int) $product['is_active']) throw new RuntimeException('This product is no longer available.');
    if ($quantity < 1 || $quantity > 999) throw new RuntimeException('Choose a quantity between 1 and 999.');

    $unitPrice = (float) $product['price'];
    $optionsForDisplay = []; // e.g. ['Size' => 'M', 'Colour' => 'Black']

    foreach (getOptionGroupsForProduct($pdo, $productId) as $group) {
        if (!$group['values']) continue;
        $valueId = $selectedOptionValueIds[$group['id']] ?? null;
        $allowed = array_column($group['values'], null, 'id');
        if (!is_int($valueId) || !isset($allowed[$valueId])) {
            throw new RuntimeException('Please choose an option for ' . $group['name'] . '.');
        }
        $unitPrice += (float) $allowed[$valueId]['price_delta'];
        $optionsForDisplay[$group['name']] = $allowed[$valueId]['label'];
    }

    $optionsJson = json_encode($optionsForDisplay);
    [$ownerCol, $ownerVal] = getCartOwner();

    $count = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE product_id = ? AND $ownerCol = ?");
    $count->execute([$productId, $ownerVal]);
    if ((int) $count->fetchColumn() + $quantity > (int) $product['stock']) {
        throw new RuntimeException('Sorry, there is not enough stock for that quantity, including items already in your cart.');
    }

    // If this exact product + exact same options is already in the
    // cart, just bump the quantity instead of creating a duplicate row.
    $stmt = $pdo->prepare("
        SELECT id, quantity FROM cart_items
        WHERE product_id = ? AND $ownerCol = ? AND selected_options = ?
    ");
    $stmt->execute([$productId, $ownerVal, $optionsJson]);
    $existing = $stmt->fetch();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        if ($newQty > 999) throw new RuntimeException('A cart line can contain at most 999 units.');
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, session_id, product_id, quantity, selected_options, unit_price)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $userIdVal    = $ownerCol === 'user_id' ? $ownerVal : null;
        $sessionIdVal = $ownerCol === 'session_id' ? $ownerVal : null;
        $stmt->execute([$userIdVal, $sessionIdVal, $productId, $quantity, $optionsJson, $unitPrice]);
    }
}

// All items currently in the visitor's cart, with product name/
// image/slug attached so the cart page can display everything
// in one query.
function getCartItems(PDO $pdo, bool $lock = false): array {
    [$ownerCol, $ownerVal] = getCartOwner();
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name AS product_name, p.slug AS product_slug, p.image AS product_image,
               p.is_active, p.stock
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        WHERE ci.$ownerCol = ?
        ORDER BY ci.created_at DESC
    " . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([$ownerVal]);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        $item['options_display'] = json_decode($item['selected_options'] ?? '{}', true) ?: [];
    }
    unset($item);

    return $items;
}

// Updates the quantity of one cart line. Checks the item actually
// belongs to the current visitor first — otherwise someone could
// guess another person's cart_item id and mess with their cart.
function updateCartItemQuantity(PDO $pdo, int $cartItemId, int $quantity): void {
    [$ownerCol, $ownerVal] = getCartOwner();
    if ($quantity < 1) {
        removeCartItem($pdo, $cartItemId);
        return;
    }
    if ($quantity > 999) throw new RuntimeException('Choose a quantity of 999 or less.');
    $find = $pdo->prepare("SELECT ci.product_id, p.stock FROM cart_items ci JOIN products p ON p.id = ci.product_id WHERE ci.id = ? AND ci.$ownerCol = ?");
    $find->execute([$cartItemId, $ownerVal]);
    $item = $find->fetch();
    if (!$item) return;

    $other = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE product_id = ? AND $ownerCol = ? AND id <> ?");
    $other->execute([$item['product_id'], $ownerVal, $cartItemId]);
    if ((int) $other->fetchColumn() + $quantity > (int) $item['stock']) {
        throw new RuntimeException('That quantity exceeds the available stock.');
    }

    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND $ownerCol = ?");
    $stmt->execute([$quantity, $cartItemId, $ownerVal]);
}

function removeCartItem(PDO $pdo, int $cartItemId): void {
    [$ownerCol, $ownerVal] = getCartOwner();
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND $ownerCol = ?");
    $stmt->execute([$cartItemId, $ownerVal]);
}

// Adds up quantity × unit_price across every item in the cart.
function getCartSubtotal(PDO $pdo): float {
    $total = 0.0;
    foreach (getCartItems($pdo) as $item) {
        $total += $item['unit_price'] * $item['quantity'];
    }
    return $total;
}

// Merges a guest's cart into their account the moment they log
// in or register, so nothing they added while browsing gets lost.
function mergeGuestCartIntoUser(PDO $pdo, int $userId, string $guestSessionId): void {
    $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE session_id = ?");
    $stmt->execute([$guestSessionId]);
    $guestItems = $stmt->fetchAll();

    foreach ($guestItems as $item) {
        $existing = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? AND selected_options = ?");
        $existing->execute([$userId, $item['product_id'], $item['selected_options']]);
        $match = $existing->fetch();
        if ($match) {
            $upd = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $upd->execute([$item['quantity'], $match['id']]);
        } else {
            $upd = $pdo->prepare("UPDATE cart_items SET user_id = ?, session_id = NULL WHERE id = ?");
            $upd->execute([$userId, $item['id']]);
        }
    }
    $pdo->prepare("DELETE FROM cart_items WHERE session_id = ?")->execute([$guestSessionId]);
}

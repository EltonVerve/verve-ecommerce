<?php
/**
 * ORDER MODEL
 * ---------------------------------------------------------
 * Handles turning a cart into a permanent order record, and
 * reading back order history for the account page.
 * ---------------------------------------------------------
 */

// Flat-rate shipping, free above a threshold — set in config.php.
function calculateShippingFee(float $subtotal): float {
    global $pdo;
    $zones = deliveryZones($pdo);
    if (!$zones) return 0.0;
    return deliveryQuote($pdo, (int) $zones[0]['id'], $subtotal)['shipping'];
}

/**
 * Turns the current cart into a permanent order:
 *   1. Reads the cart items
 *   2. Copies ("snapshots") each one into order_items, so the
 *      order always shows exactly what was bought — even if a
 *      product's price or name changes later
 *   3. Reduces live stock
 *   4. Empties the cart
 * Returns the new order's ID (and a guest access token, if any).
 */
function createOrderFromCart(PDO $pdo, ?int $userId, array $address, string $email, string $paymentMethod, ?array $coupon = null): array {
    if (!$userId || !isCustomerLoggedIn() || (int) $_SESSION['user_id'] !== $userId) {
        throw new RuntimeException('Please sign in to a customer account before placing an order.');
    }
    $customer = findUserById($pdo, $userId);
    if (!$customer || $customer['role'] !== 'customer') throw new RuntimeException('A valid customer account is required.');
    if ($paymentMethod !== 'cash_on_delivery' || !preg_match('/^\+?[0-9]{9,15}$/D', $address['phone'] ?? '')) throw new RuntimeException('A valid delivery phone and cash-on-delivery payment are required.');
    $pdo->beginTransaction();
    try {
        $items = getCartItems($pdo, true);
        if (!$items) throw new RuntimeException('Your cart is empty. Add a product before checking out.');

        $subtotal = 0.0;
        $stockNeeded = [];
        foreach ($items as $item) {
            if (!(int) $item['is_active']) throw new RuntimeException($item['product_name'] . ' is no longer available. Please remove it from your cart.');
            if ((int) $item['quantity'] < 1) throw new RuntimeException('Please check the quantities in your cart.');
            $subtotal += (float) $item['unit_price'] * (int) $item['quantity'];
            $stockNeeded[$item['product_id']] = ($stockNeeded[$item['product_id']] ?? 0) + (int) $item['quantity'];
            if ($stockNeeded[$item['product_id']] > (int) $item['stock']) {
                throw new RuntimeException('There is not enough stock for ' . $item['product_name'] . '. Please update your cart.');
            }
        }
        $subtotal = round($subtotal, 2);
        $zone = deliveryQuote($pdo, (int) ($address['delivery_zone_id'] ?? 0), $subtotal);
        $shipping = $zone['shipping'];
        $address['country'] = $zone['country'];
        $address['delivery_area'] = $zone['name'];

        $discount = 0.0;
        $couponCode = null;
        if ($coupon) {
            $discount = $coupon['type'] === 'percent'
                ? round($subtotal * ((float) $coupon['value'] / 100), 2)
                : min($subtotal, (float) $coupon['value']);
            $couponCode = $coupon['code'];
        }

        $total = round(max(0, $subtotal - $discount) + $shipping, 2);
        $addressId = $userId !== null ? saveAddress($pdo, $userId, $address) : null;
        $guestToken = $userId === null ? bin2hex(random_bytes(32)) : null;

        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, address_id, contact_email, delivery_address, guest_access_hash,
                                status, subtotal, shipping_fee, discount_total, total, payment_method, coupon_code)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $addressId, $email, json_encode($address, JSON_THROW_ON_ERROR),
            $guestToken !== null ? hash('sha256', $guestToken) : null, $subtotal, $shipping, $discount, $total, $paymentMethod, $couponCode]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, quantity, selected_options, unit_price, line_total)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ? AND is_active = 1");
        foreach ($items as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['selected_options'],
                $item['unit_price'],
                $item['unit_price'] * $item['quantity'],
            ]);
            $stockStmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            if ($stockStmt->rowCount() !== 1) throw new RuntimeException('Stock changed. Please review your cart and try again.');
        }

        // Empty the cart now that everything's safely copied into the order
        [$ownerColumn, $ownerValue] = getCartOwner();
        $clearStmt = $pdo->prepare("DELETE FROM cart_items WHERE $ownerColumn = ?");
        $clearStmt->execute([$ownerValue]);

        $pdo->commit();
        return ['id' => $orderId, 'guest_token' => $guestToken];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Every order a customer has placed, newest first — for the account page.
function getOrdersForUser(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// One order and its line items — checks the order belongs to this
// user before returning anything, so no one can view another
// customer's order just by guessing the order ID in the URL.
function getOrderWithItems(PDO $pdo, int $orderId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
    if (!$order) return null;

    return hydrateOrder($pdo, $order);
}

// A guest token grants access to this order only; if the link is lost,
// access requires the private token, never an email address alone.
function getAccessibleOrder(PDO $pdo, int $orderId, ?string $token = null, ?string $email = null): ?array {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return null;
    if ($order['user_id'] !== null && isCustomerLoggedIn() && (int) $order['user_id'] === (int) $_SESSION['user_id']) {
        return hydrateOrder($pdo, $order);
    }

    $token = $token ?? ($_SESSION['guest_order_tokens'][$orderId] ?? null);
    if ($order['user_id'] !== null || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)
        || empty($order['guest_access_hash']) || !hash_equals($order['guest_access_hash'], hash('sha256', $token))) return null;
    $_SESSION['guest_order_tokens'][$orderId] = $token;
    return hydrateOrder($pdo, $order);
}

// Call only after an ownership or admin check.
function hydrateOrder(PDO $pdo, array $order): array {
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();

    foreach ($order['items'] as &$item) {
        $item['options_display'] = json_decode($item['selected_options'] ?? '{}', true) ?: [];
    }
    unset($item);

    $order['address'] = json_decode($order['delivery_address'] ?? 'null', true);
    if (!$order['address'] && !empty($order['address_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ?');
        $stmt->execute([$order['address_id']]);
        $order['address'] = $stmt->fetch() ?: null;
    }
    return $order;
}

/**
 * ---------------------------------------------------------
 * ADMIN FUNCTIONS
 * ---------------------------------------------------------
 * No user_id restriction on these — an admin is allowed to see
 * and manage every customer's orders.
 * ---------------------------------------------------------
 */

function getAllOrdersAdmin(PDO $pdo, string $statusFilter = ''): array {
    if ($statusFilter !== '') {
        $stmt = $pdo->prepare("
            SELECT o.*, COALESCE(u.full_name, JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address, '$.full_name')), 'Guest') AS customer_name,
                   COALESCE(o.contact_email, u.email, '') AS customer_email
            FROM orders o LEFT JOIN users u ON u.id = o.user_id
            WHERE o.status = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$statusFilter]);
    } else {
        $stmt = $pdo->query("
            SELECT o.*, COALESCE(u.full_name, JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address, '$.full_name')), 'Guest') AS customer_name,
                   COALESCE(o.contact_email, u.email, '') AS customer_email
            FROM orders o LEFT JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC
        ");
    }
    return $stmt->fetchAll();
}

function getOrderWithItemsAdmin(PDO $pdo, int $orderId): ?array {
    $stmt = $pdo->prepare("
        SELECT o.*, COALESCE(u.full_name, JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address, '$.full_name')), 'Guest') AS customer_name,
               COALESCE(o.contact_email, u.email, '') AS customer_email,
               COALESCE(JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address, '$.phone')), u.phone) AS customer_phone
        FROM orders o LEFT JOIN users u ON u.id = o.user_id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) return null;

    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();
    foreach ($order['items'] as &$item) {
        $item['options_display'] = json_decode($item['selected_options'] ?? '{}', true) ?: [];
    }
    unset($item);

    if (!empty($order['delivery_address'])) {
        $order['address'] = json_decode($order['delivery_address'], true);
    } elseif ($order['address_id']) {
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ?");
        $stmt->execute([$order['address_id']]);
        $order['address'] = $stmt->fetch() ?: null;
    } else {
        $order['address'] = null;
    }

    return $order;
}

// The allowed order statuses, in the order they normally happen.
function getOrderStatusOptions(): array {
    return ['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'];
}

function updateOrderStatus(PDO $pdo, int $orderId, string $status): void {
    changeOrderOperation($pdo, $orderId, 'delivery', $status, (int) ($_SESSION['user_id'] ?? 0));
}

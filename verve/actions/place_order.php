<?php
/**
 * PLACE ORDER ACTION
 * ---------------------------------------------------------
 * Validates the checkout form, turns the cart into an order,
 * and sends the shopper to their receipt page.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}

verifyCsrf();

$text = static fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';

$addressData = [];
foreach (['full_name', 'line1', 'line2', 'city', 'state', 'postal_code', 'country', 'phone'] as $field) {
    $addressData[$field] = $text($field);
}
$email = strtolower($text('email'));
$paymentMethod = $text('payment_method');
$addressData['delivery_zone_id'] = (int) ($_POST['delivery_zone_id'] ?? 0);
$addressData['phone'] = preg_replace('/[\s().-]+/', '', $addressData['phone']);

$errors = [];
if (!preg_match('/^\+?[0-9]{9,15}$/D', $addressData['phone'])) $errors[] = 'Enter a delivery phone number with 9–15 digits, including country code where needed.';
try { deliveryQuote($pdo, $addressData['delivery_zone_id'], 0); } catch (RuntimeException $error) { $errors[] = 'Please select an available delivery area.'; }
if (strlen($addressData['full_name']) < 2) $errors[] = 'Please enter your full name.';
if (strlen($addressData['line1']) < 3) $errors[] = 'Please enter your street address.';
if (strlen($addressData['city']) < 2) $errors[] = 'Please enter your city.';
if (strlen($addressData['country']) < 2) $errors[] = 'Please enter your country.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if ($paymentMethod !== 'cash_on_delivery') $errors[] = 'Please choose a payment method.';

if ($errors) {
    setFlash('error', $errors[0]);
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}

$coupon = !empty($_SESSION['coupon_code']) ? findValidCoupon($pdo, $_SESSION['coupon_code']) : null;
$userId = isCustomerLoggedIn() ? (int) $_SESSION['user_id'] : null;

try {
    $placed = createOrderFromCart($pdo, $userId, $addressData, $email, $paymentMethod, $coupon);
} catch (PDOException $error) {
    error_log('Checkout database error: ' . $error->getMessage());
    setFlash('error', 'Stock or order details changed. Please review your cart and try again.');
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
} catch (RuntimeException $error) {
    setFlash('error', $error->getMessage());
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
} catch (Throwable $error) {
    error_log('Checkout failed: ' . $error->getMessage());
    setFlash('error', 'We could not save your order. Your cart is still available; please try again.');
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}

unset($_SESSION['coupon_code']);
if ($placed['guest_token'] !== null) {
    $_SESSION['guest_order_tokens'][$placed['id']] = $placed['guest_token'];
    header('Location: ' . BASE_URL . '/pages/receipt.php?order=' . $placed['id'] . '&token=' . $placed['guest_token']);
    exit;
}

header('Location: ' . BASE_URL . '/pages/receipt.php?order=' . $placed['id']);
exit;

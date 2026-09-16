<?php
/**
 * CONTACT MESSAGE MODEL
 * ---------------------------------------------------------
 * Stores customer enquiries and supports the IP-based side of
 * the contact form rate limit. Raw IP addresses are never saved
 * — only a keyed hash of them, so we can rate-limit abuse
 * without keeping anyone's real IP address on file.
 * ---------------------------------------------------------
 */

const CONTACT_RATE_LIMIT_MAX = 3;
const CONTACT_RATE_LIMIT_WINDOW_SECONDS = 3600;
const CONTACT_IP_HASH_INSTALL_SEED = 'a91f3d6c2b8e4517f0a2d9c6b3e8517029a4d6f1c8b3e750291a4d6f0c8b3e7';
const CONTACT_IP_HASH_KEY_ENV = 'VERVE_CONTACT_IP_HASH_KEY';

function getContactEnquiryTypes(): array {
    return ['order_issue', 'product_question', 'returns', 'general'];
}

function getContactMessageStatuses(): array {
    return ['new', 'read', 'replied', 'archived'];
}

function getContactIpHashKey(): string {
    static $key = null;
    if (is_string($key)) {
        return $key;
    }

    $configuredKey = getenv(CONTACT_IP_HASH_KEY_ENV);
    if (is_string($configuredKey) && $configuredKey !== '') {
        if (strlen($configuredKey) < 32) {
            throw new RuntimeException(CONTACT_IP_HASH_KEY_ENV . ' must contain at least 32 characters.');
        }
        $key = hash('sha256', 'environment|' . $configuredKey);
        return $key;
    }

    $installationIdentity = implode('|', [
        CONTACT_IP_HASH_INSTALL_SEED,
        realpath(__DIR__) ?: __DIR__,
        php_uname('n'),
        defined('DB_HOST') ? (string) DB_HOST : '',
        defined('DB_NAME') ? (string) DB_NAME : '',
    ]);
    $key = hash('sha256', 'installation|' . $installationIdentity);

    return $key;
}

function hashContactIpAddress(string $ipAddress): string {
    $packedAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) !== false
        ? @inet_pton($ipAddress)
        : false;
    $hashInput = $packedAddress === false ? 'unknown' : bin2hex($packedAddress);

    return hash_hmac('sha256', $hashInput, getContactIpHashKey());
}

function countRecentContactMessagesByIp(PDO $pdo, string $ipHash): int {
    if (!preg_match('/^[a-f0-9]{64}$/', $ipHash)) {
        throw new InvalidArgumentException('Invalid contact IP hash.');
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM contact_messages
        WHERE ip_hash = ? AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ipHash]);
    return (int) $stmt->fetchColumn();
}

function acquireContactRateLimitLock(PDO $pdo, string $ipHash): string {
    if (!preg_match('/^[a-f0-9]{64}$/', $ipHash)) {
        throw new InvalidArgumentException('Invalid contact IP hash.');
    }
    $lockName = 'verve_contact_' . substr(hash('sha256', $ipHash), 0, 48);
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->execute([$lockName]);
    if ((int) $stmt->fetchColumn() !== 1) {
        throw new RuntimeException('Could not acquire the contact rate-limit lock.');
    }
    return $lockName;
}

function releaseContactRateLimitLock(PDO $pdo, string $lockName): bool {
    if (!preg_match('/^verve_contact_[a-f0-9]{48}$/', $lockName)) {
        throw new InvalidArgumentException('Invalid contact rate-limit lock name.');
    }
    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute([$lockName]);
    return (int) $stmt->fetchColumn() === 1;
}

function createContactMessage(PDO $pdo, array $data): int {
    if (!in_array($data['enquiry_type'] ?? '', getContactEnquiryTypes(), true)) {
        throw new InvalidArgumentException('Invalid contact enquiry type.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (user_id, enquiry_type, name, email, phone, order_reference, message, ip_hash)
        VALUES (:user_id, :enquiry_type, :name, :email, :phone, :order_reference, :message, :ip_hash)
    ");
    $stmt->execute([
        'user_id' => $data['user_id'] ?? null,
        'enquiry_type' => $data['enquiry_type'],
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
        'order_reference' => ($data['order_reference'] ?? '') !== '' ? $data['order_reference'] : null,
        'message' => $data['message'],
        'ip_hash' => $data['ip_hash'],
    ]);

    return (int) $pdo->lastInsertId();
}

function countContactMessagesAdmin(PDO $pdo, string $statusFilter = ''): int {
    if ($statusFilter !== '' && !in_array($statusFilter, getContactMessageStatuses(), true)) {
        throw new InvalidArgumentException('Invalid contact message status.');
    }
    $sql = 'SELECT COUNT(*) FROM contact_messages';
    $parameters = [];
    if ($statusFilter !== '') {
        $sql .= ' WHERE status = ?';
        $parameters[] = $statusFilter;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parameters);
    return (int) $stmt->fetchColumn();
}

function getContactMessagesAdmin(PDO $pdo, string $statusFilter = '', int $limit = 50, int $offset = 0): array {
    if ($statusFilter !== '' && !in_array($statusFilter, getContactMessageStatuses(), true)) {
        throw new InvalidArgumentException('Invalid contact message status.');
    }
    if ($limit < 1 || $limit > 100 || $offset < 0) {
        throw new InvalidArgumentException('Invalid contact message page range.');
    }

    $sql = "
        SELECT id, enquiry_type, name, email, LEFT(message, 160) AS message_preview, status, created_at
        FROM contact_messages
    ";
    $parameters = [];
    if ($statusFilter !== '') {
        $sql .= ' WHERE status = ?';
        $parameters[] = $statusFilter;
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parameters);
    return $stmt->fetchAll();
}

function findContactMessageAdmin(PDO $pdo, int $messageId): ?array {
    if ($messageId < 1) return null;
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.user_id, cm.enquiry_type, cm.name, cm.email, cm.phone, cm.order_reference,
               cm.message, cm.status, cm.created_at, cm.updated_at,
               u.full_name AS account_name, u.email AS account_email
        FROM contact_messages cm
        LEFT JOIN users u ON u.id = cm.user_id
        WHERE cm.id = ?
        LIMIT 1
    ");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    return $message ?: null;
}

function updateContactMessageStatus(PDO $pdo, int $messageId, string $status): bool {
    if ($messageId < 1 || !in_array($status, getContactMessageStatuses(), true)) return false;

    $stmt = $pdo->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
    $stmt->execute([$status, $messageId]);
    if ($stmt->rowCount() > 0) return true;

    $stmt = $pdo->prepare('SELECT 1 FROM contact_messages WHERE id = ? LIMIT 1');
    $stmt->execute([$messageId]);
    return $stmt->fetchColumn() !== false;
}

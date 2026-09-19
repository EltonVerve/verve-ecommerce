<?php
/**
 * PRODUCT MODEL
 * ---------------------------------------------------------
 * Every database query about products lives here. Pages
 * (shop.php, product.php, home.php, admin pages) call these
 * functions instead of writing their own SQL.
 *
 * Why this matters: if we ever need to change HOW products
 * are fetched (add a filter, hide out-of-stock items, etc.)
 * we change it in ONE place and every page that uses it
 * updates automatically.
 * ---------------------------------------------------------
 */

// The shop page's main product listing, with category, price-range,
// search and sort all handled in one place so shop.php stays simple.
function getShopProducts(PDO $pdo, array $filters = [], ?array &$pagination = null): array {
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($filters['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
        $like = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        $where[] = 'p.price >= ?';
        $params[] = (float) $filters['min_price'];
    }
    if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        $where[] = 'p.price <= ?';
        $params[] = (float) $filters['max_price'];
    }
    if (!empty($filters['in_stock_only'])) {
        $where[] = 'p.stock > 0';
    }

    $orderBy = match ($filters['sort'] ?? '') {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
        'rating'     => 'avg_rating DESC, p.name ASC',
        default      => 'p.created_at DESC',
    };

    $count = $pdo->prepare('SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . implode(' AND ', $where));
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / 24));
    $page = min($pages, max(1, (int) ($filters['page'] ?? 1)));
    $pagination = ['total' => $total, 'page' => $page, 'pages' => $pages];
    $offset = ($page - 1) * 24;
    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               COALESCE((SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy, p.id DESC
        LIMIT 24 OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Featured products for the homepage.
function getFeaturedProducts(PDO $pdo, int $limit = 8): array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               COALESCE((SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND p.is_featured = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Newest arrivals — used as a homepage fallback / secondary rail.
function getNewArrivals(PDO $pdo, int $limit = 8): array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Other products in the same category — "You might also like".
function getRelatedProducts(PDO $pdo, int $categoryId, int $excludeProductId, int $limit = 4): array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ?
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(2, $excludeProductId, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Small, literal-text matches for the header's live product search.
function getProductSearchSuggestions(PDO $pdo, string $query): array {
    $stmt = $pdo->prepare("
        SELECT p.name, p.slug, p.price, p.image, p.stock, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1
          AND (LOCATE(LOWER(?), LOWER(p.name)) > 0
            OR LOCATE(LOWER(?), LOWER(c.name)) > 0
            OR LOCATE(LOWER(?), LOWER(p.description)) > 0)
        ORDER BY (LOCATE(LOWER(?), LOWER(p.name)) = 1) DESC,
                 (p.stock <= 0) ASC, p.name ASC
        LIMIT 6
    ");
    $stmt->execute([$query, $query, $query, $query]);
    return $stmt->fetchAll();
}

// A single product by its URL slug, with category info attached.
function getProductBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               COALESCE((SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.slug = ? AND p.is_active = 1
    ");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();
    return $product ?: null;
}

// A single product by its numeric ID — used by cart/checkout logic.
function getProductById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

function getProductImages(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function addProductImage(PDO $pdo, int $productId, string $filename, int $sortOrder): void {
    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$productId, $filename, $sortOrder]);
}

// The lowest and highest active product prices — used to build
// the shop page's price-range filter slider/inputs.
function getPriceBounds(PDO $pdo): array {
    $row = $pdo->query("SELECT MIN(price) AS lo, MAX(price) AS hi FROM products WHERE is_active = 1")->fetch();
    return ['min' => (float) ($row['lo'] ?? 0), 'max' => (float) ($row['hi'] ?? 0)];
}

/**
 * ---------------------------------------------------------
 * ADMIN FUNCTIONS
 * ---------------------------------------------------------
 */

function getAllProductsAdmin(PDO $pdo, string $search = ''): array {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE p.name LIKE ? AND NOT EXISTS (SELECT 1 FROM product_submissions s WHERE s.product_id=p.id AND (s.status!='approved' OR s.target_id IS NOT NULL))
            ORDER BY p.created_at DESC
        ");
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $pdo->query("
            SELECT p.*, c.name AS category_name
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE NOT EXISTS (SELECT 1 FROM product_submissions s WHERE s.product_id=p.id AND (s.status!='approved' OR s.target_id IS NOT NULL))
            ORDER BY p.created_at DESC
        ");
    }
    return $stmt->fetchAll();
}

// Turns a product name into a URL-safe slug. If that slug is
// already taken, appends -2, -3, etc. until it's unique.
function generateUniqueSlug(PDO $pdo, string $name, ?int $excludeId = null): string {
    $base = slugify($name);
    if ($base === '') $base = 'product';

    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId ?? 0]);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

function createProduct(PDO $pdo, array $data): int {
    $stmt = $pdo->prepare("
        INSERT INTO products (category_id, name, slug, description, price, compare_at_price, sku, image, stock, is_featured, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['category_id'], $data['name'], $data['slug'], $data['description'],
        $data['price'], $data['compare_at_price'], $data['sku'], $data['image'],
        $data['stock'], $data['is_featured'], $data['is_active'],
    ]);
    return (int) $pdo->lastInsertId();
}

function updateProduct(PDO $pdo, int $id, array $data): void {
    $stmt = $pdo->prepare("
        UPDATE products SET
            category_id = ?, name = ?, slug = ?, description = ?, price = ?,
            compare_at_price = ?, sku = ?, image = ?, stock = ?, is_featured = ?, is_active = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['category_id'], $data['name'], $data['slug'], $data['description'], $data['price'],
        $data['compare_at_price'], $data['sku'], $data['image'], $data['stock'],
        $data['is_featured'], $data['is_active'], $id,
    ]);
}

// Deletion cascades cleanly per the schema: its option groups/
// values, images, reviews, wishlist entries and cart_items
// referencing it are removed automatically, while past
// order_items keep their frozen name/price snapshot.
function deleteProduct(PDO $pdo, int $id): void {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

function toggleProductActive(PDO $pdo, int $id, bool $isActive): void {
    $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ?");
    $stmt->execute([$isActive ? 1 : 0, $id]);
}

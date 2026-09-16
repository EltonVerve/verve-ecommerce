<?php
/**
 * CATEGORY MODEL
 * ---------------------------------------------------------
 * Every database query about categories lives here.
 * ---------------------------------------------------------
 */

function getCategoryBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch();
    return $category ?: null;
}

function getCategoryById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    return $category ?: null;
}

// How many active products sit in each category — used for the
// shop sidebar filter counts.
function getCategoriesWithProductCounts(PDO $pdo): array {
    return $pdo->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
        GROUP BY c.id
        ORDER BY c.name ASC
    ")->fetchAll();
}

/**
 * ---------------------------------------------------------
 * ADMIN FUNCTIONS
 * ---------------------------------------------------------
 */

function getAllCategoriesWithCounts(PDO $pdo): array {
    return $pdo->query("
        SELECT c.*, COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        GROUP BY c.id
        ORDER BY c.name ASC
    ")->fetchAll();
}

function createCategory(PDO $pdo, string $name, string $slug, string $description): int {
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    $stmt->execute([$name, $slug, $description]);
    return (int) $pdo->lastInsertId();
}

function updateCategory(PDO $pdo, int $id, string $name, string $slug, string $description): void {
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $slug, $description, $id]);
}

// Turns a category name into a unique slug — if it's already
// taken, appends -2, -3, etc. until it's unique.
function generateUniqueCategorySlug(PDO $pdo, string $name, ?int $excludeId = null): string {
    $base = slugify($name);
    if ($base === '') $base = 'category';

    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId ?? 0]);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

// Refuses to delete a category that still has products in it —
// the foreign key would block this anyway, but checking first
// lets us show a clear message instead of a raw SQL error.
function deleteCategory(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    if ((int) $stmt->fetch()['c'] > 0) {
        return false;
    }
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return true;
}

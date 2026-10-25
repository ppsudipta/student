<?php
/**
 * Shared helpers for coaching center locations.
 */

function centers_ensure_table(mysqli $con): void
{
    $con->query("CREATE TABLE IF NOT EXISTS `centers` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `address` text NOT NULL,
      `map_url` text DEFAULT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `is_primary` tinyint(1) DEFAULT 0,
      `sort_order` int(11) DEFAULT 0,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Fetch one center by id without requiring mysqlnd (get_result).
 */
function centers_find(mysqli $con, int $id): ?array
{
    $stmt = $con->prepare('SELECT id, name, address, map_url, phone, email, is_primary, sort_order FROM centers WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $stmt->store_result();
    if ($stmt->num_rows < 1) {
        $stmt->close();
        return null;
    }
    $row = [
        'id' => null,
        'name' => null,
        'address' => null,
        'map_url' => null,
        'phone' => null,
        'email' => null,
        'is_primary' => null,
        'sort_order' => null,
    ];
    $stmt->bind_result(
        $row['id'],
        $row['name'],
        $row['address'],
        $row['map_url'],
        $row['phone'],
        $row['email'],
        $row['is_primary'],
        $row['sort_order']
    );
    $stmt->fetch();
    $stmt->close();
    return $row;
}

/**
 * Fetch all centers ordered for admin list.
 *
 * @return array<int, array<string, mixed>>
 */
function centers_all(mysqli $con): array
{
    $rows = [];
    $sql = 'SELECT id, name, address, map_url, phone, email, is_primary, sort_order FROM centers ORDER BY is_primary DESC, sort_order ASC, id ASC';
    $res = $con->query($sql);
    if (!$res) {
        return $rows;
    }
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

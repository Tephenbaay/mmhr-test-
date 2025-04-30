<?php
require 'config.php';

$stmt = $pdo->query("SELECT admin_notes FROM settings WHERE id = 1");
$row = $stmt->fetch();

echo json_encode([
    'admin_notes' => $row['admin_notes'] ?? ''
]);

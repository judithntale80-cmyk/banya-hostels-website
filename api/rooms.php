<?php
// ===== API REQUEST AND HOSTEL VALIDATION =====
require '../config.php';
send_security_headers();
header('Content-Type: application/json');

$hostelId = (int) ($_GET['hostel'] ?? 0);
$hostelQuery = $pdo->prepare('SELECT status FROM hostels WHERE id = ?');
$hostelQuery->execute([$hostelId]);
$hostel = $hostelQuery->fetch();

if (!$hostel || $hostel['status'] !== 'open') {
    echo json_encode([
        'open' => false,
        'rooms' => [],
        'pricing' => [],
    ]);
    exit;
}

// ===== RETURN AVAILABLE ROOMS AND BOOKABLE PRICE OPTIONS =====
$roomsQuery = $pdo->prepare("
    SELECT id, room_no
    FROM rooms
    WHERE hostel_id = ? AND status = 'available'
    ORDER BY id
");
$roomsQuery->execute([$hostelId]);

$pricingQuery = $pdo->prepare("
    SELECT id, label, amount, slots, note
    FROM pricing
    WHERE hostel_id = ? AND slots <= 2
    ORDER BY amount DESC
");
$pricingQuery->execute([$hostelId]);

echo json_encode([
    'open' => true,
    'rooms' => $roomsQuery->fetchAll(),
    'pricing' => $pricingQuery->fetchAll(),
]);

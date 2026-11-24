<?php
// ===== DASHBOARD COUNTS AND RECENT BOOKINGS =====
include '_head.php';

$count = fn($sql) => $pdo->query($sql)->fetchColumn();
$summary = [
    'Pending bookings' => $count("SELECT COUNT(*) FROM bookings WHERE status = 'pending'"),
    'Available rooms' => $count("SELECT COUNT(*) FROM rooms WHERE status = 'available'"),
    'New enquiries/complaints' => $count("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"),
    'Photos' => $count('SELECT COUNT(*) FROM gallery'),
];

$recentBookings = $pdo->query("
    SELECT b.*, r.room_no, h.name AS hostel, p.amount
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN hostels h ON h.id = r.hostel_id
    JOIN pricing p ON p.id = b.pricing_id
    ORDER BY b.created DESC
    LIMIT 8
")->fetchAll();
?>
<h1 class="h3 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
    <?php foreach ($summary as $label => $value): ?>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center">
                <div class="display-6"><?= $value ?></div>
                <div><?= $label ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<h2 class="h5">Latest bookings</h2>
<div class="table-responsive">
    <table class="table table-sm bg-white">
        <tr>
            <th>Ref</th>
            <th>Student</th>
            <th>Hostel / room</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
        <?php foreach ($recentBookings as $booking): ?>
            <tr>
                <td><?= e($booking['ref']) ?></td>
                <td><?= e($booking['name']) ?></td>
                <td><?= e($booking['hostel'] . ' / ' . $booking['room_no']) ?></td>
                <td><?= number_format($booking['amount']) ?></td>
                <td><?= e($booking['status']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include '_foot.php'; ?>

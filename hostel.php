<?php
// ===== LOAD THE REQUESTED HOSTEL AND ITS ROOM DATA =====
require 'config.php';
send_security_headers();

$hostelQuery = $pdo->prepare('SELECT * FROM hostels WHERE slug = ?');
$hostelQuery->execute([$_GET['slug'] ?? '']);
$hostel = $hostelQuery->fetch();

if (!$hostel) {
    http_response_code(404);
    $title = 'Not found';
    include 'includes/header.php';
    echo '<div class="container py-5"><h1>Hostel not found</h1></div>';
    include 'includes/footer.php';
    exit;
}

$pricingQuery = $pdo->prepare("
    SELECT *
    FROM pricing
    WHERE hostel_id = ? AND slots <= 2
    ORDER BY amount DESC
");
$pricingQuery->execute([$hostel['id']]);
$pricingOptions = $pricingQuery->fetchAll();

$roomsQuery = $pdo->prepare('SELECT * FROM rooms WHERE hostel_id = ? ORDER BY id');
$roomsQuery->execute([$hostel['id']]);
$rooms = $roomsQuery->fetchAll();
$availableRooms = count(array_filter(
    $rooms,
    fn($room) => $room['status'] === 'available'
));

$title = $hostel['name'];
$desc = $hostel['tagline'] . ' - ' . mb_strimwidth($hostel['description'], 0, 110, '');

include 'includes/header.php';
render_page_hero($hostel['name']);
?>
<!-- ===== HOSTEL DETAILS, FACILITIES, AND PRICE OPTIONS ===== -->
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <?php if ($hostel['status'] === 'upcoming'): ?>
                <div class="alert alert-warning">Coming soon. This hostel is still under construction.</div>
            <?php endif; ?>

            <p class="lead"><?= e($hostel['tagline']) ?></p>
            <p><?= nl2br(e($hostel['description'])) ?></p>

            <h2 class="h4 mt-4">
                <?= $hostel['status'] === 'upcoming' ? 'Planned facilities' : 'Facilities' ?>
            </h2>
            <ul>
                <?php foreach (array_filter(array_map('trim', explode("\n", $hostel['facilities']))) as $facility): ?>
                    <li><?= e($facility) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($hostel['slug'] === 'banya-4'): ?>
                <h2 class="h4 mt-4">Wi-Fi</h2>
                <p><?= e(setting('wifi_info')) ?></p>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <?php if ($hostel['image']): ?>
                <img
                    class="img-fluid rounded mb-4"
                    src="<?= BASE_URL ?>/uploads/<?= e($hostel['image']) ?>"
                    alt="<?= e($hostel['name']) ?>"
                >
            <?php endif; ?>

            <?php if ($hostel['status'] === 'open'): ?>
                <div class="card p-3 mb-4">
                    <h2 class="h5">Accommodation charges</h2>
                    <table class="table table-sm mb-2">
                        <?php foreach ($pricingOptions as $price): ?>
                            <tr>
                                <td>
                                    <?= e($price['label']) ?><br>
                                    <small class="text-muted"><?= e($price['note']) ?></small>
                                </td>
                                <td class="text-end fw-semibold">
                                    UGX <?= number_format($price['amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <a class="btn btn-brand" href="<?= BASE_URL ?>/booking.php?hostel=<?= $hostel['id'] ?>">
                        Request a room
                    </a>
                </div>
            <?php else: ?>
                <div class="card p-3">
                    <p>Want to know when it opens?</p>
                    <a class="btn btn-brand" href="<?= BASE_URL ?>/enquires.php">Ask us</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== LIVE ROOM AVAILABILITY ===== -->
    <?php if ($hostel['status'] === 'open' && $rooms): ?>
        <h2 class="h4 mt-5">
            Room availability
            <small class="text-muted fs-6"><?= $availableRooms ?> of <?= count($rooms) ?> available</small>
        </h2>
        <p class="small">
            <span class="badge text-bg-success">Available</span>
            <span class="badge text-bg-warning">Reserved</span>
            <span class="badge text-bg-danger">Occupied</span>
        </p>
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room <?= e($room['status']) ?>"><?= e($room['room_no']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>

<?php
// ===== UPDATE, ADD, OR DELETE ROOM RECORDS =====
require '../config.php';
require_admin();

$hostelId = (int) ($_GET['hostel'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $action = $_POST['action'] ?? '';
    $hostelId = (int) ($_POST['hostel_id'] ?? 0);

    if ($action === 'status' && in_array($_POST['status'], ['available', 'reserved', 'occupied'], true)) {
        $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ?')
            ->execute([$_POST['status'], (int) $_POST['id']]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([(int) $_POST['id']]);
    } elseif ($action === 'add') {
        $prefix = trim($_POST['prefix']);
        $from = (int) $_POST['from'];
        $to = min((int) $_POST['to'], $from + 199);
        $insertRoom = $pdo->prepare('INSERT IGNORE INTO rooms(hostel_id, room_no) VALUES(?, ?)');

        for ($number = $from; $number <= $to; $number++) {
            $roomNumber = $prefix . '-' . str_pad($number, 2, '0', STR_PAD_LEFT);
            $insertRoom->execute([$hostelId, $roomNumber]);
        }
    } elseif ($action === 'bulk' && in_array($_POST['status'], ['available', 'reserved', 'occupied'], true)) {
        $pdo->prepare('UPDATE rooms SET status = ? WHERE hostel_id = ?')
            ->execute([$_POST['status'], $hostelId]);
    }

    flash('Rooms updated.');
    redirect('/admin/rooms.php?hostel=' . $hostelId);
}

// ===== LOAD ROOMS FOR THE SELECTED HOSTEL =====
$hostels = $pdo->query('SELECT id, name FROM hostels ORDER BY id')->fetchAll();
if (!$hostelId && $hostels) {
    $hostelId = $hostels[0]['id'];
}

$roomsQuery = $pdo->prepare('SELECT * FROM rooms WHERE hostel_id = ? ORDER BY id');
$roomsQuery->execute([$hostelId]);
$rooms = $roomsQuery->fetchAll();

include '_head.php';
?>
<!-- ===== ROOM CONTROLS AND AVAILABILITY TABLE ===== -->
<h1 class="h3 mb-3">Rooms &amp; availability</h1>
<p>
    <?php foreach ($hostels as $hostel): ?>
        <a
            class="btn btn-sm <?= $hostel['id'] == $hostelId ? 'btn-dark' : 'btn-outline-dark' ?>"
            href="?hostel=<?= $hostel['id'] ?>"
        >
            <?= e($hostel['name']) ?>
        </a>
    <?php endforeach; ?>
</p>

<form method="post" class="row g-2 mb-3">
    <?= csrf() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
    <div class="col-auto">
        <input class="form-control" name="prefix" placeholder="Prefix e.g. B3" required>
    </div>
    <div class="col-auto">
        <input type="number" class="form-control" name="from" placeholder="From" required>
    </div>
    <div class="col-auto">
        <input type="number" class="form-control" name="to" placeholder="To" required>
    </div>
    <div class="col-auto">
        <button class="btn btn-success">Add rooms</button>
    </div>
</form>

<form method="post" class="d-flex gap-2 mb-3">
    <?= csrf() ?>
    <input type="hidden" name="action" value="bulk">
    <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
    <select name="status" class="form-select w-auto">
        <option>available</option>
        <option>reserved</option>
        <option>occupied</option>
    </select>
    <button class="btn btn-outline-dark" data-confirm="Set ALL rooms in this hostel to this status?">
        Set all rooms
    </button>
</form>

<div class="table-responsive">
    <table class="table table-sm bg-white align-middle">
        <tr>
            <th>Room</th>
            <th>Status</th>
            <th></th>
        </tr>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= e($room['room_no']) ?></td>
                <td>
                    <form method="post" class="d-flex gap-1">
                        <?= csrf() ?>
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
                        <input type="hidden" name="id" value="<?= $room['id'] ?>">
                        <select name="status" class="form-select form-select-sm w-auto">
                            <?php foreach (['available', 'reserved', 'occupied'] as $status): ?>
                                <option <?= $room['status'] === $status ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary">Save</button>
                    </form>
                </td>
                <td>
                    <form method="post">
                        <?= csrf() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
                        <input type="hidden" name="id" value="<?= $room['id'] ?>">
                        <button
                            class="btn btn-sm btn-outline-danger"
                            data-confirm="Delete room and its bookings?"
                        >
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include '_foot.php'; ?>

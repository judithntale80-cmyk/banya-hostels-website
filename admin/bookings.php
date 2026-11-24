<?php
// ===== HANDLE BOOKING STATUS CHANGES AND DELETION =====
require '../config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        flash('Your session has expired. Refresh the page and try again.', 'danger');
        redirect('/admin/bookings.php');
    }

    $rawBookingId = $_POST['id'] ?? '';
    $bookingId = is_string($rawBookingId) || is_int($rawBookingId)
        ? filter_var($rawBookingId, FILTER_VALIDATE_INT)
        : false;
    if ($bookingId === false || $bookingId < 1) {
        flash('Choose a valid booking.', 'danger');
        redirect('/admin/bookings.php');
    }

    if (($_POST['action'] ?? '') === 'delete') {
        $pdo->beginTransaction();
        $bookingQuery = $pdo->prepare("
            SELECT b.room_id, b.status, p.slots
            FROM bookings b
            JOIN pricing p ON p.id = b.pricing_id
            WHERE b.id = ?
            FOR UPDATE
        ");
        $bookingQuery->execute([$bookingId]);
        $booking = $bookingQuery->fetch();

        if (!$booking) {
            $pdo->rollBack();
            flash('Booking not found.', 'danger');
            redirect('/admin/bookings.php');
        }

        $roomQuery = $pdo->prepare('SELECT status FROM rooms WHERE id = ? FOR UPDATE');
        $roomQuery->execute([$booking['room_id']]);
        $room = $roomQuery->fetch();

        $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$bookingId]);
        if ($booking['status'] === 'approved' && $room && $room['status'] !== 'occupied') {
            $occupancyQuery = $pdo->prepare("
                SELECT COALESCE(SUM(occupants), 0)
                FROM bookings
                WHERE room_id = ? AND status = 'approved'
            ");
            $occupancyQuery->execute([$booking['room_id']]);
            $approvedOccupants = (int) $occupancyQuery->fetchColumn();
            $capacity = (int) $booking['slots'];
            if ($capacity > 0) {
                $roomStatus = $approvedOccupants >= $capacity ? 'reserved' : 'available';
                $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ?')
                    ->execute([$roomStatus, $booking['room_id']]);
            }
        }
        $pdo->commit();
    } elseif (in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected', 'cancelled', 'completed'], true)) {
        $newStatus = $_POST['status'];
        $pdo->beginTransaction();

        $bookingQuery = $pdo->prepare("
            SELECT b.room_id, b.pricing_id, b.status, b.occupants, p.slots
            FROM bookings b
            JOIN pricing p ON p.id = b.pricing_id
            WHERE b.id = ?
            FOR UPDATE
        ");
        $bookingQuery->execute([$bookingId]);
        $booking = $bookingQuery->fetch();

        if (!$booking) {
            $pdo->rollBack();
            flash('Booking not found.', 'danger');
            redirect('/admin/bookings.php');
        }

        $roomQuery = $pdo->prepare('SELECT status FROM rooms WHERE id = ? FOR UPDATE');
        $roomQuery->execute([$booking['room_id']]);
        $room = $roomQuery->fetch();
        if (!$room) {
            $pdo->rollBack();
            flash('The room for this booking could not be found.', 'danger');
            redirect('/admin/bookings.php');
        }

        $occupancyQuery = $pdo->prepare("
            SELECT COALESCE(SUM(occupants), 0) AS occupants,
                COALESCE(SUM(CASE WHEN pricing_id <> ? THEN 1 ELSE 0 END), 0) AS different_pricing
            FROM bookings
            WHERE room_id = ? AND status = 'approved' AND id <> ?
        ");
        $occupancyQuery->execute([$booking['pricing_id'], $booking['room_id'], $bookingId]);
        $approvedUsage = $occupancyQuery->fetch();
        $approvedOccupants = (int) $approvedUsage['occupants'];

        if ($newStatus === 'approved') {
            if (
                $room['status'] === 'occupied'
                || (
                    $room['status'] === 'reserved'
                    && $booking['status'] !== 'approved'
                    && $approvedOccupants === 0
                )
            ) {
                $pdo->rollBack();
                flash('This room is no longer available for approval.', 'danger');
                redirect('/admin/bookings.php');
            }
            if ((int) $approvedUsage['different_pricing'] > 0) {
                $pdo->rollBack();
                flash('This room already has an approved booking with a different occupancy option.', 'danger');
                redirect('/admin/bookings.php');
            }
            if ($approvedOccupants + (int) $booking['occupants'] > (int) $booking['slots']) {
                $pdo->rollBack();
                flash('Approving this request would exceed the room capacity. No change was made.', 'danger');
                redirect('/admin/bookings.php');
            }

            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')
                ->execute([$newStatus, $bookingId]);
            $occupancyAfterApproval = $approvedOccupants + (int) $booking['occupants'];
            $roomStatus = $occupancyAfterApproval >= (int) $booking['slots'] ? 'reserved' : 'available';
            $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ?')
                ->execute([$roomStatus, $booking['room_id']]);
        } else {
            $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?')
                ->execute([$newStatus, $bookingId]);
            if ($booking['status'] === 'approved' && $room['status'] !== 'occupied') {
                $roomStatus = $approvedOccupants >= (int) $booking['slots'] ? 'reserved' : 'available';
                $pdo->prepare('UPDATE rooms SET status = ? WHERE id = ?')
                    ->execute([$roomStatus, $booking['room_id']]);
            }
        }

        $pdo->commit();
    } else {
        flash('Choose a valid booking action or status.', 'danger');
        redirect('/admin/bookings.php');
    }

    flash(($_POST['action'] ?? '') === 'delete' ? 'Booking deleted.' : 'Booking updated.');
    redirect('/admin/bookings.php');
}

// ===== LOAD AND DISPLAY BOOKING REQUESTS =====
$bookings = $pdo->query("
    SELECT b.*, r.room_no, h.name AS hostel, p.label, p.amount
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN hostels h ON h.id = r.hostel_id
    JOIN pricing p ON p.id = b.pricing_id
    ORDER BY FIELD(b.status, 'pending', 'approved', 'rejected', 'cancelled', 'completed'), b.created DESC
")->fetchAll();

include '_head.php';
?>
<h1 class="h3 mb-3">Booking requests</h1>
<div class="table-responsive">
    <table class="table bg-white align-middle small">
        <tr>
            <th>Ref</th>
            <th>Student</th>
            <th>Room</th>
            <th>Charge</th>
            <th>Payment ref</th>
            <th>Status</th>
            <th></th>
        </tr>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td>
                    <?= e($booking['ref']) ?><br>
                    <?= e(date('d M', strtotime($booking['created']))) ?>
                </td>
                <td>
                    <?= e($booking['name']) ?><br>
                    <?= e($booking['reg_no']) ?><br>
                    <?= e($booking['institution']) ?><br>
                    <?php if ($booking['course']): ?>
                        <?= e($booking['course']) ?><br>
                    <?php endif; ?>
                    <?= e($booking['phone']) ?> / <?= e($booking['email']) ?>
                    <br><?= (int) $booking['occupants'] ?> occupant<?= (int) $booking['occupants'] === 1 ? '' : 's' ?>
                    | Check-in: <?= $booking['check_in_date'] ? e(date('d M Y', strtotime($booking['check_in_date']))) : 'Not specified' ?>
                    | Stay: <?= $booking['stay_duration_months'] ? (int) $booking['stay_duration_months'] . ' months' : 'Not specified' ?>
                    <?php if ($booking['notes']): ?>
                        <br><em><?= e($booking['notes']) ?></em>
                    <?php endif; ?>
                </td>
                <td>
                    <?= e($booking['hostel']) ?><br>
                    <?= e($booking['room_no']) ?>
                </td>
                <td>
                    <?= e($booking['label']) ?><br>
                    UGX <?= number_format($booking['amount']) ?>
                </td>
                <td><?= e($booking['payment_ref']) ?></td>
                <td>
                    <form method="post" class="d-flex gap-1">
                        <?= csrf() ?>
                        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed'] as $status): ?>
                                <option <?= $booking['status'] === $status ? 'selected' : '' ?>>
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
                        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                        <button
                            class="btn btn-sm btn-outline-danger"
                            data-confirm="Delete booking?"
                        >
                            X
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php include '_foot.php'; ?>

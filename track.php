<?php
// ===== PAGE SETUP AND BOOKING LOOKUP =====
require 'config.php';
send_security_headers();

$title = 'Track Your Hostel Booking - Banya Hostels';
$desc = 'Check the current status of your Banya Hostels accommodation booking request using your booking reference.';
$booking = null;
$message = '';

// The booking reference and phone number are checked together before showing details.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $query = $pdo->prepare("
        SELECT b.*, r.room_no, h.name AS hostel, p.label, p.amount
        FROM bookings b
        JOIN rooms r ON r.id = b.room_id
        JOIN hostels h ON h.id = r.hostel_id
        JOIN pricing p ON p.id = b.pricing_id
        WHERE b.ref = ? AND b.phone = ?
    ");
    $query->execute([
        strtoupper(trim($_POST['ref'] ?? '')),
        trim($_POST['phone'] ?? ''),
    ]);
    $booking = $query->fetch();

    if (!$booking) {
        $message = 'No booking found. Check your reference and phone number.';
    } elseif (!empty($_POST['payment_ref'])) {
        $paymentReference = trim($_POST['payment_ref']);
        $pdo->prepare('UPDATE bookings SET payment_ref = ? WHERE id = ?')
            ->execute([$paymentReference, $booking['id']]);
        $booking['payment_ref'] = $paymentReference;
        $message = 'Payment reference saved.';
    }
}

// ===== LOOKUP FORM, STATUS, AND PAYMENT REFERENCE =====
include 'includes/header.php';
render_page_hero('Track booking');
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-6">
            <?php if ($message): ?>
                <div class="alert alert-info"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="post">
                <?= csrf() ?>

                <div class="mb-3">
                    <label class="form-label">Booking reference</label>
                    <input
                        class="form-control"
                        name="ref"
                        required
                        value="<?= e($_POST['ref'] ?? '') ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone used when booking</label>
                    <input
                        class="form-control"
                        name="phone"
                        required
                        value="<?= e($_POST['phone'] ?? '') ?>"
                    >
                </div>

                <?php if ($booking): ?>
                    <div class="mb-3">
                        <label class="form-label">Payment reference / receipt number (optional)</label>
                        <input
                            class="form-control"
                            name="payment_ref"
                            value="<?= e($booking['payment_ref']) ?>"
                        >
                    </div>
                <?php endif; ?>

                <button class="btn btn-brand">
                    <?= $booking ? 'Update' : 'Check status' ?>
                </button>
            </form>

            <?php if ($booking): ?>
                <div class="card p-3 mt-4">
                    <p class="mb-1">
                        <strong><?= e($booking['hostel']) ?>, room <?= e($booking['room_no']) ?></strong>
                    </p>
                    <p class="mb-1">
                        <?= e($booking['label']) ?> - UGX <?= number_format($booking['amount']) ?>
                    </p>
                    <p class="mb-0">
                        Status:
                        <span class="badge text-bg-<?= [
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'secondary',
                            'completed' => 'primary',
                        ][$booking['status']] ?>">
                            <?= e($booking['status']) ?>
                        </span>
                    </p>
                    <p class="mb-0 mt-2">
                        <?= (int) $booking['occupants'] ?> occupant<?= (int) $booking['occupants'] === 1 ? '' : 's' ?>
                        <?php if ($booking['check_in_date']): ?>
                            · Check-in <?= e(date('d M Y', strtotime($booking['check_in_date']))) ?>
                        <?php endif; ?>
                        <?php if ($booking['stay_duration_months']): ?>
                            · <?= (int) $booking['stay_duration_months'] ?> months
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

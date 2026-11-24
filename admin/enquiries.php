<?php
// ===== SAVE OR DELETE AN ENQUIRY =====
require '../config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    if (!csrf_ok()) {
        flash('Your form session expired. Please try again.', 'danger');
    } elseif ($id === false) {
        flash('Choose a valid enquiry.', 'danger');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $delete = $pdo->prepare('DELETE FROM enquiries WHERE id = ?');
        $delete->execute([$id]);
        flash($delete->rowCount() ? 'Enquiry deleted.' : 'Enquiry not found.', $delete->rowCount() ? 'success' : 'danger');
    } else {
        $statuses = ['new', 'in_progress', 'responded', 'resolved'];
        $status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
        $reply = isset($_POST['reply']) && is_string($_POST['reply']) ? trim($_POST['reply']) : '';

        if (!in_array($status, $statuses, true)) {
            flash('Choose a valid enquiry status.', 'danger');
        } elseif ($status === 'responded' && $reply === '') {
            flash('Record the response before marking the enquiry as responded.', 'danger');
        } else {
            $update = $pdo->prepare('UPDATE enquiries SET status = ?, reply = ? WHERE id = ?');
            $update->execute([$status, $reply, $id]);
            flash($update->rowCount() ? 'Enquiry updated.' : 'Enquiry status and response are unchanged.');
        }
    }
    redirect('/admin/enquiries.php');
}

// ===== LOAD AND DISPLAY ENQUIRIES AND COMPLAINTS =====
$enquiries = $pdo->query("
    SELECT e.*, h.name AS hostel
    FROM enquiries e
    LEFT JOIN hostels h ON h.id = e.hostel_id
    ORDER BY FIELD(e.status, 'new', 'in_progress', 'responded', 'resolved'), e.created DESC
")->fetchAll();

include '_head.php';
?>
<h1 class="h3 mb-3">Enquiries &amp; complaints</h1>
<?php foreach ($enquiries as $enquiry): ?>
    <div class="card p-3 mb-3">
        <div class="d-flex justify-content-between">
            <strong>
                <span class="badge text-bg-<?= $enquiry['type'] === 'complaint' ? 'danger' : 'info' ?>">
                    <?= e($enquiry['type']) ?>
                </span>
                <?= e($enquiry['subject'] ?: '(no subject)') ?>
            </strong>
            <small><?= e($enquiry['created']) ?></small>
        </div>

        <div class="small">
            <?php if ($enquiry['category']): ?>
                Category: <?= e($enquiry['category']) ?> |
            <?php endif; ?>
            <?= e($enquiry['name']) ?> <?= e($enquiry['reg_no']) ?> |
            <a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a> |
            <?= e($enquiry['phone']) ?> |
            <?= e($enquiry['hostel'] ?? 'General') ?>
        </div>
        <p class="mt-2"><?= nl2br(e($enquiry['body'])) ?></p>

        <form method="post">
            <?= csrf() ?>
            <input type="hidden" name="id" value="<?= $enquiry['id'] ?>">
            <textarea
                name="reply"
                class="form-control mb-2"
                rows="2"
                placeholder="Record the response sent to the resident or an internal note"
            ><?= e($enquiry['reply']) ?></textarea>

            <div class="d-flex gap-2">
                <select name="status" class="form-select w-auto">
                    <option value="new" <?= $enquiry['status'] === 'new' ? 'selected' : '' ?>>New</option>
                    <option value="in_progress" <?= $enquiry['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="responded" <?= $enquiry['status'] === 'responded' ? 'selected' : '' ?>>Responded</option>
                    <option value="resolved" <?= $enquiry['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
                <button class="btn btn-sm btn-primary">Save</button>
                <button
                    class="btn btn-sm btn-outline-danger"
                    name="action"
                    value="delete"
                    data-confirm="Delete?"
                >
                    Delete
                </button>
            </div>
        </form>
    </div>
<?php endforeach; ?>
<?php include '_foot.php'; ?>

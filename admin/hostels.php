<?php
// ===== SAVE HOSTELS AND MANAGE PRICING OPTIONS =====
require '../config.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $action = $_POST['action'] ?? '';
    $hostelId = (int) ($_POST['hostel_id'] ?? 0);

    if ($action === 'save') {
        $image = upload_image('image');
        $name = trim($_POST['name']);
        $status = in_array($_POST['status'], ['open', 'upcoming'], true)
            ? $_POST['status']
            : 'open';
        $values = [
            $name,
            trim($_POST['tagline']),
            trim($_POST['description']),
            trim($_POST['facilities']),
            $status,
        ];

        if ($hostelId) {
            $sql = 'UPDATE hostels SET name=?,tagline=?,description=?,facilities=?,status=?'
                . ($image ? ',image=?' : '')
                . ' WHERE id=?';
            if ($image) {
                $values[] = $image;
            }
            $values[] = $hostelId;
            $pdo->prepare($sql)->execute($values);
        } else {
            $pdo->prepare("
                INSERT INTO hostels (slug, name, tagline, description, facilities, status, image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                slugify($name) . '-' . substr((string) time(), -4),
                ...$values,
                $image,
            ]);
        }

        flash('Hostel saved.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM hostels WHERE id = ?')->execute([$hostelId]);
        flash('Hostel deleted.');
        $hostelId = 0;
    } elseif ($action === 'add_price') {
        $pdo->prepare("
            INSERT INTO pricing (hostel_id, label, amount, slots, note)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $hostelId,
            trim($_POST['label']),
            (int) $_POST['amount'],
            min(2, max(1, (int) $_POST['slots'])),
            trim($_POST['note']),
        ]);
        flash('Price added.');
    } elseif ($action === 'update_price') {
        $pdo->prepare("
            UPDATE pricing
            SET label = ?, amount = ?, slots = ?, note = ?
            WHERE id = ?
        ")->execute([
            trim($_POST['label']),
            (int) $_POST['amount'],
            min(2, max(1, (int) $_POST['slots'])),
            trim($_POST['note']),
            (int) $_POST['price_id'],
        ]);
        flash('Price updated.');
    } elseif ($action === 'del_price') {
        $pdo->prepare('DELETE FROM pricing WHERE id = ?')->execute([(int) $_POST['price_id']]);
        flash('Price deleted.');
    }

    redirect('/admin/hostels.php' . ($hostelId ? '?id=' . $hostelId : ''));
}

// ===== SELECT THE HOSTEL TO EDIT AND LOAD ITS PRICES =====
$hostels = $pdo->query('SELECT * FROM hostels ORDER BY id')->fetchAll();
$hostelId = (int) ($_GET['id'] ?? 0);
$hostel = null;
foreach ($hostels as $item) {
    if ($item['id'] == $hostelId) {
        $hostel = $item;
    }
}

$pricingOptions = [];
if ($hostel) {
    $pricingQuery = $pdo->prepare('SELECT * FROM pricing WHERE hostel_id = ? ORDER BY amount DESC');
    $pricingQuery->execute([$hostelId]);
    $pricingOptions = $pricingQuery->fetchAll();
}

// ===== HOSTEL DETAILS AND PRICE EDITOR =====
include '_head.php';
?>
<h1 class="h3 mb-3">Hostels &amp; prices</h1>
<p>
    <?php foreach ($hostels as $item): ?>
        <a
            class="btn btn-sm <?= $item['id'] == $hostelId ? 'btn-dark' : 'btn-outline-dark' ?>"
            href="?id=<?= $item['id'] ?>"
        >
            <?= e($item['name']) ?>
        </a>
    <?php endforeach; ?>
    <a class="btn btn-sm btn-outline-success" href="hostels.php">+ New hostel</a>
</p>

<form method="post" enctype="multipart/form-data" class="card p-3 mb-4">
    <?= csrf() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="<?= e($hostel['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="open" <?= ($hostel['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="upcoming" <?= ($hostel['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Coming soon</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Photo</label>
            <input type="file" class="form-control" name="image" accept="image/*">
        </div>
        <div class="col-12">
            <label class="form-label">Tagline</label>
            <input class="form-control" name="tagline" value="<?= e($hostel['tagline'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"><?= e($hostel['description'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Facilities (one per line)</label>
            <textarea class="form-control" name="facilities" rows="5"><?= e($hostel['facilities'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-dark">Save hostel</button>
    </div>
</form>

<?php if ($hostel): ?>
    <form method="post" class="mb-4">
        <?= csrf() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
        <button
            class="btn btn-sm btn-outline-danger"
            data-confirm="Delete hostel with all its rooms, prices and bookings?"
        >
            Delete this hostel
        </button>
    </form>

    <h2 class="h5">Accommodation charges (UGX)</h2>
    <p class="small text-muted">
        "Slots" = students per room for that option. A booking with 1 slot marks the room Reserved when approved.
    </p>

    <?php foreach ($pricingOptions as $price): ?>
        <form method="post" class="row g-2 mb-2">
            <?= csrf() ?>
            <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
            <input type="hidden" name="price_id" value="<?= $price['id'] ?>">
            <div class="col-md-4">
                <input class="form-control" name="label" value="<?= e($price['label']) ?>">
            </div>
            <div class="col-md-2">
                <input type="number" class="form-control" name="amount" value="<?= (int) $price['amount'] ?>">
            </div>
            <div class="col-md-1">
                <input
                    type="number"
                    class="form-control"
                    name="slots"
                    min="1"
                    max="2"
                    value="<?= min(2, max(1, (int) $price['slots'])) ?>"
                >
            </div>
            <div class="col-md-3">
                <input class="form-control" name="note" value="<?= e($price['note']) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary" name="action" value="update_price">Save</button>
                <button
                    class="btn btn-sm btn-outline-danger"
                    name="action"
                    value="del_price"
                    data-confirm="Delete this price?"
                >
                    X
                </button>
            </div>
        </form>
    <?php endforeach; ?>

    <form method="post" class="row g-2 mt-3">
        <?= csrf() ?>
        <input type="hidden" name="action" value="add_price">
        <input type="hidden" name="hostel_id" value="<?= $hostelId ?>">
        <div class="col-md-4">
            <input class="form-control" name="label" placeholder="Label" required>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="amount" placeholder="Amount" required>
        </div>
        <div class="col-md-1">
            <input type="number" class="form-control" name="slots" min="1" max="2" value="1">
        </div>
        <div class="col-md-3">
            <input class="form-control" name="note" placeholder="Note">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success">Add price</button>
        </div>
    </form>
<?php endif; ?>
<?php include '_foot.php'; ?>

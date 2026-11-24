<?php
require '../config.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', '/banya');
}
require_admin();

$galleryCategories = gallery_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = $_POST['id'] ?? '';
    $id = $postedId === '' || $postedId === '0'
        ? 0
        : filter_var($postedId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
    $returnUrl = $id && $id !== false ? '/admin/gallery.php?edit=' . $id : '/admin/gallery.php';

    if (!csrf_ok()) {
        flash('Your form session expired. Please try again.', 'danger');
        redirect($returnUrl);
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'delete') {
        if (!$id || $id === false) {
            flash('Choose a valid photo to delete.', 'danger');
        } else {
            $photoQuery = $pdo->prepare('SELECT image FROM gallery WHERE id = ?');
            $photoQuery->execute([$id]);
            $photo = $photoQuery->fetch();

            if (!$photo) {
                flash('Photo not found.', 'danger');
            } else {
                $delete = $pdo->prepare('DELETE FROM gallery WHERE id = ?');
                $delete->execute([$id]);
                $imagePath = __DIR__ . '/../uploads/' . basename($photo['image']);
                if (strpos($photo['image'], 'assets/banya_images/') !== 0
                    && is_file($imagePath)
                    && !unlink($imagePath)) {
                    flash('Photo removed from the gallery, but its image file could not be deleted.', 'warning');
                } else {
                    flash('Photo deleted.');
                }
            }
        }
        redirect('/admin/gallery.php');
    }

    $caption = isset($_POST['caption']) && is_string($_POST['caption'])
        ? trim($_POST['caption'])
        : '';
    $category = isset($_POST['category']) && is_string($_POST['category'])
        ? $_POST['category']
        : '';
    $hostelValue = isset($_POST['hostel_id']) && is_string($_POST['hostel_id'])
        ? $_POST['hostel_id']
        : '';
    $errors = [];

    if ($id === false) {
        $errors[] = 'Choose a valid photo to edit.';
    }
    if ($caption === '' || (function_exists('mb_strlen')
        ? mb_strlen($caption, 'UTF-8')
        : strlen($caption)) > 160) {
        $errors[] = 'Enter a photo description of 1 to 160 characters.';
    }
    if (!in_array($category, $galleryCategories, true)) {
        $errors[] = 'Choose a valid gallery category.';
    }

    $hostelId = null;
    if ($hostelValue !== '') {
        if (!ctype_digit($hostelValue) || (int) $hostelValue < 1) {
            $errors[] = 'Choose a valid hostel.';
        } else {
            $hostelId = (int) $hostelValue;
            $hostelCheck = $pdo->prepare('SELECT 1 FROM hostels WHERE id = ?');
            $hostelCheck->execute([$hostelId]);
            if (!$hostelCheck->fetchColumn()) {
                $errors[] = 'Choose a valid hostel.';
            }
        }
    }

    $existingPhoto = null;
    if ($id && $id !== false) {
        $existingQuery = $pdo->prepare('SELECT image FROM gallery WHERE id = ?');
        $existingQuery->execute([$id]);
        $existingPhoto = $existingQuery->fetch();
        if (!$existingPhoto) {
            $errors[] = 'Photo not found.';
        }
    }

    $upload = $_FILES['image'] ?? null;
    $uploadError = is_array($upload) && isset($upload['error'])
        ? (int) $upload['error']
        : UPLOAD_ERR_NO_FILE;
    if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Upload the photo as a JPG, PNG or WEBP file under 2 MB.';
    }
    if (!$existingPhoto && $uploadError === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Choose a photo to upload.';
    }

    $newImage = null;
    if (!$errors && $uploadError === UPLOAD_ERR_OK) {
        $newImage = upload_image('image');
        if ($newImage === null) {
            $errors[] = 'Upload failed. Use a JPG, PNG or WEBP image under 2 MB.';
        }
    }

    if ($errors) {
        flash(implode(' ', $errors), 'danger');
        redirect($returnUrl);
    }

    $image = $newImage ?? $existingPhoto['image'];
    try {
        if ($existingPhoto) {
            $save = $pdo->prepare("
                UPDATE gallery
                SET hostel_id = ?, category = ?, caption = ?, image = ?
                WHERE id = ?
            ");
            $save->execute([$hostelId, $category, $caption, $image, $id]);
        } else {
            $save = $pdo->prepare("
                INSERT INTO gallery (hostel_id, category, caption, image)
                VALUES (?, ?, ?, ?)
            ");
            $save->execute([$hostelId, $category, $caption, $image]);
        }
    } catch (PDOException $exception) {
        if ($newImage !== null) {
            $newImagePath = __DIR__ . '/../uploads/' . basename($newImage);
            if (is_file($newImagePath) && !unlink($newImagePath)) {
                error_log('Unable to remove a gallery upload after a database error.');
            }
        }
        throw $exception;
    }

    if ($newImage !== null && $existingPhoto
        && strpos($existingPhoto['image'], 'assets/banya_images/') !== 0
        && $existingPhoto['image'] !== $newImage) {
        $oldImagePath = __DIR__ . '/../uploads/' . basename($existingPhoto['image']);
        if (is_file($oldImagePath) && !unlink($oldImagePath)) {
            flash('Photo updated, but the previous image file could not be deleted.', 'warning');
            redirect('/admin/gallery.php?edit=' . $id);
        }
    }

    flash($existingPhoto ? 'Photo updated.' : 'Photo uploaded.');
    redirect('/admin/gallery.php');
}

$hostels = $pdo->query('SELECT id, name FROM hostels ORDER BY name')->fetchAll();
$editingItem = null;
if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editingQuery = $pdo->prepare('SELECT * FROM gallery WHERE id = ?');
    $editingQuery->execute([(int) $_GET['edit']]);
    $editingItem = $editingQuery->fetch();
    if (!$editingItem) {
        flash('Photo not found.', 'danger');
        redirect('/admin/gallery.php');
    }
}

$galleryItems = $pdo->query("
    SELECT g.*, h.name AS hostel
    FROM gallery g
    LEFT JOIN hostels h ON h.id = g.hostel_id
    ORDER BY g.category, g.created DESC, g.id DESC
")->fetchAll();

include '_head.php';
?>
<h1 class="h3 mb-3">Gallery</h1>
<form method="post" enctype="multipart/form-data" class="card p-3 p-md-4 mb-4">
    <?= csrf() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editingItem['id'] ?? 0) ?>">
    <h2 class="h5"><?= $editingItem ? 'Edit photo' : 'Upload a photo' ?></h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="gallery-caption">Photo description</label>
            <input
                class="form-control"
                id="gallery-caption"
                name="caption"
                maxlength="160"
                value="<?= e($editingItem['caption'] ?? '') ?>"
                required
            >
        </div>
        <div class="col-md-6">
            <label class="form-label" for="gallery-category">Category</label>
            <select class="form-select" id="gallery-category" name="category" required>
                <option value="">Choose a category</option>
                <?php foreach ($galleryCategories as $category): ?>
                    <option value="<?= e($category) ?>" <?= ($editingItem['category'] ?? '') === $category ? 'selected' : '' ?>>
                        <?= e($category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="gallery-hostel">Hostel (optional)</label>
            <select class="form-select" id="gallery-hostel" name="hostel_id">
                <option value="">General / all hostels</option>
                <?php foreach ($hostels as $hostel): ?>
                    <option value="<?= (int) $hostel['id'] ?>" <?= (int) ($editingItem['hostel_id'] ?? 0) === (int) $hostel['id'] ? 'selected' : '' ?>>
                        <?= e($hostel['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="gallery-image">
                <?= $editingItem ? 'Replace photo (optional)' : 'Photo' ?>
            </label>
            <input
                type="file"
                class="form-control"
                id="gallery-image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
                <?= $editingItem ? '' : 'required' ?>
            >
            <div class="form-text">JPG, PNG or WEBP, up to 2 MB.</div>
        </div>
        <?php if ($editingItem): ?>
            <div class="col-12">
                <img
                    class="gallery-admin-preview"
                    src="<?= e(gallery_image_url($editingItem['image'])) ?>"
                    alt="<?= e($editingItem['caption']) ?>"
                >
            </div>
        <?php endif; ?>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-dark" type="submit"><?= $editingItem ? 'Save changes' : 'Upload photo' ?></button>
            <?php if ($editingItem): ?>
                <a class="btn btn-outline-dark" href="gallery.php">Cancel edit</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<h2 class="h5 mb-3">Uploaded photos</h2>
<?php if (!$galleryItems): ?>
    <p>No photos have been uploaded yet.</p>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($galleryItems as $item): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <article class="card p-2 h-100">
                    <img
                        class="gallery-admin-thumbnail"
                        src="<?= e(gallery_image_url($item['image'])) ?>"
                        alt="<?= e($item['caption']) ?>"
                    >
                    <div class="p-2">
                        <span class="badge text-bg-secondary mb-2"><?= e($item['category']) ?></span>
                        <p class="mb-1"><?= e($item['caption']) ?></p>
                        <?php if ($item['hostel']): ?>
                            <small class="text-muted"><?= e($item['hostel']) ?></small>
                        <?php endif; ?>
                        <div class="d-flex gap-2 mt-3">
                            <a class="btn btn-sm btn-outline-dark" href="gallery.php?edit=<?= (int) $item['id'] ?>">Edit</a>
                            <form method="post">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this photo?">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include '_foot.php'; ?>

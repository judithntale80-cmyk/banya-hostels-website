<?php
require '../config.php';
if (!defined('BASE_URL')) {
    define('BASE_URL', '/banya');
}
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedId = $_POST['id'] ?? '';
    $id = $postedId === '' || $postedId === '0'
        ? 0
        : filter_var($postedId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
    $returnUrl = $id && $id !== false ? '/admin/posts.php?edit=' . $id : '/admin/posts.php';

    if (!csrf_ok()) {
        flash('Your form session expired. Please try again.', 'danger');
        redirect($returnUrl);
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'delete') {
        if (!$id || $id === false) {
            flash('Choose a valid article to delete.', 'danger');
        } else {
            $postQuery = $pdo->prepare('SELECT image FROM posts WHERE id = ?');
            $postQuery->execute([$id]);
            $post = $postQuery->fetch();

            if (!$post) {
                flash('Article not found.', 'danger');
            } else {
                $delete = $pdo->prepare('DELETE FROM posts WHERE id = ?');
                $delete->execute([$id]);
                $imagePath = __DIR__ . '/../uploads/' . basename((string) $post['image']);
                if ($post['image']
                    && strpos($post['image'], 'assets/banya_images/') !== 0
                    && is_file($imagePath)
                    && !unlink($imagePath)) {
                    flash('Article deleted, but its featured image could not be deleted.', 'warning');
                } else {
                    flash('Article deleted.');
                }
            }
        }
        redirect('/admin/posts.php');
    }

    $title = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
    $excerpt = isset($_POST['excerpt']) && is_string($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $body = isset($_POST['body']) && is_string($_POST['body']) ? trim($_POST['body']) : '';
    $category = isset($_POST['category']) && is_string($_POST['category'])
        ? trim($_POST['category'])
        : '';
    $errors = [];
    $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
    $categoryLength = function_exists('mb_strlen')
        ? mb_strlen($category, 'UTF-8')
        : strlen($category);

    if ($id === false) {
        $errors[] = 'Choose a valid article to edit.';
    }
    if ($title === '' || $titleLength > 255) {
        $errors[] = 'Enter an article title of 1 to 255 characters.';
    }
    if ($body === '') {
        $errors[] = 'Enter the article body.';
    }
    if ($category === '' || $categoryLength > 100) {
        $errors[] = 'Enter a category of 1 to 100 characters.';
    }

    $existingPost = null;
    if ($id && $id !== false) {
        $existingQuery = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
        $existingQuery->execute([$id]);
        $existingPost = $existingQuery->fetch();
        if (!$existingPost) {
            $errors[] = 'Article not found.';
        }
    }

    $upload = $_FILES['image'] ?? null;
    $uploadError = is_array($upload) && isset($upload['error'])
        ? (int) $upload['error']
        : UPLOAD_ERR_NO_FILE;
    if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Upload a JPG, PNG or WEBP featured image under 2 MB.';
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

    $image = $newImage ?? ($existingPost['image'] ?? null);
    try {
        if ($existingPost) {
            $save = $pdo->prepare("
                UPDATE posts
                SET title = ?, excerpt = ?, body = ?, category = ?, image = ?
                WHERE id = ?
            ");
            $save->execute([$title, $excerpt, $body, $category, $image, $id]);
        } else {
            $baseSlug = substr(slugify($title) ?: 'article', 0, 175);
            $slug = $baseSlug;
            $suffix = 2;
            $slugCheck = $pdo->prepare('SELECT 1 FROM posts WHERE slug = ?');
            while (true) {
                $slugCheck->execute([$slug]);
                if (!$slugCheck->fetchColumn()) {
                    break;
                }
                $slug = $baseSlug . '-' . $suffix++;
            }

            $save = $pdo->prepare("
                INSERT INTO posts (slug, title, excerpt, body, category, image)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $save->execute([$slug, $title, $excerpt, $body, $category, $image]);
        }
    } catch (PDOException $exception) {
        if ($newImage !== null) {
            $newImagePath = __DIR__ . '/../uploads/' . basename($newImage);
            if (is_file($newImagePath) && !unlink($newImagePath)) {
                error_log('Unable to remove a post upload after a database error.');
            }
        }
        throw $exception;
    }

    if ($newImage !== null && $existingPost && $existingPost['image']
        && strpos($existingPost['image'], 'assets/banya_images/') !== 0
        && $existingPost['image'] !== $newImage) {
        $oldImagePath = __DIR__ . '/../uploads/' . basename($existingPost['image']);
        if (is_file($oldImagePath) && !unlink($oldImagePath)) {
            flash('Article saved, but the previous featured image could not be deleted.', 'warning');
            redirect('/admin/posts.php?edit=' . $id);
        }
    }

    flash($existingPost ? 'Article updated and published.' : 'Article published.');
    redirect('/admin/posts.php');
}

$editingItem = null;
if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editingQuery = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $editingQuery->execute([(int) $_GET['edit']]);
    $editingItem = $editingQuery->fetch();
    if (!$editingItem) {
        flash('Article not found.', 'danger');
        redirect('/admin/posts.php');
    }
}

$categories = $pdo->query('SELECT DISTINCT category FROM posts ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
$posts = $pdo->query('SELECT id, title, category, image, created FROM posts ORDER BY created DESC, id DESC')->fetchAll();

include '_head.php';
?>
<h1 class="h3 mb-3">Blog posts</h1>
<p>Articles saved here are published immediately on the public News &amp; Guides page.</p>

<form method="post" enctype="multipart/form-data" class="card p-3 p-md-4 mb-4">
    <?= csrf() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editingItem['id'] ?? 0) ?>">
    <h2 class="h5"><?= $editingItem ? 'Edit article' : 'Create article' ?></h2>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="post-title">Title</label>
            <input
                class="form-control"
                id="post-title"
                name="title"
                maxlength="255"
                value="<?= e($editingItem['title'] ?? '') ?>"
                required
            >
        </div>
        <div class="col-md-4">
            <label class="form-label" for="post-category">Category</label>
            <input
                class="form-control"
                id="post-category"
                name="category"
                list="post-categories"
                maxlength="100"
                value="<?= e($editingItem['category'] ?? '') ?>"
                required
            >
            <datalist id="post-categories">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category) ?>">
                <?php endforeach; ?>
            </datalist>
            <div class="form-text">Choose an existing category or enter a new one.</div>
        </div>
        <div class="col-12">
            <label class="form-label" for="post-excerpt">Summary</label>
            <textarea class="form-control" id="post-excerpt" name="excerpt" rows="2"><?= e($editingItem['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label" for="post-body">Article body</label>
            <textarea class="form-control" id="post-body" name="body" rows="14" required><?= e($editingItem['body'] ?? '') ?></textarea>
            <div class="form-text">Use blank lines for paragraphs, ## or ### for headings, and - for list items.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="post-image">
                <?= $editingItem ? 'Replace featured image (optional)' : 'Featured image (optional)' ?>
            </label>
            <input
                type="file"
                class="form-control"
                id="post-image"
                name="image"
                accept=".jpg,.jpeg,.png,.webp"
            >
            <div class="form-text">JPG, PNG or WEBP, up to 2 MB.</div>
        </div>
        <?php if (!empty($editingItem['image'])): ?>
            <div class="col-12">
                <img
                    class="gallery-admin-preview"
                    src="<?= e(gallery_image_url($editingItem['image'])) ?>"
                    alt="<?= e($editingItem['title']) ?>"
                >
            </div>
        <?php endif; ?>
        <div class="col-12 d-flex flex-wrap gap-2">
            <button class="btn btn-dark" type="submit">
                <?= $editingItem ? 'Save and publish' : 'Publish article' ?>
            </button>
            <?php if ($editingItem): ?>
                <a class="btn btn-outline-dark" href="posts.php">Cancel edit</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<h2 class="h5 mb-3">Articles</h2>
<?php if (!$posts): ?>
    <p>No articles have been created yet.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Category</th>
                    <th scope="col">Published</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?= e($post['title']) ?></td>
                        <td><?= e($post['category']) ?></td>
                        <td><?= e(date('j M Y', strtotime($post['created']))) ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-dark" href="posts.php?edit=<?= (int) $post['id'] ?>">Edit</a>
                                <form method="post">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        type="submit"
                                        data-confirm="Delete this article?"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php include '_foot.php'; ?>

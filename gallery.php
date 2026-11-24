<?php
require 'config.php';
send_security_headers();

$title = 'Hostel Photos and Facilities - Banya Hostels';
$desc = 'Explore photographs of Banya Hostels, including student rooms, bathrooms, buildings, parking and other accommodation facilities.';
$galleryCategories = gallery_categories();
$selectedCategory = isset($_GET['category']) && is_string($_GET['category'])
    ? trim($_GET['category'])
    : '';

if (!in_array($selectedCategory, $galleryCategories, true)) {
    $selectedCategory = '';
}

$categoryCounts = [];
foreach ($pdo->query('SELECT category, COUNT(*) AS photo_count FROM gallery GROUP BY category') as $row) {
    $categoryCounts[$row['category']] = (int) $row['photo_count'];
}
$totalPhotos = array_sum($categoryCounts);

$galleryQuery = "
    SELECT g.*, h.name AS hostel
    FROM gallery g
    LEFT JOIN hostels h ON h.id = g.hostel_id
";
if ($selectedCategory !== '') {
    $galleryQuery .= ' WHERE g.category = ?';
}
$galleryQuery .= ' ORDER BY g.created DESC, g.id DESC';

$galleryStatement = $pdo->prepare($galleryQuery);
$galleryStatement->execute($selectedCategory !== '' ? [$selectedCategory] : []);
$galleryItems = $galleryStatement->fetchAll();

include 'includes/header.php';
render_page_hero('Gallery');
?>
<section class="container py-5" aria-label="Hostel photo gallery">
    <?php if ($totalPhotos > 0): ?>
        <nav class="gallery-filters mb-4" aria-label="Filter gallery by category">
            <a
                class="btn btn-sm <?= $selectedCategory === '' ? 'btn-brand' : 'btn-outline-secondary' ?>"
                href="<?= BASE_URL ?>/gallery.php"
                <?= $selectedCategory === '' ? 'aria-current="true"' : '' ?>
            >All <span class="gallery-filter-count"><?= $totalPhotos ?></span></a>
            <?php foreach ($galleryCategories as $category): ?>
                <?php if (!empty($categoryCounts[$category])): ?>
                    <a
                        class="btn btn-sm <?= $selectedCategory === $category ? 'btn-brand' : 'btn-outline-secondary' ?>"
                        href="<?= BASE_URL ?>/gallery.php?category=<?= urlencode($category) ?>"
                        <?= $selectedCategory === $category ? 'aria-current="true"' : '' ?>
                    ><?= e($category) ?> <span class="gallery-filter-count"><?= $categoryCounts[$category] ?></span></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!$galleryItems): ?>
        <div class="gallery-empty-state">
            <h2 class="h4"><?= $totalPhotos ? 'No photos in this category yet' : 'Gallery photos coming soon' ?></h2>
            <p class="mb-0"><?= $totalPhotos
                ? 'Choose another category to see more of Banya Hostels.'
                : 'The administration will add photos of the rooms, facilities and hostel surroundings here.' ?></p>
        </div>
    <?php else: ?>
        <div class="row g-4 gallery-grid">
            <?php foreach ($galleryItems as $item): ?>
                <?php
                $imageUrl = gallery_image_url($item['image']);
                $imageDescription = trim((string) $item['caption']);
                $imageAlt = $imageDescription !== ''
                    ? $imageDescription
                    : $item['category'] . ($item['hostel'] ? ' at ' . $item['hostel'] : ' at Banya Hostels');
                ?>
                <div class="col-6 col-lg-4">
                    <figure class="gallery-card h-100">
                        <a
                            class="gallery-image-link"
                            href="<?= e($imageUrl) ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#gallery-lightbox"
                            data-gallery-alt="<?= e($imageAlt) ?>"
                            data-gallery-caption="<?= e($item['category'] . ($item['hostel'] ? ' · ' . $item['hostel'] : '') . ($imageDescription !== '' ? ' — ' . $imageDescription : '')) ?>"
                        >
                            <img
                                class="gallery-image"
                                loading="lazy"
                                src="<?= e($imageUrl) ?>"
                                alt="<?= e($imageAlt) ?>"
                            >
                            <span class="gallery-image-expand" aria-hidden="true"><i class="bi bi-arrows-fullscreen"></i></span>
                        </a>
                        <figcaption class="gallery-caption">
                            <span class="gallery-category"><?= e($item['category']) ?></span>
                            <?php if ($imageDescription !== ''): ?>
                                <span class="gallery-description"><?= e($imageDescription) ?></span>
                            <?php endif; ?>
                            <?php if ($item['hostel']): ?>
                                <span class="gallery-hostel"><?= e($item['hostel']) ?></span>
                            <?php endif; ?>
                        </figcaption>
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade gallery-lightbox" id="gallery-lightbox" tabindex="-1" aria-labelledby="gallery-lightbox-caption" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <button type="button" class="btn-close btn-close-white gallery-lightbox-close" data-bs-dismiss="modal" aria-label="Close image"></button>
            <img class="gallery-lightbox-image" id="gallery-lightbox-image" alt="">
            <p class="gallery-lightbox-caption" id="gallery-lightbox-caption"></p>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>

<?php
require 'config.php';

function render_post_body($body)
{
    $html = '';
    $paragraph = [];
    $listOpen = false;
    $lines = preg_split('/\R/', trim((string) $body));
    $flushParagraph = function () use (&$paragraph, &$html) {
        if ($paragraph) {
            $html .= '<p>' . e(implode(' ', array_map('trim', $paragraph))) . '</p>';
            $paragraph = [];
        }
    };
    $closeList = function () use (&$listOpen, &$html) {
        if ($listOpen) {
            $html .= '</ul>';
            $listOpen = false;
        }
    };

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            $flushParagraph();
            $closeList();
        } elseif (preg_match('/^(#{2,3})\s+(.+)$/', $line, $matches)) {
            $flushParagraph();
            $closeList();
            $level = strlen($matches[1]);
            $heading = trim($matches[2]);
            $html .= '<h' . $level . ' id="' . e(slugify($heading)) . '">'
                . e($heading) . '</h' . $level . '>';
        } elseif (preg_match('/^-\s+(.+)$/', $line, $matches)) {
            $flushParagraph();
            if (!$listOpen) {
                $html .= '<ul>';
                $listOpen = true;
            }
            $html .= '<li>' . e($matches[1]) . '</li>';
        } else {
            $closeList();
            $paragraph[] = $line;
        }
    }

    $flushParagraph();
    $closeList();

    return $html;
}

function post_image_src($image)
{
    $image = (string) $image;
    if (strpos($image, 'assets/banya_images/') !== 0) {
        $image = 'uploads/' . basename($image);
    }

    $segments = array_map('rawurlencode', explode('/', $image));
    return BASE_URL . '/' . implode('/', $segments);
}

$hasSlug = array_key_exists('slug', $_GET);
$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';

if ($hasSlug) {
    $statement = $pdo->prepare('SELECT * FROM posts WHERE slug = ?');
    $statement->execute([$slug]);
    $post = $statement->fetch();

    if (!$post) {
        http_response_code(404);
        $title = 'Not found';
        include 'includes/header.php';
        ?>
        <div class="container py-5">
            <h1>Article not found</h1>
            <a href="<?= BASE_URL ?>/news.php">Back to news</a>
        </div>
        <?php
        include 'includes/footer.php';
        exit;
    }

    $title = $post['title'];
    $description = $post['excerpt'] ?: substr(strip_tags($post['body']), 0, 155);
    $desc = $description;
    $wordCount = str_word_count(strip_tags($post['body']));

    $relatedStatement = $pdo->prepare(
        'SELECT title, slug, category FROM posts WHERE id <> ? ORDER BY category = ? DESC, created DESC LIMIT 3'
    );
    $relatedStatement->execute([$post['id'], $post['category']]);
    $relatedPosts = $relatedStatement->fetchAll();

    $extraHead = '<script type="application/ld+json">'
        . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'],
            'datePublished' => date('c', strtotime($post['created'])),
            'description' => $description,
            'author' => ['@type' => 'Organization', 'name' => setting('site_name')],
            'articleSection' => $post['category'],
            'wordCount' => $wordCount,
        ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)
        . '</script>';

    include 'includes/header.php';
    ?>
    <article class="container py-5 article">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/news.php">News</a></li>
                <li class="breadcrumb-item active"><?= e($post['title']) ?></li>
            </ol>
        </nav>
        <a class="badge text-bg-secondary text-decoration-none mb-2" href="<?= BASE_URL ?>/news.php?cat=<?= urlencode($post['category']) ?>"><?= e($post['category']) ?></a>
        <h1><?= e($post['title']) ?></h1>
        <small class="text-muted"><?= date('j F Y', strtotime($post['created'])) ?> &middot; <?= max(1, round($wordCount / 200)) ?> min read</small>
        <?php if ($post['image']): ?>
            <img class="img-fluid my-4 rounded" src="<?= e(post_image_src($post['image'])) ?>" alt="<?= e($post['title']) ?>">
        <?php endif; ?>
        <div class="mt-3"><?= render_post_body($post['body']) ?></div>
        <div class="card p-3 mt-4 bg-soft">
            <strong>Ready to book?</strong>
            <div class="mt-2">
                <a class="btn btn-brand" href="<?= BASE_URL ?>/booking.php">Book &amp; Pay</a>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/hostel.php?slug=banya-4">See Banya 4</a>
            </div>
        </div>
        <?php if ($relatedPosts): ?>
            <h2 class="h5 mt-5">More from Banya Hostels</h2>
            <ul>
                <?php foreach ($relatedPosts as $relatedPost): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/news.php?slug=<?= rawurlencode($relatedPost['slug']) ?>"><?= e($relatedPost['title']) ?></a>
                        <small class="text-muted">(<?= e($relatedPost['category']) ?>)</small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <?php
    include 'includes/footer.php';
    exit;
}

$title = 'Student Accommodation News and Guides';
$desc = 'Read student accommodation guides, booking advice, hostel information and updates from Banya Hostels in Fort Portal.';
$cat = isset($_GET['cat']) && is_string($_GET['cat']) ? trim($_GET['cat']) : '';
$cats = $pdo->query('SELECT category, COUNT(*) n FROM posts GROUP BY category ORDER BY category')->fetchAll();
$statement = $pdo->prepare('SELECT * FROM posts' . ($cat !== '' ? ' WHERE category = ?' : '') . ' ORDER BY created DESC');
$statement->execute($cat !== '' ? [$cat] : []);
$posts = $statement->fetchAll();

if ($cat !== '') {
    $title = $cat . ' | News';
}

$featuredPost = ($cat === '' && $posts) ? array_shift($posts) : null;
$total = array_sum(array_column($cats, 'n'));

include 'includes/header.php';
render_page_hero('News & guides', 'News, guides & updates');
?>
<div class="container py-5">
    <p class="lead">Guides for students, booking and payment help, safety tips and updates about Banya 3.</p>
    <div class="mb-4 d-flex flex-wrap gap-2" aria-label="Categories">
        <a class="btn btn-sm btn-outline-secondary filter-btn <?= $cat === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/news.php">All (<?= $total ?>)</a>
        <?php foreach ($cats as $category): ?>
            <a class="btn btn-sm btn-outline-secondary filter-btn <?= $cat === $category['category'] ? 'active' : '' ?>" href="<?= BASE_URL ?>/news.php?cat=<?= urlencode($category['category']) ?>"><?= e($category['category']) ?> (<?= $category['n'] ?>)</a>
        <?php endforeach; ?>
    </div>
    <?php if ($featuredPost): ?>
        <div class="card mb-4 overflow-hidden">
            <div class="row g-0">
                <?php if ($featuredPost['image']): ?>
                    <div class="col-md-5">
                        <img class="w-100 h-100" style="object-fit:cover;min-height:220px" loading="lazy" src="<?= e(post_image_src($featuredPost['image'])) ?>" alt="<?= e($featuredPost['title']) ?>">
                    </div>
                <?php endif; ?>
                <div class="col p-4">
                    <span class="badge text-bg-warning mb-2">Latest &middot; <?= e($featuredPost['category']) ?></span>
                    <h2 class="h3"><a class="text-decoration-none"href="<?= BASE_URL ?>/news.php?slug=<?= rawurlencode($featuredPost['slug']) ?>"><?= e($featuredPost['title']) ?></a></h2>
                    <p class="text-muted small mb-2"><?= date('j F Y', strtotime($featuredPost['created'])) ?> &middot; <?= max(1, round(str_word_count(strip_tags($featuredPost['body'])) / 200)) ?> min read</p>
                    <p><?= e($featuredPost['excerpt']) ?></p>
                    <a class="btn btn-brand"href="<?= BASE_URL ?>/news.php?slug=<?= rawurlencode($featuredPost['slug']) ?>">Read article</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 p-3">
                    <?php if ($post['image']): ?>
                        <img class="card-img-top mb-3" loading="lazy" src="<?= e(post_image_src($post['image'])) ?>" alt="<?= e($post['title']) ?>">
                    <?php endif; ?>
                    <span class="badge text-bg-secondary align-self-start mb-2"><?= e($post['category']) ?></span>
                    <h2 class="h5"><a class="text-decoration-none"href="<?= BASE_URL ?>/news.php?slug=<?= rawurlencode($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                    <small class="text-muted"><?= date('j M Y', strtotime($post['created'])) ?> &middot; <?= max(1, round(str_word_count(strip_tags($post['body'])) / 200)) ?> min read</small>
                    <p class="mt-2"><?= e($post['excerpt']) ?></p>
                    <a class="mt-auto"href="<?= BASE_URL ?>/news.php?slug=<?= rawurlencode($post['slug']) ?>">Read more &rarr;</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$featuredPost && !$posts): ?>
        <p>No articles in this category yet.</p>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>

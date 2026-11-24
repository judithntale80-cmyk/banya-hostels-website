<?php
// ===== PAGE SETUP AND HOSTEL DATA =====
require 'config.php';
send_security_headers();
$title = 'Affordable Student Accommodation in Fort Portal | Banya Hostels';
$desc = 'Discover Banya Hostels in Fort Portal, offering student accommodation near Mountains of the Moon University. Explore our rooms, facilities and booking options.';

$hostels = $pdo->query("
    SELECT h.*,
        (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id AND r.status = 'available') AS available_rooms,
        (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) AS total_rooms
    FROM hostels h
    WHERE h.status = 'open'
    ORDER BY h.id
")->fetchAll();

$offers = $pdo->query("
    SELECT p.id AS pricing_id, p.label, p.amount, p.slots, p.note,
        h.id AS hostel_id, h.slug, h.name, h.description, h.facilities, h.image,
        (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id AND r.status = 'available') AS available_rooms
    FROM pricing p
    JOIN hostels h ON h.id = p.hostel_id
    WHERE h.status = 'open' AND p.slots <= 2
    ORDER BY h.id, p.amount DESC
")->fetchAll();

$facilityItems = [];
foreach ($hostels as $hostel) {
    $facilities = strtolower((string) $hostel['facilities']);
    if (strpos($facilities, 'water') !== false || strpos($facilities, 'electricity') !== false) {
        $facilityItems['utilities'] = [
            'bi-droplet-half',
            'Water & electricity',
            'Water and electricity are available at our open hostels.',
        ];
    }
    if (strpos($facilities, 'cctv') !== false || strpos($facilities, 'security') !== false) {
        $facilityItems['security'] = [
            'bi-shield-check',
            'CCTV security',
            'CCTV cameras are available at Banya 4 and Banya 5.',
        ];
    }
    if (strpos($facilities, 'bathroom') !== false || strpos($facilities, 'self-contained') !== false) {
        $facilityItems['bathrooms'] = [
            'bi-house-check',
            'Bathroom options',
            'Choose shared or self-contained bathrooms, depending on the hostel.',
        ];
    }
    if (strpos($facilities, 'wi-fi') !== false || strpos($facilities, 'wifi') !== false) {
        $facilityItems['wifi'] = [
            'bi-wifi',
            'Wi-Fi options',
            'Availability and charges vary by hostel; ask us for details.',
        ];
    }
    if (strpos($facilities, 'cook') !== false) {
        $facilityItems['cooking'] = [
            'bi-cup-hot',
            'Cooking space',
            'A space to cook is available at Banya 5.',
        ];
    }
    if (strpos($facilities, 'parking') !== false) {
        $facilityItems['parking'] = [
            'bi-p-circle',
            'Parking',
            'Some parking space is available at Banya 4.',
        ];
    }
}

$roomImages = [
    'banya-5' => "Student's room at banya 5.webp",
    'banya-4' => 'student-room-wall-tv at banya 4.jpg',
    'banya-3' => 'banya3-construction-columns.jpg',
];
include 'includes/header.php';
?>
<!-- ===== HOME PAGE: INTRODUCTION AND HOSTEL HIGHLIGHTS ===== -->
<section class="home-hero">
    <div class="container home-hero-content">
        <p class="eyebrow text-uppercase">Banya Hostels · Fort Portal</p>
        <h1>Your Comfort, Your Space,<br>Your Home Away From Home.</h1>
        <p class="lead">Experience comfortable, affordable and convenient student accommodation in a peaceful environment designed to make your university life enjoyable.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
            <a class="btn btn-gold btn-lg" href="#featured-rooms">Explore Our Rooms</a>
            <a class="btn btn-outline-light btn-lg" href="<?= BASE_URL ?>/booking.php">Book Your Stay</a>
        </div>
        <div class="hero-note"><i class="bi bi-geo-alt"></i> Close to Mountains of the Moon University</div>
    </div>
</section>

<!-- ===== HOME PAGE: ABOUT AND FEATURED ROOM OFFERS ===== -->
<section class="section-space" id="about-banya">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-photo-wrap">
                    <img
                        class="about-photo"
                        src="<?= BASE_URL ?>/assets/banya_images/Banya4 Showcase.png"
                        alt="The Banya Hostels building"
                        loading="lazy"
                    >
                </div>
            </div>
            <div class="col-lg-6">
                <p class="eyebrow">A place to feel at home</p>
                <h2 class="section-title">About Banya Hostel</h2>
                <p class="section-copy"><?= e(mb_strimwidth(trim((string)setting('about')), 0, 360, '…')) ?></p>
                <p>Our mission is to provide Mountains of the Moon University students with practical, welcoming accommodation close to campus.</p>
                <a class="btn btn-brand mt-2" href="<?= BASE_URL ?>/about.php">Read More <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="section-space bg-section" id="featured-rooms">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Make yourself at home</p>
                <h2 class="section-title mb-0">Featured Rooms</h2>
            </div>
            <a class="text-link" href="<?= BASE_URL ?>/booking.php">
            See booking details <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <?php
        $shownOffers = 0;
        foreach ($offers as $offer):
            $shownOffers++;
            $photo = $offer['slug'] === 'banya-4' && (int) $offer['slots'] > 1
                ? BASE_URL . '/assets/banya_images/banya4-double-room.jpg'
                : (in_array($offer['slug'], ['banya-4', 'banya-5'], true)
                ? BASE_URL . '/assets/banya_images/' . rawurlencode($roomImages[$offer['slug']])
                : ($offer['image']
                ? BASE_URL . '/uploads/' . rawurlencode($offer['image'])
                    : BASE_URL . '/assets/banya_images/' . rawurlencode($roomImages[$offer['slug']] ?? 'main-building-front.jpg')));
        ?>
            <?php if ($shownOffers === 1): ?>
                <div class="row g-4">
            <?php endif; ?>
            <div class="col-md-6 col-xl-4">
                <article class="card room-card h-100">
                    <div class="room-photo-wrap">
                        <img
                            class="card-img-top room-photo"
                            src="<?= e($photo) ?>"
                            alt="<?= e($offer['name'] . ' ' . $offer['label']) ?>"
                            loading="lazy"
                        >
                        <span class="room-badge">
                            <?= (int) $offer['available_rooms'] > 0
                                ? (int) $offer['available_rooms'] . ' rooms available'
                                : 'Ask about availability' ?>
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <p class="eyebrow mb-2"><?= e($offer['name']) ?></p>
                        <h3 class="h4"><?= e($offer['label']) ?></h3>
                        <p class="room-description">
                            <?= e(mb_strimwidth(trim((string) $offer['description']), 0, 145, '…')) ?>
                        </p>
                        <div class="room-details">
                            <span>
                                <i class="bi bi-people"></i>
                                Up to <?= (int) $offer['slots'] ?>
                                <?= (int) $offer['slots'] === 1 ? 'occupant' : 'occupants' ?>
                            </span>
                            <?php if ($offer['note']): ?>
                                <span><i class="bi bi-info-circle"></i> <?= e($offer['note']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="room-price mt-3">
                            UGX <?= number_format((float) $offer['amount']) ?>
                            <small> / semester</small>
                        </p>
                        <div class="d-flex gap-2 mt-auto">
                            <a
                                class="btn btn-outline-brand flex-fill"
                                href="<?= BASE_URL ?>/hostel/<?= e($offer['slug']) ?>"
                            >
                                View Details
                            </a>
                            <a
                                class="btn btn-brand flex-fill"
                                href="<?= BASE_URL ?>/booking.php?hostel=<?= (int) $offer['hostel_id'] ?>&amp;pricing=<?= (int) $offer['pricing_id'] ?>"
                            >
                                Book Now
                            </a>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
        <?php if ($shownOffers > 0): ?>
            </div>
        <?php endif; ?>
        <?php if ($shownOffers === 0): ?>
            <div class="empty-search">
                <i class="bi bi-house-x"></i>
                <h3 class="h5 mt-3">Room details are coming soon</h3>
                <p class="mb-3">Contact us to ask about current accommodation options.</p>
                <a class="btn btn-brand" href="<?= BASE_URL ?>/contact.php">Contact Us</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== HOME PAGE: FACILITIES AND REASONS TO CHOOSE BANYA ===== -->
<?php if ($facilityItems): ?>
<section class="section-space">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Thoughtful essentials</p>
                <h2 class="section-title mb-0">Facilities &amp; Amenities</h2>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($facilityItems as [$icon, $label, $copy]): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="amenity-card">
                        <span class="amenity-icon">
                            <i class="bi <?= e($icon) ?>"></i>
                        </span>
                        <div>
                            <h3 class="h6"><?= e($label) ?></h3>
                            <p class="mb-0"><?= e($copy) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section-space bg-section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">The Banya difference</p>
                <h2 class="section-title mb-0">Why Choose Banya Hostel?</h2>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3">
                <div class="feature-card">
                    <i class="bi bi-house-heart"></i>
                    <h3 class="h5">Comfortable Accommodation</h3>
                    <p>Room options for different student needs, with shared and self-contained choices.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-card">
                    <i class="bi bi-wallet2"></i>
                    <h3 class="h5">Affordable Student Living</h3>
                    <p>Clear semester pricing, with flexible choices at Banya 4.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-card">
                    <i class="bi bi-geo-alt"></i>
                    <h3 class="h5">Convenient Location</h3>
                    <p>Stay close to Mountains of the Moon University in Fort Portal.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="feature-card">
                    <i class="bi bi-book"></i>
                    <h3 class="h5">Student-Focused</h3>
                    <p>A place to settle in and make the most of your university experience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOME PAGE: RESIDENT INFORMATION AND BOOKING CALL TO ACTION ===== -->
<section class="section-space testimonials-section">
    <div class="container text-center">
        <p class="eyebrow">Resident voices</p>
        <h2 class="section-title">Life at Banya</h2>
        <div class="testimonial-invite mx-auto">
            <p class="mb-3">The photographs on our gallery pack will walk through the hostel’s bright rooms with desks for study, clean tiled bathrooms, long corridors, lockable doors and covered verandas. Students can check available rooms online, send a booking request, send enquiries or complaints, all in one place.</p>
            <a class="text-link" href="<?= BASE_URL ?>/gallery.php">View our gallery <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="home-cta">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
        <div>
            <p class="eyebrow text-uppercase mb-2">Your next chapter starts here</p>
            <h2 class="mb-0">Ready to Find Your New Home?</h2>
        </div>
        <a class="btn btn-gold btn-lg" href="<?= BASE_URL ?>/booking.php">Book Your Room <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
</section>
<?php include 'includes/footer.php'; ?>

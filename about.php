<?php
// ===== PAGE SETUP =====
require 'config.php';
send_security_headers();
$title = 'About Banya Hostel - Student Accommodation in Fort Portal';
$desc = 'Learn about Banya Hostels, our accommodation, facilities, values and commitment to providing comfortable student housing in Fort Portal.';
include 'includes/header.php';
?>
<!-- ===== ABOUT PAGE: STORY, PURPOSE, AND VALUES ===== -->
<section class="about-banner">
    <div class="container">
        <p class="eyebrow text-uppercase">Banya Hostels · Fort Portal</p>
        <h1 class="mb-0">About Banya Hostel</h1>
    </div>
</section>

<section class="section-space about-story">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">A place to feel at home</p>
                <h2 class="section-title mb-0">Our Story</h2>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <article class="about-story-card" tabindex="0">
                    <h3 class="h4">How did Banya begin?</h3>
                    <div class="about-story-details">
                        <div class="about-story-details-inner">
                            <span class="about-story-icon"><i class="bi bi-house-heart" aria-hidden="true"></i></span>
                            <div>
                                <p class="mb-0">Banya Hostel was established with a vision of providing comfortable, affordable and welcoming accommodation in Fort Portal. Recognising the importance of having a safe and convenient place to stay while pursuing education and other opportunities, the hostel was developed to offer more than just accommodation. It was created to provide a place where residents can feel at home, build meaningful connections and enjoy a peaceful living environment.</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-lg-4">
                <article class="about-story-card" tabindex="0">
                    <h3 class="h4">Why was Banya established?</h3>
                    <div class="about-story-details">
                        <div class="about-story-details-inner">
                            <span class="about-story-icon"><i class="bi bi-bullseye" aria-hidden="true"></i></span>
                            <div>
                                <p class="mb-0">Banya Hostel was established to address the need for reliable, accessible and affordable accommodation near MMU. Our goal is to provide residents with a secure, clean and supportive environment where they can relax, study, work and grow.</p>
                                <p class="mt-3 mb-0">We believe that a good living environment contributes significantly to personal well-being, productivity and success. This is why we are committed to offering quality accommodation, friendly services and an atmosphere that promotes comfort, convenience and community.</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <div class="col-lg-4">
                <article class="about-story-card" tabindex="0">
                    <h3 class="h4">Who does Banya serve?</h3>
                    <div class="about-story-details">
                        <div class="about-story-details-inner">
                            <span class="about-story-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                            <div>
                                <p class="mb-0">Banya Hostel welcomes individuals looking for a comfortable and convenient place to stay, especially university students from Mountains of the Moon University and other nearby institutions seeking convenient accommodation.</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section-space bg-section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">What guides us</p>
                <h2 class="section-title mb-0">Vision and Mission</h2>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <article class="about-purpose-card">
                    <span class="about-purpose-icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                    <h3 class="h4">Our Vision</h3>
                    <p class="mb-0">To be a preferred student accommodation destination, providing a comfortable, secure and welcoming living environment.</p>
                </article>
            </div>
            <div class="col-md-6">
                <article class="about-purpose-card">
                    <span class="about-purpose-icon"><i class="bi bi-compass" aria-hidden="true"></i></span>
                    <h3 class="h4">Our Mission</h3>
                    <p class="mb-0">To provide affordable, quality accommodation that supports students' academic, social and personal development.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section-space">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">The way we welcome you</p>
                <h2 class="section-title mb-0">Our Core Values</h2>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-heart" aria-hidden="true"></i><h3 class="h5 mb-0">Comfort</h3></div></div>
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-shield-check" aria-hidden="true"></i><h3 class="h5 mb-0">Security</h3></div></div>
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-wallet2" aria-hidden="true"></i><h3 class="h5 mb-0">Affordability</h3></div></div>
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-stars" aria-hidden="true"></i><h3 class="h5 mb-0">Cleanliness</h3></div></div>
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-house-heart" aria-hidden="true"></i><h3 class="h5 mb-0">Hospitality</h3></div></div>
            <div class="col-sm-6 col-lg-4"><div class="about-value"><i class="bi bi-people" aria-hidden="true"></i><h3 class="h5 mb-0">Community</h3></div></div>
        </div>
    </div>
</section>

<!-- ===== ABOUT PAGE: HOSTEL FACILITIES ===== -->
<section class="section-space bg-section about-facilities">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Find the right place for you</p>
                <h2 class="section-title mb-0">Our Facilities</h2>
            </div>
        </div>

        <article class="about-facility mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <img src="<?= BASE_URL ?>/assets/banya_images/yellow-block-red-roof.jpg" alt="Exterior of Banya 5" loading="lazy">
                </div>
                <div class="col-lg-6">
                    <div class="about-facility-copy">
                        <p class="eyebrow">A room of your own</p>
                        <h3 class="section-title">Banya 5</h3>
                        <p class="mb-0">Banya 5 has 50 single rooms for students who want their own space at UGX 600,000 per room. Bathrooms are shared, water and electricity are available, Wi-Fi is available, and residents have a space where they can cook. CCTV cameras around the hostel help everyone feel safe.</p>
                    </div>
                </div>
            </div>
        </article>

        <article class="about-facility mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="about-facility-copy">
                        <p class="eyebrow">Flexible room sharing</p>
                        <h3 class="section-title">Banya 4</h3>
                        <p class="mb-0">Banya 4 has 55 self-contained rooms, designed as doubles. A student can stay alone for UGX 800,000, or share. Two students pay UGX 500,000 each. The more friends share, the less each one pays. Wi-Fi is installed, and residents choose daily, weekly or monthly subscriptions. There is some parking space, and CCTV watches over the compound.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="<?= BASE_URL ?>/assets/banya_images/main-building-front.jpg" alt="Exterior of Banya 4" loading="lazy">
                </div>
            </div>
        </article>

        <article class="about-facility">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <img src="<?= BASE_URL ?>/assets/banya_images/banya3-construction-staircase.jpg" alt="Staircase under construction at Banya 3" loading="lazy">
                </div>
                <div class="col-lg-6">
                    <div class="about-facility-copy">
                        <p class="eyebrow">Growing for the future</p>
                        <h3 class="section-title">Banya 3</h3>
                        <p class="mb-0">Banya 3 is rising right now. The photographs of its columns, slabs and staircase show a hostel taking shape. When it is complete, its rooms, facilities and prices will be added to this website, so students can plan ahead and be first to book.</p>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>

<!-- ===== NEXT STEP: BOOKING LINK ===== -->
<section class="home-cta">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
        <h2 class="mb-0">Experience Comfortable Student Living.</h2>
        <a class="btn btn-gold btn-lg" href="<?= BASE_URL ?>/booking.php">Book Your Room <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
    </div>
</section>
<?php include 'includes/footer.php'; ?>

<?php
require 'config.php';
send_security_headers();

$title = 'Contact Banya Hostels - Fort Portal';
$desc = 'Contact Banya Hostels in Fort Portal for room availability, accommodation information, booking assistance and enquiries.';
$fields = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Your form session expired. Please try submitting the form again.';
    } else {
        foreach ($fields as $key => $value) {
            $postedValue = $_POST[$key] ?? '';
            $fields[$key] = is_string($postedValue) ? trim($postedValue) : '';
        }

        if ($fields['name'] === '' || strlen($fields['name']) > 100) {
            $errors[] = 'Enter your full name (up to 100 characters).';
        }
        if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL) || strlen($fields['email']) > 120) {
            $errors[] = 'Enter a valid email address (up to 120 characters).';
        }
        if ($fields['phone'] === '' || strlen($fields['phone']) > 30) {
            $errors[] = 'Enter a phone number (up to 30 characters).';
        }
        if ($fields['subject'] === '' || strlen($fields['subject']) > 150) {
            $errors[] = 'Enter a subject (up to 150 characters).';
        }
        if ($fields['message'] === '' || strlen($fields['message']) > 5000) {
            $errors[] = 'Enter a message (up to 5,000 characters).';
        }

        if (!$errors) {
            $insert = $pdo->prepare("
                INSERT INTO enquiries (type, name, email, phone, subject, body)
                VALUES ('enquiry', ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $fields['name'],
                $fields['email'],
                $fields['phone'],
                $fields['subject'],
                $fields['message'],
            ]);
            flash('Thank you for contacting Banya Hostel. Your message has been received.');
            redirect('/contact.php');
        }
    }
}

$notification = flash();
$phone = setting('phone');
$whatsapp = preg_replace('/[^0-9]/', '', setting('whatsapp'));
$email = setting('email');
$address = setting('address');
$hours = setting('hours');

include 'includes/header.php';
render_page_hero('Get in Touch With Banya Hostel.');
?>

<section class="container contact-main py-5">
    <div class="contact-intro text-center">
        <p class="eyebrow">Reach out</p>
        <h2 class="section-title mb-2">We’re here to help</h2>
        <p>Call, email or visit us in Fort Portal. Our team can help with room options, bookings and enquiries.</p>
    </div>

    <div class="row g-4">
        <aside class="col-lg-5">
            <div class="contact-info">
                <h3 class="h4 mb-4">Get in touch</h3>
                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                        <div>
                            <h4>Our Location</h4>
                            <p><?= e($address) ?></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true"><i class="bi bi-telephone"></i></span>
                        <div>
                            <h4>Call Us</h4>
                            <p><a href="tel:<?= e(preg_replace('/[^0-9+]/','',$phone)) ?>"><?= e($phone) ?></a></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                        <div>
                            <h4>Email Us</h4>
                            <p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
                        </div>
                    </div>
                    <div class="contact-info-item">
                        <span class="contact-info-icon" aria-hidden="true"><i class="bi bi-clock"></i></span>
                        <div>
                            <h4>Working Hours</h4>
                            <p><?= e($hours) ?></p>
                        </div>
                    </div>
                </div>

                <div class="contact-social-links">
                    <h3 class="h5">Connect with us</h3>
                    <a
                        class="btn btn-outline-brand"
                        href="https://wa.me/<?= e($whatsapp) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Message Banya Hostel on WhatsApp"
                    >
                        <i class="bi bi-whatsapp me-1" aria-hidden="true"></i>WhatsApp
                    </a>
                </div>
            </div>

            <a
                class="text-link d-inline-block mt-4"
                href="https://www.google.com/maps/place/BANYA+4HOSTEL/@0.6651908,30.2693191,17z/data=!3m1!4b1!4m6!3m5!1s0x1763db006eaa13ff:0x2d9bf625d76d5e5c!8m2!3d0.6651908!4d30.271894!16s%2Fg%2F11zb8p58yc?entry=ttu&amp;g_ep=EgoyMDI2MTExMC4wIKXMDSoASAFQAw%3D%3D"
                target="_blank"
                rel="noopener noreferrer"
            >
                <i class="bi bi-map me-1" aria-hidden="true"></i>Open in Google Maps
            </a>
            <div class="ratio ratio-16x9 rounded overflow-hidden contact-map">
                <iframe
                    src="https://maps.google.com/maps?q=0.6651908,30.271894&amp;z=17&amp;output=embed"
                    title="Map showing Banya Hostel in Fort Portal"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>
            </div>
        </aside>

        <div class="col-lg-7">
            <?php if ($notification): ?>
                <?= $notification ?>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <p class="mb-1">Please review the following:</p>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="card contact-form-card p-4 p-md-5">
                <h3 class="h4 mb-4">Send us a message</h3>
                <?= csrf() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="contact-name">Full Name</label>
                        <input id="contact-name" class="form-control" name="name" maxlength="100" required value="<?= e($fields['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-email">Email Address</label>
                        <input id="contact-email" type="email" class="form-control" name="email" maxlength="120" required value="<?= e($fields['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-phone">Phone Number</label>
                        <input id="contact-phone" type="tel" class="form-control" name="phone" maxlength="30" required value="<?= e($fields['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact-subject">Subject</label>
                        <input id="contact-subject" class="form-control" name="subject" maxlength="150" required value="<?= e($fields['subject']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="contact-message">Message</label>
                        <textarea id="contact-message" class="form-control" name="message" rows="5" maxlength="5000" required><?= e($fields['message']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-brand">Send message</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

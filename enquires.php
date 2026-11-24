<?php
require 'config.php';
send_security_headers();

$title = 'Student Accommodation Enquiries - Banya Hostels';
$desc = 'Have questions about accommodation, room sharing, hostel facilities or booking? Send an enquiry to Banya Hostels.';
$categories = [
    'Room Availability',
    'Accommodation Fees',
    'Facilities',
    'Booking Assistance',
    'Payment Assistance',
    'Other',
];
$fields = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'category' => '',
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
            $errors[] = 'Enter a telephone number (up to 30 characters).';
        }
        if (!in_array($fields['category'], $categories, true)) {
            $errors[] = 'Choose a valid enquiry category.';
        }
        if ($fields['subject'] === '' || strlen($fields['subject']) > 150) {
            $errors[] = 'Enter a subject (up to 150 characters).';
        }
        if ($fields['message'] === '' || strlen($fields['message']) > 5000) {
            $errors[] = 'Enter a message (up to 5,000 characters).';
        }

        if (!$errors) {
            $insert = $pdo->prepare("
                INSERT INTO enquiries (type, category, name, email, phone, subject, body)
                VALUES ('enquiry', ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $fields['category'],
                $fields['name'],
                $fields['email'],
                $fields['phone'],
                $fields['subject'],
                $fields['message'],
            ]);
            flash('Thank you for contacting Banya Hostel. Your enquiry has been received. Our team will get back to you shortly.');
            redirect('/enquires.php');
        }
    }
}

$notification = flash();
$pricingOptions = $pdo->query("
    SELECT h.name AS hostel, p.label, p.amount, p.note
    FROM pricing p
    JOIN hostels h ON h.id = p.hostel_id
    WHERE h.status = 'open'
    ORDER BY h.id, p.amount DESC
")->fetchAll();

$faqs = [
    [
        'What types of rooms are available?',
        'Banya 5 has single rooms with shared bathrooms. Banya 4 has self-contained rooms that can be used by one or two students. Banya 3 is under construction. Check the hostel pages for current details.',
    ],
    [
        'How much does accommodation cost?',
        '',
    ],
    [
        'What is included in the accommodation fee?',
        'Facilities differ by hostel. Water and electricity are listed among the facilities; Wi-Fi arrangements also vary, and Banya 4 Wi-Fi is paid separately. Ask management to confirm what applies to your chosen room.',
    ],
    [
        'Are rooms furnished?',
        'Furnishing details are not listed for every room. Please ask us to confirm what is provided at your chosen hostel before booking.',
    ],
    [
        'How do I reserve a room?',
        'Submit a request on the booking page by choosing an available room and price option, then entering your details. Requests remain pending until approved by the administration.',
    ],
    [
        'What payment methods are accepted?',
        'The booking page lists the official Mobile Money, bank transfer and cash payment channels. Please wait until your booking is approved before making a payment.',
    ],
    [
        'Can I visit the hostel before booking?',
        'Yes. Contact our team to arrange a visit before booking.',
    ],
    [
        'What are the hostel rules?',
        'House rules can vary by hostel. Contact management for the current rules on guests, noise, safety and shared spaces before moving in.',
    ],
];

include 'includes/header.php';
render_page_hero('Have Questions About Staying With Us?');
?>
<section class="container py-5" aria-labelledby="enquiries-intro-title">
    <div class="row justify-content-center">
        <div class="col-lg-9 text-center">
            <p class="eyebrow">Accommodation enquiries</p>
            <h2 class="section-title" id="enquiries-intro-title">Get answers before you book</h2>
            <p class="mb-0">Ask us about rooms, fees, facilities or the booking process. For general contact details and directions, visit our <a href="<?= BASE_URL ?>/contact.php">Contact page</a>.</p>
        </div>
    </div>
</section>

<section class="bg-section py-5" aria-labelledby="enquiry-faq-title">
    <div class="container">
        <div class="section-heading mb-4">
            <div>
                <p class="eyebrow mb-1">Accommodation help</p>
                <h2 class="section-title mb-0" id="enquiry-faq-title">Frequently Asked Questions</h2>
            </div>
            <p class="mb-0">Select a question to read its answer.</p>
        </div>

        <div class="accordion contact-faq" id="accommodation-faq">
            <?php foreach ($faqs as $index => [$question, $answer]): ?>
                <?php
                $faqNumber = $index + 1;
                $headingId = 'accommodation-faq-heading-' . $faqNumber;
                $answerId = 'accommodation-faq-answer-' . $faqNumber;
                ?>
                <div class="accordion-item">
                    <h3 class="accordion-header" id="<?= e($headingId) ?>">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= e($answerId) ?>"
                            aria-expanded="false"
                            aria-controls="<?= e($answerId) ?>"
                        ><?= e($question) ?></button>
                    </h3>
                    <div
                        id="<?= e($answerId) ?>"
                        class="accordion-collapse collapse"
                        aria-labelledby="<?= e($headingId) ?>"
                        data-bs-parent="#accommodation-faq"
                    >
                        <div class="accordion-body">
                            <?php if ($index === 1): ?>
                                <?php if ($pricingOptions): ?>
                                    <p>Rates depend on the hostel and occupancy option. Current listed prices are:</p>
                                    <ul class="mb-0">
                                        <?php foreach ($pricingOptions as $option): ?>
                                            <li>
                                                <?= e($option['hostel']) ?> — <?= e($option['label']) ?>:
                                                UGX <?= number_format((float) $option['amount']) ?>
                                                <?php if ($option['note']): ?>
                                                    (<?= e($option['note']) ?>)
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>Rates depend on the hostel and room option. Please contact us for current prices.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <?= e($answer) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container py-5" aria-labelledby="submit-enquiry-title">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="mb-4">
                <p class="eyebrow mb-1">We are here to help</p>
                <h2 class="section-title mb-2" id="submit-enquiry-title">Submit an Enquiry</h2>
                <p class="mb-0">Send us your accommodation question and our team will get back to you.</p>
            </div>

            <?php if ($notification): ?>
                <div role="status"><?= $notification ?></div>
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

            <form method="post" class="card contact-form-card enquiry-form-card p-4 p-md-5">
                <?= csrf() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="enquiry-name">Full Name</label>
                        <input id="enquiry-name" class="form-control" name="name" maxlength="100" autocomplete="name" required value="<?= e($fields['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="enquiry-email">Email Address</label>
                        <input id="enquiry-email" type="email" class="form-control" name="email" maxlength="120" autocomplete="email" required value="<?= e($fields['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="enquiry-phone">Telephone Number</label>
                        <input id="enquiry-phone" type="tel" class="form-control" name="phone" maxlength="30" autocomplete="tel" required value="<?= e($fields['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="enquiry-category">Enquiry Category</label>
                        <select id="enquiry-category" class="form-select" name="category" required>
                            <option value="">Choose a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e($category) ?>" <?= $fields['category'] === $category ? 'selected' : '' ?>>
                                    <?= e($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="enquiry-subject">Subject</label>
                        <input id="enquiry-subject" class="form-control" name="subject" maxlength="150" required value="<?= e($fields['subject']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="enquiry-message">Message</label>
                        <textarea id="enquiry-message" class="form-control" name="message" rows="5" maxlength="5000" required><?= e($fields['message']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-brand" type="submit">Send Enquiry</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>

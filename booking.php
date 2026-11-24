<?php
require 'config.php';
send_security_headers();

$title = 'Book Student Accommodation - Banya Hostels';
$desc = 'Request a student accommodation room at Banya Hostels in Fort Portal. Explore available accommodation and submit your booking request.';
$message = '';
$bookingSubmitted = false;
$bookingReference = '';
$institution = 'Mountains of the Moon University';
$paymentBankName = setting('bank_name');
$paymentBankAccount = setting('bank_account_no');
$paymentMobileMoney = setting('mobile_money');
$paymentCashInstructions = setting('cash_instructions');

if ($paymentBankName === '' || strpos($paymentBankName, '[') === 0) {
    $paymentBankName = 'Centenary Bank';
}
if ($paymentBankAccount === '' || strpos($paymentBankAccount, '[') === 0) {
    $paymentBankAccount = '5620004830';
}
if ($paymentMobileMoney === '' || strpos($paymentMobileMoney, '[') === 0) {
    $paymentMobileMoney = '0787270383';
}
if ($paymentCashInstructions === '') {
    $paymentCashInstructions = 'Hostel management office';
}

$bookingHostels = $pdo->query("
    SELECT h.id, h.name, h.description, h.facilities,
        SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) AS available_rooms
    FROM hostels h
    LEFT JOIN rooms r ON r.hostel_id = h.id
    WHERE h.status = 'open'
    GROUP BY h.id, h.name, h.description, h.facilities
    ORDER BY h.id
")->fetchAll();

$pricingByHostel = [];
$pricingRows = $pdo->query("
    SELECT p.id, p.hostel_id, p.label, p.amount, p.slots, p.note
    FROM pricing p
    JOIN hostels h ON h.id = p.hostel_id
    WHERE h.status = 'open' AND p.slots BETWEEN 1 AND 2
    ORDER BY p.hostel_id, p.amount DESC
")->fetchAll();
foreach ($pricingRows as $pricingRow) {
    $pricingByHostel[(int) $pricingRow['hostel_id']][] = $pricingRow;
}

$initialHostelId = isset($_GET['hostel']) ? (int) $_GET['hostel'] : 0;
$form = [
    'hostel_id' => (string) $initialHostelId,
    'room_id' => '',
    'pricing_id' => '',
    'occupants' => '1',
    'name' => '',
    'reg_no' => '',
    'course' => '',
    'email' => '',
    'phone' => '',
    'check_in_date' => date('Y-m-d'),
    'stay_duration_months' => '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $message = '<div class="alert alert-danger" role="alert">Your session has expired. Refresh the page and submit your request again.</div>';
    } else {
        $field = static function (string $key): string {
            $value = $_POST[$key] ?? '';
            return is_string($value) ? trim($value) : '';
        };
        foreach ($form as $key => $unused) {
            $form[$key] = $field($key);
        }
        $errors = [];

        if ($form['name'] === '' || $form['reg_no'] === '' || $form['phone'] === '') {
            $errors[] = 'Enter your full name, MMU registration number and telephone number.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        foreach ([
            'name' => 100,
            'reg_no' => 40,
            'course' => 100,
            'email' => 120,
            'phone' => 30,
        ] as $key => $maxLength) {
            $length = function_exists('mb_strlen')
                ? mb_strlen($form[$key], 'UTF-8')
                : strlen($form[$key]);
            if ($length > $maxLength) {
                $errors[] = ucfirst(str_replace('_', ' ', $key)) . ' must be ' . $maxLength . ' characters or fewer.';
            }
        }
        if (($_POST['agree'] ?? null) !== '1') {
            $errors[] = 'Confirm that you are an MMU student and that your details are correct.';
        }

        $checkIn = DateTime::createFromFormat('!Y-m-d', $form['check_in_date']);
        $dateErrors = DateTime::getLastErrors();
        if (
            !$checkIn
            || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))
            || $checkIn->format('Y-m-d') !== $form['check_in_date']
            || $form['check_in_date'] < date('Y-m-d')
        ) {
            $errors[] = 'Choose a valid check-in date that is today or later.';
        }

        $stayDuration = filter_var($form['stay_duration_months'], FILTER_VALIDATE_INT);
        if ($stayDuration === false || $stayDuration < 1 || $stayDuration > 65535) {
            $errors[] = 'Enter an expected stay duration of at least one month.';
        }

        if (strlen($form['notes']) > 5000) {
            $errors[] = 'Special requests must be 5,000 characters or fewer.';
        }

        $hostelId = filter_var($form['hostel_id'], FILTER_VALIDATE_INT);
        $roomId = filter_var($form['room_id'], FILTER_VALIDATE_INT);
        $pricingId = filter_var($form['pricing_id'], FILTER_VALIDATE_INT);
        $occupants = filter_var($form['occupants'], FILTER_VALIDATE_INT);
        $roomOption = false;

        if (
            $hostelId === false || $hostelId < 1
            || $roomId === false || $roomId < 1
            || $pricingId === false || $pricingId < 1
        ) {
            $errors[] = 'Choose an open hostel, an available room and an occupancy option.';
        } else {
            $roomQuery = $pdo->prepare("
                SELECT r.id AS room_id, p.id AS pricing_id, p.slots
                FROM rooms r
                JOIN hostels h ON h.id = r.hostel_id
                JOIN pricing p ON p.hostel_id = h.id
                WHERE r.id = ?
                    AND r.hostel_id = ?
                    AND p.id = ?
                    AND p.slots BETWEEN 1 AND 2
                    AND r.status = 'available'
                    AND h.status = 'open'
            ");
            $roomQuery->execute([$roomId, $hostelId, $pricingId]);
            $roomOption = $roomQuery->fetch();
            if (!$roomOption) {
                $errors[] = 'That room or occupancy option is no longer available. Please choose again.';
            }
        }

        if ($occupants === false || $occupants < 1 || ($roomOption && $occupants > (int) $roomOption['slots'])) {
            $errors[] = 'The number of occupants must fit the selected room option.';
        }
        if (
            $roomOption
            && $occupants !== false
            && $occupants >= 1
            && $occupants <= (int) $roomOption['slots']
        ) {
            $approvedQuery = $pdo->prepare("
                SELECT COALESCE(SUM(occupants), 0) AS occupants,
                    COALESCE(SUM(CASE WHEN pricing_id <> ? THEN 1 ELSE 0 END), 0) AS different_pricing
                FROM bookings
                WHERE room_id = ? AND status = 'approved'
            ");
            $approvedQuery->execute([$pricingId, $roomId]);
            $approvedUsage = $approvedQuery->fetch();
            if ((int) $approvedUsage['different_pricing'] > 0) {
                $errors[] = 'This room already has an approved booking with a different occupancy option. Choose another room.';
            } elseif ((int) $approvedUsage['occupants'] + $occupants > (int) $roomOption['slots']) {
                $errors[] = 'This room no longer has enough capacity for your request. Choose another room.';
            }
        }

        if ($errors) {
            $message = '<div class="alert alert-danger" role="alert"><ul class="mb-0">';
            foreach (array_unique($errors) as $error) {
                $message .= '<li>' . e($error) . '</li>';
            }
            $message .= '</ul></div>';
        } else {
            $reference = 'BH' . strtoupper(bin2hex(random_bytes(5)));
            $insert = $pdo->prepare("
                INSERT INTO bookings (
                    ref, room_id, pricing_id, name, reg_no, institution,
                    course, email, phone, check_in_date,
                    stay_duration_months, occupants, notes
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $reference,
                $roomId,
                $pricingId,
                $form['name'],
                $form['reg_no'],
                $institution,
                $form['course'],
                $form['email'],
                $form['phone'],
                $form['check_in_date'],
                $stayDuration,
                $occupants,
                $form['notes'],
            ]);

            $bookingReference = $reference;
            $bookingSubmitted = true;
        }
    }
}

include 'includes/header.php';
render_page_hero('Find Your Perfect Student Accommodation.');
?>
    <section class="booking-intro-section">
        <div class="container">
            <div class="booking-intro">
                <p class="eyebrow">Book a room</p>
                <h2>Comfortable accommodation, clear booking steps.</h2>
                <p class="mb-0">Compare the available room options below, choose a room and send your request. The administration will check availability and approve your booking before it is confirmed.</p>
            </div>
        </div>
    </section>

    <section class="booking-rooms-section section-space" aria-labelledby="available-rooms-title">
        <div class="container">
            <div class="section-heading mb-4">
                <p class="eyebrow">Explore your options</p>
                <h2 id="available-rooms-title">Available Rooms</h2>
                <p>Room availability may change while requests are being reviewed.</p>
            </div>

            <?php if (!$bookingHostels): ?>
                <div class="alert alert-info">There are no open hostels available for booking at the moment.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($bookingHostels as $hostel): ?>
                        <?php
                        $hostelId = (int) $hostel['id'];
                        $hostelPricing = $pricingByHostel[$hostelId] ?? [];
                        $facilities = preg_split('/\R+/', trim((string) $hostel['facilities']));
                        $facilities = array_filter(array_map('trim', $facilities));
                        ?>
                        <?php foreach ($hostelPricing as $price): ?>
                            <div class="col-md-6 col-xl-4">
                                <article class="booking-room-card" tabindex="0">
                                    <div class="booking-room-card-content">
                                        <p class="eyebrow"><?= e($hostel['name']) ?></p>
                                        <h3><?= e($price['label']) ?></h3>
                                        <p class="booking-room-description"><?= e($hostel['description']) ?></p>
                                        <div class="booking-room-card-reveal">
                                            <div class="booking-room-card-reveal-inner">
                                                <dl class="booking-room-details">
                                                    <div>
                                                        <dt>Occupancy</dt>
                                                        <dd>Up to <?= (int) $price['slots'] ?> student<?= (int) $price['slots'] === 1 ? '' : 's' ?></dd>
                                                    </div>
                                                    <div>
                                                        <dt>Price</dt>
                                                        <dd>
                                                            UGX <?= number_format((float) $price['amount']) ?>
                                                            <?php if ($price['note']): ?>
                                                                <small class="d-block booking-price-note"><?= e($price['note']) ?></small>
                                                            <?php endif; ?>
                                                        </dd>
                                                    </div>
                                                </dl>
                                                <?php if ($facilities): ?>
                                                    <h4>Facilities</h4>
                                                    <ul class="booking-facilities">
                                                        <?php foreach ($facilities as $facility): ?>
                                                            <li><?= e($facility) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                                <div class="booking-room-card-footer">
                                                    <?php if ((int) $hostel['available_rooms'] > 0): ?>
                                                        <span class="booking-availability is-available">
                                                            <?= (int) $hostel['available_rooms'] ?> room<?= (int) $hostel['available_rooms'] === 1 ? '' : 's' ?> available
                                                        </span>
                                                        <a
                                                            class="btn btn-brand"
                                                            href="#booking-form"
                                                            data-select-hostel="<?= $hostelId ?>"
                                                        >Book Now</a>
                                                    <?php else: ?>
                                                        <span class="booking-availability is-unavailable">Currently unavailable</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!$bookingSubmitted): ?>
    <section class="booking-form-section section-space" id="booking-form" aria-labelledby="booking-form-title">
        <div class="container">
            <div class="booking-form-layout">
                <div class="booking-form-copy">
                    <p class="eyebrow">Your request</p>
                    <h2 id="booking-form-title">Submit a Booking Request</h2>
                    <p>Provide your contact and stay details. Your request will remain pending until an administrator reviews and approves it.</p>
                    <div class="booking-policy-note">
                        <strong>MMU students</strong>
                        <span>Bookings are currently limited to Mountains of the Moon University students. Submitting this form does not guarantee a room.</span>
                    </div>
                </div>

                <div class="booking-form-card">
                    <?php if ($message): ?>
                        <?= $message ?>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                            <?= csrf() ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="hostel">Hostel</label>
                                    <select class="form-select" name="hostel_id" id="hostel" required>
                                        <option value="">Choose a hostel</option>
                                        <?php foreach ($bookingHostels as $hostel): ?>
                                            <option
                                                value="<?= (int) $hostel['id'] ?>"
                                                <?= $form['hostel_id'] === (string) $hostel['id'] ? 'selected' : '' ?>
                                            ><?= e($hostel['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="room">Available room</label>
                                    <select
                                        class="form-select"
                                        name="room_id"
                                        id="room"
                                        data-selected="<?= e($form['room_id']) ?>"
                                        required
                                        disabled
                                    >
                                        <option value="">Choose a hostel first</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="pricing">Room type and price</label>
                                    <select
                                        class="form-select"
                                        name="pricing_id"
                                        id="pricing"
                                        data-selected="<?= e($form['pricing_id']) ?>"
                                        required
                                        disabled
                                    >
                                        <option value="">Choose a hostel first</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="occupants">Number of occupants</label>
                                    <select
                                        class="form-select"
                                        name="occupants"
                                        id="occupants"
                                        data-selected="<?= e($form['occupants']) ?>"
                                        required
                                        disabled
                                    >
                                        <option value="">Choose room type first</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="booking-load-status" id="booking-load-status" role="status" aria-live="polite"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="name">Full name</label>
                                    <input class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" autocomplete="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="reg_no">MMU registration number</label>
                                    <input class="form-control" id="reg_no" name="reg_no" value="<?= e($form['reg_no']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="institution">University / Institution</label>
                                    <input class="form-control" id="institution" name="institution" value="<?= e($institution) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="course">Course (optional)</label>
                                    <input class="form-control" id="course" name="course" value="<?= e($form['course']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Telephone number</label>
                                    <input class="form-control" id="phone" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="check_in_date">Preferred check-in date</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="check_in_date"
                                        name="check_in_date"
                                        min="<?= date('Y-m-d') ?>"
                                        value="<?= e($form['check_in_date']) ?>"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="stay_duration_months">Expected stay duration (months)</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="stay_duration_months"
                                        name="stay_duration_months"
                                        min="1"
                                        max="65535"
                                        step="1"
                                        value="<?= e($form['stay_duration_months']) ?>"
                                        required
                                    >
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Special requests</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="5000"><?= e($form['notes']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="agree" id="agree" value="1" required>
                                        <label class="form-check-label" for="agree">I am a Mountains of the Moon University student and the details above are correct.</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-brand btn-lg" type="submit">Submit Booking Request</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="booking-confirmation-section section-space" aria-labelledby="booking-confirmation-title">
        <div class="container">
            <div class="booking-confirmation" role="status">
                <p class="eyebrow">Section 4: Confirmation</p>
                <h2 id="booking-confirmation-title">Your booking request is pending approval.</h2>
                <p>Your unique booking reference is <strong><?= e($bookingReference) ?></strong>. Save it to track your request. A room is not confirmed or reserved until an administrator approves it.</p>
                <p class="mb-0"><a href="<?= e(BASE_URL) ?>/track.php">Track your booking</a>. Payment is due only after approval. Use the official channels below, then submit your transaction details and receipt to the hostel office for verification.</p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="container py-5" aria-labelledby="booking-payment-info-title">
        <div class="section-heading mb-4">
            <div>
                <p class="eyebrow">After your request</p>
                <h2 class="section-title mb-0" id="booking-payment-info-title">Accommodation payments</h2>
            </div>
            <p class="mb-0">Pay only after your booking has been approved.</p>
        </div>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-4">
                <section class="payment-summary-card h-100">
                    <p class="eyebrow">Accommodation payments</p>
                    <h3 class="h4">Clear steps, secure payment</h3>
                    <p>Keep your receipt and transaction reference for verification.</p>
                    <ol class="payment-steps mb-0">
                        <li>Wait until your booking is approved.</li>
                        <li>Pay through one of the official channels.</li>
                        <li>Submit your transaction details and receipt to the hostel office.</li>
                        <li>Wait for the administration to verify your payment.</li>
                    </ol>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="payment-summary-card h-100">
                    <p class="eyebrow">Payment method information</p>
                    <h3 class="h4">Official payment channels</h3>
                    <dl class="payment-channels mb-0">
                        <dt>Mobile Money</dt>
                        <dd><?= e($paymentMobileMoney) ?></dd>
                        <dt>Bank Transfer</dt>
                        <dd><?= e($paymentBankName) ?> · <?= e(setting('bank_account_name') ?: 'Banya Hostels') ?><br>Account: <?= e($paymentBankAccount) ?></dd>
                        <dt>Cash Payment</dt>
                        <dd class="mb-0"><?= e($paymentCashInstructions) ?></dd>
                    </dl>
                </section>
            </div>
            <div class="col-lg-4">
                <section class="payment-summary-card h-100">
                    <p class="eyebrow">Payment verification</p>
                    <h3 class="h4">What happens next?</h3>
                    <p>The administration reviews your transaction and updates its status. Contact the hostel office if you need help with a submitted payment.</p>
                    <p class="payment-status-note mb-0"><strong>Possible statuses:</strong><br>Pending, Verified, Rejected or Refunded.</p>
                </section>
            </div>
        </div>
    </section>
<script>
const hostelSelect = document.getElementById('hostel');
const roomSelect = document.getElementById('room');
const pricingSelect = document.getElementById('pricing');
const occupantsSelect = document.getElementById('occupants');
const loadStatus = document.getElementById('booking-load-status');
const roomsApi = <?= json_encode(BASE_URL . '/api/rooms.php') ?>;

if (hostelSelect) {
function resetSelect(select, label) {
    select.replaceChildren(new Option(label, ''));
}

function updateOccupantOptions() {
    const selectedPricing = pricingSelect.selectedOptions[0];
    const slots = selectedPricing ? Number(selectedPricing.dataset.slots || 0) : 0;
    const requestedOccupants = occupantsSelect.dataset.selected;
    resetSelect(occupantsSelect, slots ? 'Choose number of occupants' : 'Choose room type first');
    for (let count = 1; count <= slots; count += 1) {
        occupantsSelect.add(new Option(String(count), String(count)));
    }
    occupantsSelect.disabled = slots === 0;
    if (slots) {
        occupantsSelect.value = requestedOccupants && Number(requestedOccupants) <= slots
            ? requestedOccupants
            : '1';
    }
}

async function loadRoomOptions() {
    resetSelect(roomSelect, hostelSelect.value ? 'Loading available rooms...' : 'Choose a hostel first');
    resetSelect(pricingSelect, hostelSelect.value ? 'Loading room types...' : 'Choose a hostel first');
    resetSelect(occupantsSelect, 'Choose room type first');
    roomSelect.disabled = true;
    pricingSelect.disabled = true;
    occupantsSelect.disabled = true;
    loadStatus.textContent = '';
    loadStatus.className = 'booking-load-status';

    if (!hostelSelect.value) {
        return;
    }

    try {
        const response = await fetch(
            roomsApi + '?hostel=' + encodeURIComponent(hostelSelect.value),
            { headers: { Accept: 'application/json' } }
        );
        if (!response.ok) {
            throw new Error('Room availability request failed with status ' + response.status);
        }
        const data = await response.json();
        if (!data.open) {
            resetSelect(roomSelect, 'This hostel is not open for booking');
            resetSelect(pricingSelect, 'No room options available');
            loadStatus.textContent = 'This hostel is currently not accepting booking requests.';
            return;
        }

        resetSelect(roomSelect, data.rooms.length ? 'Choose an available room' : 'No rooms available');
        data.rooms.forEach(room => roomSelect.add(new Option(String(room.room_no), String(room.id))));
        roomSelect.disabled = data.rooms.length === 0;

        resetSelect(pricingSelect, data.pricing.length ? 'Choose room type and price' : 'No room options available');
        data.pricing.forEach(price => {
            const option = new Option(
                String(price.label)
                    + ' - UGX ' + Number(price.amount).toLocaleString()
                    + (price.note ? ' (' + String(price.note) + ')' : ''),
                String(price.id)
            );
            option.dataset.slots = String(price.slots);
            pricingSelect.add(option);
        });
        pricingSelect.disabled = data.pricing.length === 0;

        const selectedRoom = roomSelect.dataset.selected;
        const selectedPricing = pricingSelect.dataset.selected;
        if (selectedRoom) {
            roomSelect.value = selectedRoom;
        }
        if (selectedPricing) {
            pricingSelect.value = selectedPricing;
        }
        updateOccupantOptions();

        if (!data.rooms.length) {
            loadStatus.textContent = 'There are no available rooms in this hostel right now.';
        } else {
            loadStatus.textContent = data.rooms.length + ' room' + (data.rooms.length === 1 ? '' : 's') + ' currently available. Availability is checked again when you submit.';
        }
    } catch (error) {
        console.error(error);
        resetSelect(roomSelect, 'Could not load rooms');
        resetSelect(pricingSelect, 'Could not load room types');
        roomSelect.disabled = true;
        pricingSelect.disabled = true;
        loadStatus.textContent = 'Room availability could not be loaded. Please refresh the page or try again shortly.';
        loadStatus.className = 'booking-load-status text-danger';
    }
}

hostelSelect.addEventListener('change', () => {
    roomSelect.dataset.selected = '';
    pricingSelect.dataset.selected = '';
    occupantsSelect.dataset.selected = '1';
    loadRoomOptions();
});
pricingSelect.addEventListener('change', () => {
    occupantsSelect.dataset.selected = '1';
    updateOccupantOptions();
});
document.querySelectorAll('[data-select-hostel]').forEach(button => {
    button.addEventListener('click', () => {
        hostelSelect.value = button.dataset.selectHostel;
        roomSelect.dataset.selected = '';
        pricingSelect.dataset.selected = '';
        occupantsSelect.dataset.selected = '1';
        loadRoomOptions();
    });
});
loadRoomOptions();
}
</script>
<?php include 'includes/footer.php'; ?>

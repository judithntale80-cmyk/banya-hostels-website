<?php
// ===== EDITABLE SITE SETTINGS AND FORM SAVE =====
require '../config.php';
require_admin();

$settings = [
    'site_name' => 'Organisation name',
    'tagline' => 'Tagline',
    'brand_color' => 'Brand colour (hex)',
    'phone' => 'Phone',
    'whatsapp' => 'WhatsApp number (digits only, e.g. 2567...)',
    'email' => 'Email',
    'address' => 'Address / location',
    'hours' => 'Office hours',
    'about' => 'About text',
    'security_info' => 'Security and services text',
    'wifi_info' => 'Wi-Fi subscription information',
    'bank_name' => 'Bank name',
    'bank_account_name' => 'Bank account name',
    'bank_account_no' => 'Bank account number',
    'mobile_money' => 'Mobile money number',
    'cash_instructions' => 'Cash payment instructions',
];
$longSettings = ['about', 'security_info', 'wifi_info'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $saveSetting = $pdo->prepare("
        INSERT INTO settings (k, v)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE v = VALUES(v)
    ");

    foreach ($settings as $key => $label) {
        $value = trim($_POST[$key] ?? '');
        if ($key === 'brand_color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            $value = '#1b4965';
        }
        $saveSetting->execute([$key, $value]);
    }

    flash('Settings saved.');
    redirect('/admin/settings.php');
}

// ===== SETTINGS FORM =====
include '_head.php';
?>
<h1 class="h3 mb-3">Site settings</h1>
<form method="post" class="card p-3">
    <?= csrf() ?>

    <?php foreach ($settings as $key => $label): ?>
        <div class="mb-3">
            <label class="form-label"><?= $label ?></label>
            <?php if (in_array($key, $longSettings, true)): ?>
                <textarea class="form-control" name="<?= $key ?>" rows="6"><?= e(setting($key)) ?></textarea>
            <?php else: ?>
                <input class="form-control" name="<?= $key ?>" value="<?= e(setting($key)) ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button class="btn btn-dark">Save settings</button>
</form>
<?php include '_foot.php'; ?>

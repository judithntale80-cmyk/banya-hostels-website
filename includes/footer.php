<!-- ===== PAGE CONTENT ENDS; SHARED SITE FOOTER ===== -->
</main>
<footer class="site-footer">
  <div class="container">
    <div class="row g-4 footer-columns">
      <div class="col-sm-6 col-lg-3">
        <a class="footer-brand" href="<?= BASE_URL ?>/index.php">
          <img src="<?= BASE_URL ?>/assets/banya_images/logo.png" alt="">
          <span><?= e(setting('site_name')) ?></span>
        </a>
        <p class="mt-3"><?= e(setting('tagline')) ?></p>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="h6">Quick Links</h2>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
          <li><a href="<?= BASE_URL ?>/gallery.php">Gallery</a></li>
          <li><a href="<?= BASE_URL ?>/enquires.php">Enquiries</a></li>
          <li><a href="<?= BASE_URL ?>/news.php">News / Blog</a></li>
          <li><a href="<?= BASE_URL ?>/contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="h6">Contact Information</h2>
        <p><i class="bi bi-geo-alt"></i> <?= e(setting('address')) ?></p>
        <p class="mb-2"><i class="bi bi-telephone"></i> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p>
        <p><i class="bi bi-envelope"></i> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="h6">Follow Us</h2>
        <p>Connect with our team for hostel updates and enquiries.</p>
        <a class="footer-social" href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', setting('whatsapp'))) ?>" aria-label="Contact Banya Hostels on WhatsApp"><i class="bi bi-whatsapp"></i><span>WhatsApp</span></a>
      </div>
    </div>
    <div class="footer-bottom">
      <small>&copy; 2026 Banya Hostel. All Rights Reserved</small>
    </div>
  </div>
</footer>
<!-- ===== SHARED JAVASCRIPT ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body></html>

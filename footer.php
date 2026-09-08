<?php
// normally naka-set na ni sa header, pero para safe kung mag-usa ra ang footer
$base = $base ?? '';
?>
<footer class="footer">

  <div class="footer-top">

    <div class="footer-brand">
      <img src="<?= e($base) ?>images/shift-logo-white-transparent.png" alt="Shift Car Rental">
      <p class="footer-tag">Car Rental</p>

      <!-- letters lang sa karon, wala pa koy icon files -->
      <div class="footer-social">
        <a href="https://www.facebook.com/sheene.prestin.55" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a>
        <a href="https://www.instagram.com/sheene_ig/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">ig</a>
        <a href="https://www.linkedin.com/in/sheene-prestin-72049726a/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">in</a>
      </div>
    </div>

    <nav class="footer-col" aria-label="Quick links">
      <h2>Quick Links</h2>
      <ul>
        <li><a href="<?= e($base) ?>vehicles.php">Vehicles</a></li>
        <li><a href="<?= e($base) ?>terms.php">Terms and Conditions</a></li>
        <li><a href="<?= e($base) ?>fees.php">Fees and Charges Guide</a></li>
        <li><a href="<?= e($base) ?>locations.php">Locations</a></li>
        <li><a href="<?= e($base) ?>reviews.php">Reviews</a></li>
        <li><a href="<?= e($base) ?>faqs.php">FAQ</a></li>
      </ul>
    </nav>

    <div class="footer-col">
      <h2>Contact</h2>
      <ul>
        <li><a href="tel:+639123456789">+63 912 345 6789</a></li>
        <li><a href="mailto:hello@shiftcarrental.ph">hello@shiftcarrental.ph</a></li>
      </ul>
    </div>

    <div class="footer-col footer-locations">
      <h2>Locations</h2>
      <p>Rizal Boulevard, Dumaguete City, Negros Oriental 6200 &middot; Mon&ndash;Sun 7:00 am &ndash; 11:00 pm</p>
      <p>Sibulan, Negros Oriental &middot; Mon&ndash;Sun 7:00 am &ndash; 6:00 pm</p>
      <p>Valencia, Negros Oriental &middot; Mon&ndash;Sun 7:00 am &ndash; 6:00 pm</p>
    </div>

  </div>

  <div class="footer-bottom">
    <p>&copy; 2026 SHIFT Car Rental &mdash; All Rights Reserved</p>
    <img class="footer-mark" src="<?= e($base) ?>images/standalone-footer-logo.png" alt="">
  </div>

</footer>

<!-- ang slider script, gi-defer para human na ang HTML pag-dagan niini -->
<script src="<?= e($base) ?>js/app.js" defer></script>

</body>
</html>

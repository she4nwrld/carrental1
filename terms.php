<?php
require 'auth.php';
require 'helpers.php';

/* pareho ra sa FAQs, native <details> gamit para walay extra JS */
$termSections = [
  'Rental Agreement' => [
    ['h' => 'Who may rent',
     'p' => 'Renters must be at least 21 years old, hold a valid driver\'s license, and present one government-issued ID at pick-up. The person named on the booking must be the primary driver and must be present when the unit is released.'],
    ['h' => 'Booking and confirmation',
     'p' => 'A submitted booking is a request, not a guarantee. Shift Car Rental confirms availability within 24 hours. A booking is only reserved once it shows as Confirmed in My Bookings.'],
    ['h' => 'Payment terms',
     'p' => 'Payment is collected on pick-up by cash, GCash or bank transfer. A refundable security deposit is also collected at pick-up and returned in full once the unit is returned in the same condition.'],
  ],
  'Use of the Vehicle' => [
    ['h' => 'Authorised drivers',
     'p' => 'Only the named renter and drivers registered with us at pick-up may operate the unit. Allowing an unregistered person to drive voids any damage coverage and ends the rental immediately.'],
    ['h' => 'Permitted use',
     'p' => 'Units may not be used for racing, driving instruction, towing, carrying goods for hire, or any unlawful purpose. Smoking inside the unit is not allowed.'],
    ['h' => 'Where you may drive',
     'p' => 'Units may be driven anywhere on Negros Island. Inter-island travel by RORO requires advance notice so paperwork can be prepared. Taking a unit off-island without notice is a breach of this agreement.'],
    ['h' => 'Fuel policy',
     'p' => 'Units are released full-tank and must be returned full-tank. Shortfalls are charged at the prevailing pump rate plus a refuelling service fee.'],
  ],
  'Cancellation and Changes' => [
    ['h' => 'Cancelling a booking',
     'p' => 'Pending bookings can be cancelled free of charge from My Bookings. Confirmed bookings may be cancelled up to 24 hours before pick-up by calling us. Cancellations inside 24 hours may forfeit the deposit.'],
    ['h' => 'Changing your dates',
     'p' => 'Date changes are subject to availability and are re-priced at the rate applying to the new dates. Promotional discounts are re-checked against the new dates and may no longer qualify.'],
    ['h' => 'Late returns',
     'p' => 'Returning a unit after the agreed return date without prior arrangement is charged at the full daily rate for each additional day, and may affect the security deposit.'],
  ],
  'Liability' => [
    ['h' => 'Damage and loss',
     'p' => 'The renter is responsible for damage, loss or theft occurring during the rental period, up to the limits set out at pick-up. All damage must be reported to Shift Car Rental before the unit is returned.'],
    ['h' => 'Traffic violations',
     'p' => 'Fines, penalties and towing charges incurred during the rental period remain the responsibility of the renter, including those issued after the unit has been returned.'],
    ['h' => 'Breakdowns',
     'p' => 'Mechanical faults not caused by misuse are our responsibility. Call the 24/7 roadside number in the glovebox and we will repair on-site or swap the unit at no cost to you.'],
  ],
];

$pageTitle = 'Terms and Conditions — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="terms-hero">
    <h1>Terms and <span>Conditions</span></h1>
    <p>The rules that apply to every Shift rental.</p>
  </section>

  <section class="faq-wrap" id="terms-list">
    <?php foreach ($termSections as $group => $items) { ?>

      <h2 class="faq-group"><?= e($group) ?></h2>

      <?php foreach ($items as $item) { ?>
        <details class="faq-item">
          <summary><?= e($item['h']) ?><span class="faq-caret" aria-hidden="true">&#8964;</span></summary>
          <p><?= e($item['p']) ?></p>
        </details>
      <?php } ?>

    <?php } ?>

    <p class="faq-still">
      Questions about these terms?
      <a href="contact.php">Contact us</a> or call <a href="tel:+639123456789">+63 912 345 6789</a>.
    </p>
  </section>

</main>

<?php require 'footer.php'; ?>

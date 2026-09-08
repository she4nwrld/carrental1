<?php
require 'auth.php';
require 'helpers.php';

$ages     = ageBrackets();
$delivery = peso(deliveryFee());

/* ang delivery fee ug ang age brackets gikan sa helpers.php,
   para dili maglahi kung usbon nako didto */
$feeSections = [
  'What the Daily Rate Covers' => [
    ['h' => 'Included in every rental',
     'p' => 'The daily rate shown on each vehicle covers the unit itself, basic insurance, unlimited mileage on Negros Island, and 24/7 roadside assistance. No booking fee or online payment surcharge is added.'],
    ['h' => 'How days are counted',
     'p' => 'A rental day runs from your pick-up date to your return date. A pick-up on the 9th with a return on the 12th is three days. The running total on the booking form always shows the exact day count being charged.'],
  ],
  'Optional Charges' => [
    ['h' => 'Delivery to your location — ' . $delivery,
     'p' => 'A flat ' . $delivery . ' delivers the unit anywhere around ' . implode(', ', pickupPoints()) . '. Tick the delivery box when booking and the fee is added to your quote.'],
    ['h' => 'Young driver fee',
     'p' => 'Drivers in the ' . $ages[0] . ' bracket pay a small young-driver fee at pick-up. Drivers ' . $ages[1] . ' and above are not charged this fee.'],
    ['h' => 'One-way rentals',
     'p' => 'Returning to a different branch than you picked up from is allowed at no extra charge. Select a different return location on the booking form.'],
  ],
  'Charges You Can Avoid' => [
    ['h' => 'Refuelling',
     'p' => 'Units are released full-tank. If a unit comes back short, the missing fuel is charged at the prevailing pump rate plus a refuelling service fee. Topping up before you return avoids this entirely.'],
    ['h' => 'Late return',
     'p' => 'Keeping a unit past the agreed return date without arranging it first is charged at the full daily rate per additional day. Call us if your plans change and we will extend the booking instead.'],
    ['h' => 'Cleaning',
     'p' => 'Normal use is expected and never charged. A cleaning fee applies only for smoking inside the unit, or for sand, mud or spills that need more than a standard wash.'],
    ['h' => 'Traffic fines',
     'p' => 'Fines and towing charges incurred during your rental are passed on at cost, including those that reach us after you have returned the unit.'],
  ],
  'Deposits and Discounts' => [
    ['h' => 'Security deposit',
     'p' => 'A refundable deposit is collected at pick-up and returned in full when the unit comes back in the same condition. The amount depends on the vehicle and is confirmed before you pay.'],
    ['h' => 'Discount codes',
     'p' => 'Codes are entered on the booking form and applied before you confirm. Some codes require a minimum rental length or a minimum number of days between booking and pick-up — the form tells you if a code does not qualify for your dates.'],
    ['h' => 'Current offers',
     'p' => 'All active codes and standing offers are listed on the Deals page along with the conditions that apply to each one.'],
  ],
];

$pageTitle = 'Fees and Charges Guide — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="fees-hero">
    <h1>Fees and <span>Charges</span></h1>
    <p>Every cost explained up front. No surprises at pick-up.</p>
  </section>

  <section class="faq-wrap" id="fees-list">
    <?php foreach ($feeSections as $group => $items) { ?>

      <h2 class="faq-group"><?= e($group) ?></h2>

      <?php foreach ($items as $item) { ?>
        <details class="faq-item">
          <summary><?= e($item['h']) ?><span class="faq-caret" aria-hidden="true">&#8964;</span></summary>
          <p><?= e($item['p']) ?></p>
        </details>
      <?php } ?>

    <?php } ?>

    <p class="faq-still">
      Not sure about a charge?
      <a href="contact.php">Contact us</a> or call <a href="tel:+639123456789">+63 912 345 6789</a>.
    </p>
  </section>

</main>

<?php require 'footer.php'; ?>

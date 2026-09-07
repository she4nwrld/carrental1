<?php
require 'auth.php';
require 'helpers.php';

/* grupo-grupo nga pangutana, native <details> ang gamit para walay extra JS */
$faqGroups = [
  'Booking & Payment' => [
    ['q' => 'How do I book a car?',
     'a' => 'Browse the vehicles page, press Book Now on the unit you like, pick your dates and locations, then submit. You will get a booking reference (e.g. SHIFT-0001) right away. We confirm your schedule before you pay anything.'],
    ['q' => 'Do I need an account to book?',
     'a' => 'Yes — a free account keeps all your bookings in one place so you can view or cancel them anytime under My Bookings.'],
    ['q' => 'When do I pay?',
     'a' => 'After we confirm your booking. Payment is on pick-up: cash, GCash or bank transfer. No online card payment is required to reserve.'],
    ['q' => 'Can I cancel my booking?',
     'a' => 'Yes, free cancellation while the booking is still pending — just open My Bookings and press Cancel. Confirmed bookings can be cancelled up to 24 hours before pick-up by calling us.'],
  ],
  'Requirements' => [
    ['q' => 'What documents do I need?',
     'a' => 'A valid driver\'s license (LTO or a foreign license in English), one government ID, and a working mobile number. Foreign visitors can drive with their home license for up to 90 days.'],
    ['q' => 'Is there a minimum driver age?',
     'a' => 'Drivers must be at least 21 years old with one year of driving experience. Drivers aged 21–24 pay a small young-driver fee at pick-up.'],
    ['q' => 'Do you require a security deposit?',
     'a' => 'A refundable security deposit is collected at pick-up and returned in full when the unit comes back in the same condition.'],
  ],
  'Pick-up & Return' => [
    ['q' => 'Can you deliver the car to my hotel?',
     'a' => 'Yes — delivery anywhere around Dumaguete, Sibulan, Valencia, Dauin or Bacong is a flat ₱500. Tick the delivery checkbox when booking.'],
    ['q' => 'Can I return the car to a different branch?',
     'a' => 'Yes, one-way rentals between our branches are allowed. Choose a different return location on the booking form.'],
    ['q' => 'What if my flight is delayed?',
     'a' => 'Send us your flight number and we monitor it. Airport meet & greet waits for delayed arrivals at no extra charge.'],
  ],
  'On the Road' => [
    ['q' => 'Is fuel included?',
     'a' => 'Units are released full-tank and returned full-tank. Refuel before returning or we charge the difference plus a small service fee.'],
    ['q' => 'What happens if the car breaks down?',
     'a' => 'Call our 24/7 roadside number in the glovebox. We repair on-site or swap the unit — renters are never left stranded.'],
    ['q' => 'Can I take the car to Siquijor or Cebu?',
     'a' => 'Inter-island trips via RORO are allowed with advance notice so we can prepare the paperwork. Tell us your route when booking.'],
  ],
];

$pageTitle = 'FAQs — Shift Car Rental';
require 'header.php';
?>

<main class="page-main">

  <section class="page-hero" id="faqs-hero">
    <h1>Frequently Asked <span>Questions</span></h1>
    <p>Everything renters usually ask before driving with Shift.</p>
  </section>

  <section class="faq-wrap" id="faq-list">
    <?php foreach ($faqGroups as $group => $items) { ?>

      <h2 class="faq-group"><?= e($group) ?></h2>

      <?php foreach ($items as $item) { ?>
        <details class="faq-item">
          <summary><?= e($item['q']) ?><span class="faq-caret" aria-hidden="true">&#8964;</span></summary>
          <p><?= e($item['a']) ?></p>
        </details>
      <?php } ?>

    <?php } ?>

    <p class="faq-still">
      Still have a question?
      <a href="contact.php">Contact us</a> or call <a href="tel:+639123456789">+63 912 345 6789</a>.
    </p>
  </section>

</main>

<?php require 'footer.php'; ?>

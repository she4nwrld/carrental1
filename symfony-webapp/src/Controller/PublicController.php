<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Car;
use App\Entity\MaintenanceRecord;
use App\Entity\Message;
use App\Entity\Promo;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CarRepository;
use App\Repository\MaintenanceRecordRepository;
use App\Repository\PromoRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class PublicController extends AbstractController
{
    public const PICKUPS = [
        'Rizal Boulevard, Dumaguete',
        'Sibulan Airport',
        'Valencia',
        'Bacong',
        'Dauin',
    ];

    public const AGES = ['21-24', '25-29', '30-64', '65+'];

    public const CATEGORIES = ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];

    public const DELIVERY_FEE = 500;

    private const HOME_BRANCHES = [
        ['name' => 'Sibulan Airport',            'note' => 'Meet & greet at arrivals',   'img' => 'images/loc-sibulan.png'],
        ['name' => 'Rizal Boulevard, Dumaguete', 'note' => 'Dumaguete City seaside hub', 'img' => 'images/loc-rizal.png'],
        ['name' => 'Valencia',                   'note' => 'Highland pick-up point',     'img' => 'images/loc-valencia.png'],
        ['name' => 'Dauin',                      'note' => 'Dive-resort coast branch',   'img' => 'images/loc-dauin.png'],
        ['name' => 'Bacong',                     'note' => 'South coast pick-up point',  'img' => 'images/loc-bacong.png'],
    ];

    private const DETAIL_BRANCHES = [
        [
            'name'  => 'Sibulan Airport',
            'note'  => 'Meet & greet at arrivals',
            'img'   => 'images/loc-sibulan.png',
            'addr'  => 'Sibulan Airport (Dumaguete Airport), Sibulan, Negros Oriental',
            'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
            'desc'  => 'Our agent waits at the arrivals exit holding a Shift Car Rental sign. Paperwork takes about ten minutes and you drive straight out of the airport parking.',
        ],
        [
            'name'  => 'Rizal Boulevard',
            'note'  => 'Dumaguete City seaside hub',
            'img'   => 'images/loc-rizal.png',
            'addr'  => 'Rizal Boulevard, Dumaguete City, Negros Oriental 6200',
            'hours' => 'Mon–Sun 7:00 am – 11:00 pm',
            'desc'  => 'Our main office along the boulevard. Walk-ins welcome, and this is the branch with the longest opening hours — perfect for late returns.',
        ],
        [
            'name'  => 'Valencia',
            'note'  => 'Highland pick-up point',
            'img'   => 'images/loc-valencia.png',
            'addr'  => 'Poblacion, Valencia, Negros Oriental',
            'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
            'desc'  => 'Starting point for Casaroro Falls, Pulangbato and the mountain roads. Pick up here if you are staying in the highlands.',
        ],
        [
            'name'  => 'Dauin',
            'note'  => 'Dive-resort coast branch',
            'img'   => 'images/loc-dauin.png',
            'addr'  => 'Poblacion, Dauin, Negros Oriental',
            'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
            'desc'  => 'Right along the dive-resort strip — handy for Apo Island trips and the sanctuaries. Pick up here if you are staying in a Dauin beach resort.',
        ],
        [
            'name'  => 'Bacong',
            'note'  => 'South coast pick-up point',
            'img'   => 'images/loc-bacong.png',
            'addr'  => 'Poblacion, Bacong, Negros Oriental',
            'hours' => 'Mon–Sun 7:00 am – 6:00 pm',
            'desc'  => 'Just south of Dumaguete on the coastal highway — a quick stop before heading to Dauin, Zamboanguita or Siaton.',
        ],
    ];

    private const GOOGLE_FEEDBACK = [
        ['name' => 'Miguel Torres', 'role' => 'Apo Island Weekender', 'when' => '2 weeks ago',
         'text' => 'The car was waiting for us right at Sibulan Airport arrivals, five minutes after landing we were already on the road to Dauin. Effortless from start to finish.'],
        ['name' => 'Anna Reyes', 'role' => 'Local Renter', 'when' => '1 month ago',
         'text' => 'The price I saw on the site was the exact price I paid. No surprise insurance add-ons, no fuel games. Best rental deal in Dumaguete, hands down.'],
        ['name' => 'James Whitmore', 'role' => 'Visitor from Australia', 'when' => '2 months ago',
         'text' => 'Flat tire on the mountain road up to Valencia. One call and the roadside team had us moving again within the hour. That kind of backup is worth everything.'],
        ['name' => 'Grace Villanueva', 'role' => 'Family Trip', 'when' => '2 months ago',
         'text' => 'Booked the Innova for a week with two kids in tow. Clean unit, cold aircon, child seat ready on pick-up. We just drove and enjoyed the island.'],
        ['name' => 'Daniel Cruz', 'role' => 'Business Traveller', 'when' => '3 months ago',
         'text' => 'Late flight into Sibulan and they still met me at arrivals. Paperwork took maybe ten minutes. This is now my default rental in Negros Oriental.'],
        ['name' => 'Sofia Lim', 'role' => 'Valencia Day Tripper', 'when' => '4 months ago',
         'text' => 'Rented the Swift for a Casaroro Falls run. Sharp handling on the climb and the tank was full. Returning it was just as painless as picking it up.'],
    ];

    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        CarRepository $carRepository,
        ReviewRepository $reviewRepository,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $today = date('Y-m-d');
        $active = (string) $request->query->get('category', 'All');
        if (!\in_array($active, self::CATEGORIES, true)) {
            $active = 'All';
        }

        $q = [
            'pickup_location' => (string) $request->query->get('pickup_location', ''),
            'return_location' => (string) $request->query->get('return_location', ''),
            'pickup_date'     => (string) $request->query->get('pickup_date', ''),
            'return_date'     => (string) $request->query->get('return_date', ''),
            'driver_age'      => (string) $request->query->get('driver_age', ''),
            'discount_code'   => strtoupper(trim((string) $request->query->get('discount_code', ''))),
            'delivery'        => $request->query->has('delivery') ? '1' : '',
        ];
        $trip = array_filter($q, static fn ($v) => $v !== '');
        $tripQs = $trip ? '&' . http_build_query($trip) : '';

        if ($dbReady) {
            if ('All' !== $active) {
                $carEntities = $carRepository->findBy(['available' => true, 'type' => $active], ['price' => 'ASC']);
            } else {
                $carEntities = $carRepository->findBy(['available' => true], ['type' => 'ASC', 'price' => 'ASC']);
            }
            $cars = array_map(fn (Car $c) => $this->carToArray($c), $carEntities);

            $liveReviews = $reviewRepository->findBy(['approved' => true], ['id' => 'DESC'], 9);
            $rvCount = \count($liveReviews);
            $rvAvg = $rvCount > 0
                ? round(array_sum(array_map(static fn (Review $r) => $r->getRating(), $liveReviews)) / $rvCount, 1)
                : 0.0;
        } else {
            $cars = $this->sampleCars();
            $liveReviews = [];
            $rvCount = 0;
            $rvAvg = 0.0;
        }

        $cards = [];
        foreach ($liveReviews as $note) {
            $cards[] = [
                'name'   => $note->getFullName(),
                'meta'   => 'Verified Renter &middot; ' . $this->reviewAgo($note->getCreatedAt()),
                'rating' => $note->getRating(),
                'text'   => $note->getReviewText(),
                'google' => false,
            ];
        }
        foreach (self::GOOGLE_FEEDBACK as $note) {
            if (\count($cards) >= 6) {
                break;
            }
            $cards[] = [
                'name'   => $note['name'],
                'meta'   => $note['role'] . ' &middot; ' . $note['when'],
                'rating' => 5,
                'text'   => $note['text'],
                'google' => true,
            ];
        }

        $blendCount = 1989 + $rvCount;
        $blendAvg = round(((4.9 * 1989) + ($rvAvg * $rvCount)) / $blendCount, 1);

        return $this->render('public/index.html.twig', array_merge($this->authContext(), [
            'cars'         => $cars,
            'reviews'      => $cards,
            'pickups'      => self::PICKUPS,
            'ages'         => self::AGES,
            'categories'   => self::CATEGORIES,
            'active'       => $active,
            'q'            => $q,
            'trip_qs'      => $tripQs,
            'delivery_fee' => self::DELIVERY_FEE,
            'branches'     => self::HOME_BRANCHES,
            'blend_avg'    => $blendAvg,
            'blend_count'  => $blendCount,
            'today'        => $today,
        ]));
    }

    #[Route('/vehicles', name: 'vehicles')]
    public function vehicles(
        Request $request,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        MaintenanceRecordRepository $maintenanceRepository,
        PromoRepository $promoRepository,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $today = date('Y-m-d');

        $active = (string) $request->query->get('category', 'All');
        if (!\in_array($active, self::CATEGORIES, true)) {
            $active = 'All';
        }

        $q = [
            'pickup_location' => (string) $request->query->get('pickup_location', ''),
            'return_location' => (string) $request->query->get('return_location', ''),
            'pickup_date'     => (string) $request->query->get('pickup_date', ''),
            'return_date'     => (string) $request->query->get('return_date', ''),
            'driver_age'      => (string) $request->query->get('driver_age', ''),
            'discount_code'   => strtoupper(trim((string) $request->query->get('discount_code', ''))),
            'delivery'        => $request->query->has('delivery') ? '1' : '',
        ];

        $days = $this->tripDays($q['pickup_date'], $q['return_date']);
        $hasDates = $days > 0;

        $trip = array_filter($q, static fn ($v) => $v !== '');
        $tripQs = $trip ? '&' . http_build_query($trip) : '';

        $livePromos = $dbReady ? $promoRepository->findLivePromos($today) : [];
        $found = $this->findPromos($livePromos, $q['discount_code']);
        $promoIssues = $this->promoIssues($found, $days, $q['pickup_date']);

        if ($dbReady) {
            if ('All' !== $active) {
                $carEntities = $carRepository->findBy(['available' => true, 'type' => $active], ['price' => 'ASC']);
            } else {
                $carEntities = $carRepository->findBy(['available' => true], ['type' => 'ASC', 'price' => 'ASC']);
            }

            if ($hasDates) {
                $allBookings = $bookingRepository->findAll();
                $openRepairs = $maintenanceRepository->findBy(['resolved' => false]);
                $carEntities = array_values(array_filter(
                    $carEntities,
                    fn (Car $c) => $this->carIsFree($allBookings, (int) $c->getId(), $q['pickup_date'], $q['return_date'])
                        && !$this->carHasRepair($openRepairs, (int) $c->getId(), $q['pickup_date'], $q['return_date'])
                ));
            }

            $cars = array_map(function (Car $c) use ($hasDates, $found, $days, $q) {
                $arr = $this->carToArray($c);
                if ($hasDates) {
                    $arr['quote'] = $this->quotePrice(
                        $found['promos'],
                        (int) $c->getPrice(),
                        $days,
                        '1' === $q['delivery'],
                        $q['pickup_date']
                    );
                }
                return $arr;
            }, $carEntities);
        } else {
            $cars = $this->sampleCars();
        }

        return $this->render('public/vehicles.html.twig', array_merge($this->authContext(), [
            'cars'         => $cars,
            'categories'   => self::CATEGORIES,
            'active'       => $active,
            'q'            => $q,
            'days'         => $days,
            'has_dates'    => $hasDates,
            'trip_qs'      => $tripQs,
            'found'        => $found,
            'promo_issues' => $promoIssues,
            'delivery_fee' => self::DELIVERY_FEE,
        ]));
    }

    #[Route('/book', name: 'book', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        CarRepository $carRepository,
        BookingRepository $bookingRepository,
        MaintenanceRecordRepository $maintenanceRepository,
        PromoRepository $promoRepository,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $today = date('Y-m-d');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login', ['next' => $request->getRequestUri()]);
        }

        $carId = (int) ($request->query->get('car_id') ?: $request->request->get('car_id', 0));
        if ($carId <= 0) {
            return $this->redirectToRoute('home');
        }

        $carEntity = $dbReady ? $carRepository->findOneBy(['id' => $carId, 'available' => true]) : null;
        if (null === $carEntity && $dbReady) {
            return $this->redirectToRoute('home', ['missing' => 1]);
        }

        $car = $carEntity ? $this->carToArray($carEntity) : $this->sampleCars()[0];

        $livePromos = $dbReady ? $promoRepository->findLivePromos($today) : [];
        $jsPromos = [];
        foreach ($livePromos as $p) {
            if (null !== $p->getCode() && '' !== $p->getCode()) {
                $jsPromos[strtoupper($p->getCode())] = [
                    'percent'   => (int) $p->getPercent(),
                    'freeDays'  => (int) $p->getFreeDays(),
                    'minDays'   => (int) $p->getMinDays(),
                    'minAdv'    => (int) $p->getMinAdvanceDays(),
                    'stackable' => (bool) $p->getStackable(),
                    'title'     => (string) $p->getTitle(),
                ];
            }
        }

        $errors = [];
        $pickLoc = (string) $request->query->get('pickup_location', '');
        $retLoc  = (string) $request->query->get('return_location', '');
        $old = [
            'pickup_location' => $pickLoc,
            'return_location' => '' !== $retLoc ? $retLoc : $pickLoc,
            'pickup_date'     => (string) $request->query->get('pickup_date', ''),
            'return_date'     => (string) $request->query->get('return_date', ''),
            'driver_age'      => (string) $request->query->get('driver_age', ''),
            'discount_code'   => strtoupper(trim((string) $request->query->get('discount_code', ''))),
            'delivery'        => $request->query->has('delivery') ? '1' : '0',
        ];

        if ($dbReady && $request->isMethod('POST')) {
            $pickupLocation = trim((string) $request->request->get('pickup_location', ''));
            $returnLocation = trim((string) $request->request->get('return_location', ''));
            $pickupDate     = trim((string) $request->request->get('pickup_date', ''));
            $returnDate     = trim((string) $request->request->get('return_date', ''));
            $driverAge      = trim((string) $request->request->get('driver_age', ''));
            $discountCode   = strtoupper(trim((string) $request->request->get('discount_code', '')));
            $delivery       = $request->request->has('delivery') ? 1 : 0;

            $old = [
                'pickup_location' => $pickupLocation,
                'return_location' => $returnLocation,
                'pickup_date'     => $pickupDate,
                'return_date'     => $returnDate,
                'driver_age'      => $driverAge,
                'discount_code'   => $discountCode,
                'delivery'        => (string) $delivery,
            ];

            if (!\in_array($pickupLocation, self::PICKUPS, true)) {
                $errors[] = 'Choose a valid pick-up location.';
            }
            if (!\in_array($returnLocation, self::PICKUPS, true)) {
                $errors[] = 'Choose a valid return location.';
            }
            if (!$this->isYmd($pickupDate)) {
                $errors[] = 'Pick-up date must be a valid date.';
            } elseif ($this->daysUntil($pickupDate) < 0) {
                $errors[] = 'Pick-up date cannot be in the past.';
            }
            if (!$this->isYmd($returnDate)) {
                $errors[] = 'Return date must be a valid date.';
            } elseif ($this->isYmd($pickupDate) && strtotime($returnDate) <= strtotime($pickupDate)) {
                $errors[] = 'Return date must be after the pick-up date.';
            }
            if (!\in_array($driverAge, self::AGES, true)) {
                $errors[] = "Choose a valid driver's age.";
            }

            if (empty($errors)) {
                $days  = $this->tripDays($pickupDate, $returnDate);
                $found = $this->findPromos($livePromos, $discountCode);
                $quote = $this->quotePrice($found['promos'], (int) $car['price'], $days, (bool) $delivery, $pickupDate);

                foreach ($found['unknown'] as $bad) {
                    $errors[] = '"' . $bad . '" is not a valid discount code.';
                }
                $errors = array_merge($errors, $quote['errors']);

                $allBookings = $bookingRepository->findAll();
                $openRepairs = $maintenanceRepository->findBy(['resolved' => false]);

                if (!$this->carIsFree($allBookings, (int) $car['id'], $pickupDate, $returnDate)) {
                    $errors[] = 'Sorry, that unit was just booked for those dates. Please pick other dates.';
                }
                if ($this->carHasRepair($openRepairs, (int) $car['id'], $pickupDate, $returnDate)) {
                    $errors[] = 'That unit is booked in for repairs during those dates. Please pick other dates.';
                }

                if (empty($errors)) {
                    $appliedCode = !empty($quote['applied']) ? implode(',', $quote['applied']) : null;
                    $billedDays  = $quote['billable_days'] ?: $days;

                    $booking = (new Booking())
                        ->setUserId($user->getId())
                        ->setCarId((int) $car['id'])
                        ->setCarName((string) $car['name'])
                        ->setCarImg((string) $car['img'])
                        ->setCarType((string) $car['type'])
                        ->setFullName($user->getFullName())
                        ->setEmail((string) $user->getEmail())
                        ->setPhone($user->getPhone())
                        ->setPickupLocation($pickupLocation)
                        ->setReturnLocation($returnLocation)
                        ->setPickupDate($pickupDate)
                        ->setReturnDate($returnDate)
                        ->setDriverAge($driverAge)
                        ->setDelivery((bool) $delivery)
                        ->setDays($billedDays)
                        ->setSubtotal($quote['subtotal'])
                        ->setDiscountCode($appliedCode)
                        ->setDiscount($quote['discount'])
                        ->setTotal($quote['total'])
                        ->setStatus('pending');

                    $em->persist($booking);
                    $em->flush();

                    return $this->redirectToRoute('book_success', ['id' => $booking->getId()]);
                }
            }
        }

        $days  = $this->tripDays($old['pickup_date'], $old['return_date']);
        $found = $this->findPromos($livePromos, $old['discount_code']);
        $quote = $this->quotePrice($found['promos'], (int) $car['price'], $days, '1' === $old['delivery'], $old['pickup_date']);

        return $this->render('public/book.html.twig', array_merge($this->authContext(), [
            'car'          => $car,
            'pickups'      => self::PICKUPS,
            'ages'         => self::AGES,
            'delivery_fee' => self::DELIVERY_FEE,
            'promos_json'  => json_encode($jsPromos, \JSON_THROW_ON_ERROR),
            'old'          => $old,
            'days'         => $days,
            'quote'        => $quote,
            'errors'       => $errors,
            'today'        => $today,
        ]));
    }

    #[Route('/book/success/{id}', name: 'book_success')]
    public function bookSuccess(
        int $id,
        BookingRepository $bookingRepository,
        CarRepository $carRepository,
    ): Response {
        $carRepository->ensureSeeded();
        $booking = $bookingRepository->find($id);
        if (null === $booking) {
            return $this->redirectToRoute('bookings');
        }

        $car = $carRepository->find($booking->getCarId());

        return $this->render('public/success.html.twig', array_merge($this->authContext(), [
            'bk'           => $booking,
            'car_price'    => $car ? $car->getPrice() : (int) round($booking->getSubtotal() / max(1, $booking->getDays())),
            'delivery_fee' => self::DELIVERY_FEE,
        ]));
    }

    #[Route('/locations', name: 'locations')]
    public function locations(CarRepository $carRepository): Response
    {
        $carRepository->ensureSeeded();

        return $this->render('public/locations.html.twig', array_merge($this->authContext(), [
            'branches'       => self::DETAIL_BRANCHES,
            'delivery_areas' => ['Bais City', 'Tanjay', 'San Jose', 'Amlan'],
        ]));
    }

    #[Route('/deals', name: 'deals')]
    public function deals(PromoRepository $promoRepository, CarRepository $carRepository): Response
    {
        $dbReady = $carRepository->ensureSeeded();
        $today = date('Y-m-d');

        if ($dbReady) {
            $promos = array_map(
                fn (Promo $p) => $this->promoToArray($p),
                $promoRepository->findLivePromos($today)
            );
        } else {
            $promos = $this->samplePromos();
        }

        return $this->render('public/deals.html.twig', array_merge($this->authContext(), [
            'promos' => $promos,
        ]));
    }

    #[Route('/reviews', name: 'reviews', methods: ['GET', 'POST'])]
    public function reviews(
        Request $request,
        ReviewRepository $reviewRepository,
        BookingRepository $bookingRepository,
        CarRepository $carRepository,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $user = $this->getUser();

        $myReview = null;
        $canReview = false;
        $errors = [];
        $flash = '';

        if ($request->query->has('reviewed')) {
            $flash = 'Thank you! Your review has been posted.';
        } elseif ($request->query->has('deleted')) {
            $flash = 'Your review has been removed.';
        }

        if ($dbReady && $user instanceof User) {
            $myReviewEntity = $reviewRepository->findOneBy(['userId' => $user->getId()]);
            if ($myReviewEntity) {
                $myReview = [
                    'rating'      => $myReviewEntity->getRating(),
                    'review_text' => $myReviewEntity->getReviewText(),
                    'approved'    => $myReviewEntity->isApproved(),
                ];
            }

            $doneCount = $bookingRepository->count([
                'userId' => $user->getId(),
                'status' => 'completed',
            ]);
            $canReview = $doneCount > 0;

            if ($request->isMethod('POST')) {
                $action = (string) $request->request->get('action', 'save_review');

                if ('delete_review' === $action) {
                    if ($myReviewEntity) {
                        $em->remove($myReviewEntity);
                        $em->flush();
                    }
                    return $this->redirectToRoute('reviews', ['deleted' => 1]);
                }

                if ('save_review' === $action) {
                    $rating = (int) $request->request->get('rating', 0);
                    $text   = trim((string) $request->request->get('review_text', ''));

                    if ($rating < 1 || $rating > 5) {
                        $errors[] = 'Please choose a star rating from 1 to 5.';
                    }
                    if ('' === $text) {
                        $errors[] = 'Please write a few words about your experience.';
                    } elseif (mb_strlen($text) > 600) {
                        $errors[] = 'Review is too long  keep it under 600 characters.';
                    }
                    if (empty($errors) && !$canReview) {
                        $errors[] = 'You can leave a review after completing a rental with us.';
                    }

                    if (empty($errors)) {
                        if (!$myReviewEntity) {
                            $myReviewEntity = (new Review())
                                ->setUserId($user->getId())
                                ->setFullName($user->getFullName())
                                ->setEmail((string) $user->getEmail())
                                ->setApproved(true);
                            $em->persist($myReviewEntity);
                        }
                        $myReviewEntity
                            ->setRating($rating)
                            ->setReviewText($text)
                            ->setCreatedAt(date('Y-m-d H:i:s'));
                        $em->flush();

                        return $this->redirectToRoute('reviews', ['reviewed' => 1]);
                    }
                }
            }
        }

        $formRating = (string) ($request->request->get('rating') ?? ($myReview['rating'] ?? '5'));
        $formText   = (string) ($request->request->get('review_text') ?? ($myReview['review_text'] ?? ''));

        if ($dbReady) {
            $approved = $reviewRepository->findBy(['approved' => true], ['id' => 'DESC']);
            $customerReviews = array_map(static fn (Review $r) => [
                'id'          => $r->getId(),
                'full_name'   => $r->getFullName(),
                'rating'      => $r->getRating(),
                'review_text' => $r->getReviewText(),
                'created_at'  => $r->getCreatedAt(),
            ], $approved);
        } else {
            $customerReviews = $this->sampleReviews();
        }

        $rvCount = \count($customerReviews);
        $rvAvg = $rvCount > 0
            ? round(array_sum(array_column($customerReviews, 'rating')) / $rvCount, 1)
            : 0.0;

        $blendCount = 1989 + $rvCount;
        $blendAvg   = round(((4.9 * 1989) + ($rvAvg * $rvCount)) / $blendCount, 1);

        return $this->render('public/reviews.html.twig', array_merge($this->authContext(), [
            'customer_reviews' => $customerReviews,
            'feedback'         => self::GOOGLE_FEEDBACK,
            'blend_avg'        => $blendAvg,
            'blend_count'      => $blendCount,
            'my_review'        => $myReview,
            'can_review'       => $canReview,
            'form_rating'      => $formRating,
            'form_text'        => $formText,
            'errors'           => $errors,
            'flash'            => $flash,
        ]));
    }

    #[Route('/faqs', name: 'faqs')]
    public function faqs(CarRepository $carRepository): Response
    {
        $carRepository->ensureSeeded();

        $faqGroups = [
            'Booking & Payment' => [
                ['q' => 'How do I book a car?',
                 'a' => 'Browse the vehicles page, press Book Now on the unit you like, pick your dates and locations, then submit. You will get a booking reference (e.g. SHIFT-0001) right away. We confirm your schedule before you pay anything.'],
                ['q' => 'Do I need an account to book?',
                 'a' => 'Yes   a free account keeps all your bookings in one place so you can view or cancel them anytime under My Bookings.'],
                ['q' => 'When do I pay?',
                 'a' => 'After we confirm your booking. Payment is on pick-up: cash, GCash or bank transfer. No online card payment is required to reserve.'],
                ['q' => 'Can I cancel my booking?',
                 'a' => 'Yes, free cancellation while the booking is still pending just open My Bookings and press Cancel. Confirmed bookings can be cancelled up to 24 hours before pick-up by calling us.'],
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
                 'a' => 'Yes delivery anywhere around Dumaguete, Sibulan, Valencia, Dauin or Bacong is a flat ₱500. Tick the delivery checkbox when booking.'],
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

        return $this->render('public/faqs.html.twig', array_merge($this->authContext(), [
            'faq_groups' => $faqGroups,
        ]));
    }

    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        CarRepository $carRepository,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $user = $this->getUser();

        $errors = [];
        $sent   = false;
        $old    = [
            'name'    => $user instanceof User ? $user->getFullName() : '',
            'email'   => '',
            'phone'   => '',
            'message' => '',
        ];

        if ($dbReady && $request->isMethod('POST')) {
            $name    = trim((string) $request->request->get('name', ''));
            $email   = trim((string) $request->request->get('email', ''));
            $phone   = trim((string) $request->request->get('phone', ''));
            $message = trim((string) $request->request->get('message', $request->request->get('body', '')));

            $old = ['name' => $name, 'email' => $email, 'phone' => $phone, 'message' => $message];

            if ('' === $name) {
                $errors[] = 'Name is required.';
            }
            if ('' === $email) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            }
            if ('' !== $phone) {
                $cleanPhone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
                if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $cleanPhone)) {
                    $errors[] = 'Enter a valid mobile number (e.g. 09171234567).';
                }
            }
            if ('' === $message) {
                $errors[] = 'Message is required.';
            }

            if (empty($errors)) {
                $msg = (new Message())
                    ->setUserId($user instanceof User ? $user->getId() : null)
                    ->setAccountName($user instanceof User ? $user->getFullName() : null)
                    ->setName($name)
                    ->setEmail($email)
                    ->setPhone('' !== $phone ? $phone : null)
                    ->setBody($message)
                    ->setIsRead(0);
                $em->persist($msg);
                $em->flush();

                $sent = true;
                $old  = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
            }
        }

        return $this->render('public/contact.html.twig', array_merge($this->authContext(), [
            'sent'   => $sent,
            'errors' => $errors,
            'old'    => $old,
        ]));
    }

    #[Route('/bookings', name: 'bookings', methods: ['GET', 'POST'])]
    public function bookings(
        Request $request,
        BookingRepository $bookingRepository,
        CarRepository $carRepository,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login', ['next' => $this->generateUrl('bookings')]);
        }

        if ($dbReady && $request->isMethod('POST')) {
            $bookingId = (int) $request->request->get('booking_id', 0);
            $bk = $bookingId > 0 ? $bookingRepository->find($bookingId) : null;

            if (null !== $bk && $bk->getUserId() === $user->getId() && \in_array($bk->getStatus(), ['pending', 'confirmed'], true)) {
                $bk->setStatus('cancelled');
                $em->flush();
                return $this->redirectToRoute('bookings', ['cancelled' => 1]);
            }

            return $this->redirectToRoute('bookings', ['notfound' => 1]);
        }

        if ($dbReady) {
            $bookings = $bookingRepository->createQueryBuilder('b')
                ->andWhere('b.userId = :uid OR b.email = :email')
                ->setParameter('uid', $user->getId())
                ->setParameter('email', (string) $user->getEmail())
                ->orderBy('b.createdAt', 'DESC')
                ->addOrderBy('b.id', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $bookings = [];
        }

        return $this->render('public/bookings.html.twig', array_merge($this->authContext(), [
            'bookings'  => $bookings,
            'cancelled' => $request->query->has('cancelled'),
            'notfound'  => $request->query->has('notfound'),
        ]));
    }

    #[Route('/profile', name: 'profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        UserRepository $userRepository,
        CarRepository $carRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login', ['next' => $this->generateUrl('profile')]);
        }

        $infoErrors = [];
        $passErrors = [];
        $old        = [];

        if ($dbReady && $request->isMethod('POST')) {
            $action = (string) $request->request->get('action', 'update_profile');

            if ('change_password' === $action) {
                $current = (string) $request->request->get('current_password', '');
                $new     = (string) $request->request->get('new_password', '');
                $confirm = (string) $request->request->get('confirm_password', '');

                if ('' === $current) {
                    $passErrors[] = 'Current password is required.';
                }
                if ('' === $new) {
                    $passErrors[] = 'New password is required.';
                } else {
                    if (\strlen($new) < 8) {
                        $passErrors[] = 'Password must be at least 8 characters.';
                    } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
                        $passErrors[] = 'Password must contain both a letter and a number.';
                    } elseif (!preg_match('/[^A-Za-z0-9]/', $new)) {
                        $passErrors[] = 'Password must contain at least 1 special character (e.g. ! @ # $ %).';
                    }
                }
                if ($new !== $confirm) {
                    $passErrors[] = 'Passwords do not match.';
                }

                if (empty($passErrors)) {
                    if (!$passwordHasher->isPasswordValid($user, $current)) {
                        $passErrors[] = 'That is not your current password.';
                    } elseif ($passwordHasher->isPasswordValid($user, $new)) {
                        $passErrors[] = 'Please choose a password different from your current one.';
                    }
                }

                if (empty($passErrors)) {
                    $user->setPassword($passwordHasher->hashPassword($user, $new));
                    $em->flush();
                    return $this->redirectToRoute('profile', ['password' => 1]);
                }
            } else {
                $fullName = trim((string) $request->request->get('full_name', ''));
                $email    = trim((string) $request->request->get('email', ''));
                $phone    = trim((string) $request->request->get('phone', ''));

                $old = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

                if ('' === $fullName) {
                    $infoErrors[] = 'Full name is required.';
                } elseif (mb_strlen($fullName) > 100) {
                    $infoErrors[] = 'Full name must be 100 characters or fewer.';
                }
                if ('' === $email) {
                    $infoErrors[] = 'Email is required.';
                } elseif (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                    $infoErrors[] = 'Enter a valid email address.';
                }
                if ('' === $phone) {
                    $infoErrors[] = 'Mobile number is required.';
                } else {
                    $cleanPhone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
                    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $cleanPhone)) {
                        $infoErrors[] = 'Enter a valid mobile number (e.g. 09171234567).';
                    }
                }

                if (empty($infoErrors)) {
                    $existing = $userRepository->findOneBy(['username' => $email]);
                    if (null !== $existing && $existing->getId() !== $user->getId()) {
                        $infoErrors[] = 'That email address is already used by another account.';
                    }
                }

                if (empty($infoErrors)) {
                    $user->setFullName($fullName);
                    $user->setEmail($email);
                    $user->setUsername($email);
                    $user->setPhone($phone);
                    $em->flush();

                    return $this->redirectToRoute('profile', ['saved' => 1]);
                }
            }
        }

        $me = [
            'full_name'  => $user->getFullName(),
            'email'      => (string) $user->getEmail(),
            'phone'      => $user->getPhone(),
            'created_at' => $user->getCreatedAt(),
        ];

        return $this->render('public/profile.html.twig', array_merge($this->authContext(), [
            'me'               => $me,
            'old'              => $old,
            'info_errors'      => $infoErrors,
            'pass_errors'      => $passErrors,
            'saved'            => $request->query->has('saved'),
            'password_changed' => $request->query->has('password'),
        ]));
    }

    #[Route('/terms', name: 'terms')]
    public function terms(CarRepository $carRepository): Response
    {
        $carRepository->ensureSeeded();

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

        return $this->render('public/terms.html.twig', array_merge($this->authContext(), [
            'term_sections' => $termSections,
        ]));
    }

    #[Route('/fees', name: 'fees')]
    public function fees(CarRepository $carRepository): Response
    {
        $carRepository->ensureSeeded();

        $ages     = self::AGES;
        $delivery = '₱' . number_format(self::DELIVERY_FEE);

        $feeSections = [
            'What the Daily Rate Covers' => [
                ['h' => 'Included in every rental',
                 'p' => 'The daily rate shown on each vehicle covers the unit itself, basic insurance, unlimited mileage on Negros Island, and 24/7 roadside assistance. No booking fee or online payment surcharge is added.'],
                ['h' => 'How days are counted',
                 'p' => 'A rental day runs from your pick-up date to your return date. A pick-up on the 9th with a return on the 12th is three days. The running total on the booking form always shows the exact day count being charged.'],
            ],
            'Optional Charges' => [
                ['h' => 'Delivery to your location — ' . $delivery,
                 'p' => 'A flat ' . $delivery . ' delivers the unit anywhere around ' . implode(', ', self::PICKUPS) . '. Tick the delivery box when booking and the fee is added to your quote.'],
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

        return $this->render('public/fees.html.twig', array_merge($this->authContext(), [
            'fee_sections' => $feeSections,
        ]));
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, CarRepository $carRepository): Response
    {
        $carRepository->ensureSeeded();

        $user = $this->getUser();
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return $this->redirectToRoute('admin_dashboard');
            }

            return $this->redirectToRoute('home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $next = (string) ($request->query->get('next') ?: $request->request->get('_target_path', ''));

        return $this->render('public/login.html.twig', array_merge($this->authContext(), [
            'last_username' => $lastUsername,
            'error'         => $error,
            'next'          => $next,
        ]));
    }

    #[Route('/login-redirect', name: 'login_redirect')]
    public function loginRedirect(): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $user->isAdmin()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/signup', name: 'signup', methods: ['GET', 'POST'])]
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        UserRepository $userRepository,
        CarRepository $carRepository,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        EntityManagerInterface $em,
    ): Response {
        $dbReady = $carRepository->ensureSeeded();

        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('home');
        }

        $errors = [];
        $old    = ['full_name' => '', 'email' => '', 'phone' => ''];

        if ($dbReady && $request->isMethod('POST')) {
            $fullName = trim((string) $request->request->get('full_name', ''));
            $email    = trim((string) $request->request->get('email', ''));
            $phone    = trim((string) $request->request->get('phone', ''));
            $password = (string) $request->request->get('password', '');
            $confirm  = (string) $request->request->get('confirm_password', '');

            $old = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

            if ('' === $fullName) {
                $errors[] = 'Full name is required.';
            } elseif (mb_strlen($fullName) > 100) {
                $errors[] = 'Full name must be 100 characters or fewer.';
            }

            if ('' === $email) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            }

            if ('' === $phone) {
                $errors[] = 'Mobile number is required.';
            } else {
                $cleanPhone = preg_replace('/[\s\-()]/', '', $phone) ?? '';
                if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $cleanPhone)) {
                    $errors[] = 'Enter a valid mobile number (e.g. 09171234567).';
                }
            }

            if ('' === $password) {
                $errors[] = 'Password is required.';
            } else {
                if (\strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                    $errors[] = 'Password must contain both a letter and a number.';
                } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
                    $errors[] = 'Password must contain at least 1 special character (e.g. ! @ # $ %).';
                }
            }

            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors) && null !== $userRepository->findOneBy(['username' => $email])) {
                $errors[] = 'An account with that email already exists.';
            }

            if (empty($errors)) {
                $user = (new User())
                    ->setUsername($email)
                    ->setEmail($email)
                    ->setFullName($fullName)
                    ->setPhone($phone)
                    ->setRole('customer')
                    ->setRoles(['ROLE_USER'])
                    ->setCreatedAt(date('Y-m-d H:i:s'));

                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $em->persist($user);
                $em->flush();

                $security->login($user, 'form_login', 'main');

                return $this->redirectToRoute('home', ['welcome' => 1]);
            }
        }

        return $this->render('public/signup.html.twig', array_merge($this->authContext(), [
            'errors' => $errors,
            'old'    => $old,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function authContext(): array
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return [
                'is_logged_in'   => true,
                'is_admin'       => $user->isAdmin(),
                'user_name'      => $user->getFullName(),
                'user_full_name' => $user->getFullName(),
                'current_user'   => $user,
            ];
        }

        return [
            'is_logged_in'   => false,
            'is_admin'       => false,
            'user_name'      => 'Guest',
            'user_full_name' => 'Guest',
            'current_user'   => null,
        ];
    }

    private function isYmd(string $s): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $s));
        return checkdate($m, $d, $y);
    }

    private function tripDays(string $from, string $to): int
    {
        if (!$this->isYmd($from) || !$this->isYmd($to)) {
            return 0;
        }
        $diff = (strtotime($to) - strtotime($from)) / 86400;
        return $diff > 0 ? (int) round($diff) : 0;
    }

    private function daysUntil(string $ymd): int
    {
        if (!$this->isYmd($ymd)) {
            return 0;
        }
        return (int) round((strtotime($ymd) - strtotime(date('Y-m-d'))) / 86400);
    }

    /**
     * @param list<Promo> $livePromos
     * @return array{promos: list<array<string, mixed>>, unknown: list<string>}
     */
    private function findPromos(array $livePromos, ?string $raw): array
    {
        $parts = preg_split('/[\s,]+/', strtoupper(trim((string) $raw)), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_values(array_unique($parts));

        if (empty($parts)) {
            return ['promos' => [], 'unknown' => []];
        }

        $byCode = [];
        foreach ($livePromos as $p) {
            if (null !== $p->getCode() && '' !== $p->getCode()) {
                $byCode[strtoupper($p->getCode())] = $this->promoToArray($p);
            }
        }

        $promos  = [];
        $unknown = [];
        foreach ($parts as $c) {
            if (isset($byCode[$c])) {
                $promos[] = $byCode[$c];
            } else {
                $unknown[] = $c;
            }
        }

        return ['promos' => $promos, 'unknown' => $unknown];
    }

    /**
     * @param list<array<string, mixed>> $promos
     * @return array<string, mixed>
     */
    private function quotePrice(array $promos, int $rate, int $days, bool $delivery, string $pickupDate = ''): array
    {
        $errors  = [];
        $applied = [];
        $lead    = '' !== $pickupDate ? $this->daysUntil($pickupDate) : null;

        if (\count($promos) > 1) {
            foreach ($promos as $p) {
                if (!(int) $p['stackable']) {
                    $errors[] = $p['code'] . ' cannot be combined with other codes.';
                }
            }
        }

        foreach ($promos as $p) {
            if ((int) $p['min_days'] > 0 && $days < (int) $p['min_days']) {
                $errors[] = $p['code'] . ' needs at least ' . $p['min_days'] . ' rental days.';
            }
            if ((int) $p['min_advance_days'] > 0 && null !== $lead && $lead < (int) $p['min_advance_days']) {
                $errors[] = $p['code'] . ' must be booked at least ' . $p['min_advance_days'] . ' days before pick-up.';
            }
        }

        $freeDays = 0;
        $percent  = 0;

        if (empty($errors) && $days > 0) {
            foreach ($promos as $p) {
                $freeDays += (int) $p['free_days'];
                $percent  += (int) $p['percent'];
                $applied[] = $p['code'];
            }
            if ($freeDays >= $days) {
                $freeDays = max(0, $days - 1);
            }
            if ($percent > 50) {
                $percent = 50;
            }
        }

        $billable = max(0, $days - $freeDays);
        $subtotal = $rate * $billable;
        $discount = (int) round($subtotal * ($percent / 100));
        $delivFee = ($delivery && $days > 0) ? self::DELIVERY_FEE : 0;
        $total    = $subtotal - $discount + $delivFee;

        return [
            'days'          => $days,
            'free_days'     => $freeDays,
            'billable_days' => $billable,
            'subtotal'      => $subtotal,
            'percent'       => $percent,
            'discount'      => $discount,
            'delivery_fee'  => $delivFee,
            'total'         => $total,
            'applied'       => $applied,
            'errors'        => $errors,
        ];
    }

    /**
     * @param array{promos: list<array<string, mixed>>, unknown: list<string>} $found
     * @return list<string>
     */
    private function promoIssues(array $found, int $days, string $pickupDate): array
    {
        $issues = [];
        foreach ($found['unknown'] as $bad) {
            $issues[] = '"' . $bad . '" is not a valid discount code.';
        }
        if ($days > 0) {
            $check = $this->quotePrice($found['promos'], 0, $days, false, $pickupDate);
            $issues = array_merge($issues, $check['errors']);
        }
        return $issues;
    }

    /**
     * @param list<Booking> $allBookings
     */
    private function carIsFree(array $allBookings, int $carId, string $from, string $to): bool
    {
        foreach ($allBookings as $b) {
            if ($b->getCarId() === $carId
                && \in_array($b->getStatus(), ['pending', 'confirmed'], true)
                && $b->getPickupDate() < $to
                && $b->getReturnDate() > $from
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param list<MaintenanceRecord> $openRepairs
     */
    private function carHasRepair(array $openRepairs, int $carId, string $from, string $to): bool
    {
        foreach ($openRepairs as $m) {
            if ($m->getCarId() === $carId
                && !$m->isResolved()
                && $m->getStartDate() < $to
                && $m->getEndDate() > $from
            ) {
                return true;
            }
        }
        return false;
    }

    private function dealTerms(Promo $deal): string
    {
        $bits = [];
        if ($deal->getMinDays() > 0) {
            $bits[] = 'Min ' . $deal->getMinDays() . '-day rental';
        }
        if ($deal->getMinAdvanceDays() > 0) {
            $bits[] = 'Book ' . $deal->getMinAdvanceDays() . '+ days ahead';
        }
        $bits[] = $deal->getStackable() ? 'Stackable with other codes' : 'Cannot be combined with other codes';
        if (null !== $deal->getExpiresAt() && '' !== $deal->getExpiresAt()) {
            $bits[] = 'Valid until ' . date('M j, Y', strtotime($deal->getExpiresAt()));
        }
        return implode(' · ', $bits);
    }

    private function reviewAgo(string $ts): string
    {
        $days = (int) floor((time() - strtotime($ts)) / 86400);
        if ($days <= 0) {
            return 'today';
        }
        if (1 === $days) {
            return 'yesterday';
        }
        if ($days < 7) {
            return $days . ' days ago';
        }
        if ($days < 14) {
            return 'last week';
        }
        if ($days < 60) {
            return floor($days / 7) . ' weeks ago';
        }
        $months = floor($days / 30);
        return $months . ' month' . (1 == $months ? '' : 's') . ' ago';
    }

    /**
     * @return array<string, mixed>
     */
    private function carToArray(Car $c): array
    {
        return [
            'id'        => $c->getId(),
            'name'      => $c->getName(),
            'type'      => $c->getType(),
            'price'     => $c->getPrice(),
            'gear'      => $c->getGear(),
            'seats'     => $c->getSeats(),
            'doors'     => $c->getDoors(),
            'bag_large' => $c->getBagLarge(),
            'bag_small' => $c->getBagSmall(),
            'bagL'      => $c->getBagLarge(),
            'bagS'      => $c->getBagSmall(),
            'kids'      => $c->getKids(),
            'aircon'    => $c->isAircon(),
            'img'       => $c->getImg(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function promoToArray(Promo $p): array
    {
        return [
            'id'               => $p->getId(),
            'code'             => $p->getCode(),
            'tag'              => $p->getTag(),
            'title'            => $p->getTitle(),
            'blurb'            => $p->getBlurb(),
            'percent'          => $p->getPercent(),
            'free_days'        => $p->getFreeDays(),
            'min_days'         => $p->getMinDays(),
            'min_advance_days' => $p->getMinAdvanceDays(),
            'stackable'        => $p->getStackable(),
            'expires_at'       => $p->getExpiresAt(),
            'terms'            => $this->dealTerms($p),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleCars(): array
    {
        return [
            [
                'id' => 1, 'name' => 'Toyota Avanza', 'type' => 'MPV', 'price' => 2800,
                'gear' => 'Manual', 'seats' => 7, 'doors' => 5, 'bag_large' => 2, 'bag_small' => 3,
                'bagL' => 2, 'bagS' => 3, 'kids' => 3, 'aircon' => true, 'img' => 'images/toyota-avanza.png',
            ],
            [
                'id' => 7, 'name' => 'Toyota Vios', 'type' => 'Sedan', 'price' => 2200,
                'gear' => 'Manual', 'seats' => 5, 'doors' => 4, 'bag_large' => 2, 'bag_small' => 2,
                'bagL' => 2, 'bagS' => 2, 'kids' => 2, 'aircon' => true, 'img' => 'images/toyota-vios.png',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function samplePromos(): array
    {
        return [
            [
                'id' => 1, 'code' => 'EARLY10', 'tag' => 'Early Bird',
                'title' => 'Book 7 days ahead, save 10%',
                'blurb' => 'Lock in your Dumaguete dates at least a week before pick-up and take 10% off the daily rate on any unit.',
                'percent' => 10, 'free_days' => 0, 'min_days' => 0, 'min_advance_days' => 7,
                'stackable' => 1, 'expires_at' => null,
                'terms' => 'Book 7+ days ahead · Stackable with other codes',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sampleReviews(): array
    {
        return [
            [
                'id' => 1, 'full_name' => 'Marco Santos', 'rating' => 5,
                'review_text' => 'Picked up the Innova at Sibulan Airport right after landing. Ice-cold aircon, super clean interior, and return was under 5 minutes.',
                'created_at' => '2026-10-29 18:12:00',
            ],
        ];
    }
}

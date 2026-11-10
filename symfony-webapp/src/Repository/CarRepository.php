<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Car;
use App\Entity\MaintenanceRecord;
use App\Entity\Message;
use App\Entity\Promo;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    private bool $checked = false;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    public function ensureSeeded(): bool
    {
        if ($this->checked) {
            return true;
        }

        try {
            $em = $this->getEntityManager();
            $conn = $em->getConnection();
            $schemaManager = $conn->createSchemaManager();

            $carColumns = $schemaManager->listTableColumns('car');
            if (!$schemaManager->tablesExist(['car', 'booking', 'promo', 'maintenance_record', 'review', 'message', 'user']) || !isset($carColumns['created_by'])) {
                $metadata = $em->getMetadataFactory()->getAllMetadata();
                if (!empty($metadata)) {
                    $schemaTool = new SchemaTool($em);
                    $schemaTool->updateSchema($metadata, true);
                }
            }

            if ($schemaManager->tablesExist(['category'])) {
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                try {
                    $conn->executeStatement('ALTER TABLE `car` DROP FOREIGN KEY `FK_773DE69D12469DE2`');
                } catch (\Throwable) {
                }
                try {
                    $conn->executeStatement('ALTER TABLE `car` DROP COLUMN `category_id`');
                } catch (\Throwable) {
                }
                $conn->executeStatement('DROP TABLE IF EXISTS `category`');
                $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            }

            $carCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM car');
            $hasLegacyCar = (int) $conn->fetchOne("SELECT COUNT(*) FROM car WHERE name = 'Toyota Hiace Commuter' OR img = 'images/montero-sport.png'");

            if (0 === $carCount || $hasLegacyCar > 0) {
                if ($hasLegacyCar > 0) {
                    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                    foreach (['booking', 'maintenance_record', 'review', 'message', 'promo', 'car', 'user'] as $tbl) {
                        $conn->executeStatement("TRUNCATE TABLE `$tbl`");
                    }
                    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                }
                $this->seedDatabase();
            } else {
                // dugang default staff account kung wala pa sa db
                $hasStaff = (int) $conn->fetchOne("SELECT COUNT(*) FROM `user` WHERE username = 'staff@shift.ph'");
                if (0 === $hasStaff) {
                    $staff = (new User())
                        ->setUsername('staff@shift.ph')
                        ->setEmail('staff@shift.ph')
                        ->setFullName('Jomar Staff')
                        ->setPhone('09182217744')
                        ->setRole('staff')
                        ->setIsActive(1)
                        ->setCreatedAt('2026-08-05 09:00:00')
                        ->setPassword(password_hash('staff123', \PASSWORD_BCRYPT));
                    $em->persist($staff);
                    $em->flush();
                }

                // butangan ug created_by ang sample cars para ma-test ang staff ownership
                $staffId = (int) $conn->fetchOne("SELECT id FROM `user` WHERE username = 'staff@shift.ph'");
                $adminId = (int) $conn->fetchOne("SELECT id FROM `user` WHERE username = 'sheene@admin.com'");
                if ($staffId > 0) {
                    $conn->executeStatement('UPDATE car SET created_by = ? WHERE created_by IS NULL AND name IN (?, ?)', [$staffId, 'Toyota Corolla Altis', 'Kia Picanto']);
                }
                if ($adminId > 0) {
                    $conn->executeStatement('UPDATE car SET created_by = ? WHERE created_by IS NULL', [$adminId]);
                }
            }

            $this->checked = true;

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function seedDatabase(): void
    {
        $em = $this->getEntityManager();

        $carsData = [
            ['Toyota Avanza', 'MPV', 2800, 'Manual', 7, 5, 2, 3, 3, true, 'images/toyota-avanza.png', true],
            ['Mitsubishi Xpander', 'MPV', 3000, 'Auto', 7, 5, 3, 2, 3, true, 'images/mitsubishi-xpander.png', true],
            ['Toyota Innova', 'MPV', 3500, 'Manual', 8, 5, 3, 3, 3, true, 'images/toyota-innova.png', true],
            ['Toyota Fortuner', 'SUV', 4500, 'Auto', 7, 5, 3, 3, 3, true, 'images/toyota-fortuner.png', true],
            ['Mitsubishi Montero Sport', 'SUV', 4300, 'Auto', 7, 5, 3, 3, 3, true, 'images/mitsubishi-montero.png', true],
            ['Ford Everest', 'SUV', 4800, 'Auto', 7, 5, 4, 2, 3, true, 'images/ford-everest.png', true],
            ['Toyota Vios', 'Sedan', 2200, 'Manual', 5, 4, 2, 2, 2, true, 'images/toyota-vios.png', true],
            ['Honda City', 'Sedan', 2400, 'Auto', 5, 4, 2, 2, 2, true, 'images/honda-city.png', true],
            ['Suzuki Swift', 'Hatchback', 1900, 'Manual', 5, 5, 1, 2, 2, true, 'images/suzuki-swift.png', true],
            ['Toyota Wigo', 'Hatchback', 1600, 'Manual', 5, 5, 1, 2, 1, true, 'images/toyota-wigo.png', true],
            ['Toyota Corolla Altis', 'Sedan', 2600, 'Auto', 5, 4, 2, 2, 2, true, 'images/toyota-corolla-altis.png', true],
            ['Kia Picanto', 'Hatchback', 1500, 'Manual', 5, 5, 1, 2, 1, true, 'images/kia-picanto.png', true],
        ];

        $carEntities = [];
        foreach ($carsData as [$name, $type, $price, $gear, $seats, $doors, $bagL, $bagS, $kids, $aircon, $img, $avail]) {
            $car = (new Car())
                ->setName($name)
                ->setType($type)
                ->setPrice($price)
                ->setGear($gear)
                ->setSeats($seats)
                ->setDoors($doors)
                ->setBagLarge($bagL)
                ->setBagSmall($bagS)
                ->setKids($kids)
                ->setAircon($aircon)
                ->setImg($img)
                ->setAvailable($avail);
            $em->persist($car);
            $carEntities[] = $car;
        }

        $admin = (new User())
            ->setUsername('sheene@admin.com')
            ->setEmail('sheene@admin.com')
            ->setFullName('Sheene Admin')
            ->setPhone('09171234567')
            ->setRole('admin')
            ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->setIsActive(1)
            ->setCreatedAt('2026-08-01 09:00:00')
            ->setPassword(password_hash('admin123', \PASSWORD_BCRYPT));
        $em->persist($admin);

        $staff = (new User())
            ->setUsername('staff@shift.ph')
            ->setEmail('staff@shift.ph')
            ->setFullName('Jomar Staff')
            ->setPhone('09182217744')
            ->setRole('staff')
            ->setRoles(['ROLE_STAFF', 'ROLE_USER'])
            ->setIsActive(1)
            ->setCreatedAt('2026-08-05 09:00:00')
            ->setPassword(password_hash('staff123', \PASSWORD_BCRYPT));
        $em->persist($staff);

        $customersData = [
            ['sheene@customer.com', 'Sheene Prestin', '09175550143', 'customer123', 1, '2026-08-14 09:15:00'],
            ['marco.santos@gmail.com', 'Marco Santos', '09178421190', 'customer123', 1, '2026-09-02 14:30:00'],
            ['angela.reyes@yahoo.com', 'Angela Reyes', '09183304821', 'customer123', 1, '2026-10-11 11:05:00'],
            ['carlo.mendoza@outlook.com', 'Carlo Mendoza', '09987126604', 'customer123', 1, '2026-07-19 16:40:00'],
            ['patricia.lim@gmail.com', 'Patricia Lim', '09176019928', 'customer123', 1, '2026-11-01 08:20:00'],
            ['kevin.bautista@gmail.com', 'Kevin Bautista', '09274198830', 'customer123', 0, '2026-09-25 19:50:00'],
        ];

        $customerEntities = [];
        foreach ($customersData as [$email, $fullName, $phone, $pwd, $active, $created]) {
            $u = (new User())
                ->setUsername($email)
                ->setEmail($email)
                ->setFullName($fullName)
                ->setPhone($phone)
                ->setRole('customer')
                ->setRoles(['ROLE_USER'])
                ->setIsActive($active)
                ->setCreatedAt($created)
                ->setPassword(password_hash($pwd, \PASSWORD_BCRYPT));
            $em->persist($u);
            $customerEntities[] = $u;
        }

        $em->flush();

        $promosData = [
            ['EARLY10', 'Early Bird', 'Book 7 days ahead, save 10%', 'Lock in your Dumaguete dates at least a week before pick-up and take 10% off the daily rate on any unit.', 10, 0, 0, 7, 1, null, 1, 1],
            ['WEEKFREE', 'Long Trip', '7+ days: 1 day free', 'Renting for a full week around Negros Oriental? Day seven is on us — enter the code when you book.', 0, 1, 7, 0, 0, null, 2, 1],
            [null, 'Airport', 'Free Sibulan Airport meet & greet', 'Landing at Sibulan? We meet you at arrivals with the keys — no airport surcharge, no shuttle bus.', 0, 0, 0, 0, 0, null, 3, 1],
            ['LOCAL5', 'Local', 'Negros Oriental resident discount', 'Show a local ID at pick-up and get 5% off weekday rentals on Hatchback and Sedan units.', 5, 0, 0, 0, 1, null, 4, 1],
        ];

        foreach ($promosData as [$code, $tag, $title, $blurb, $pct, $free, $minD, $minAdv, $stack, $exp, $sort, $act]) {
            $promo = (new Promo())
                ->setCode($code)
                ->setTag($tag)
                ->setTitle($title)
                ->setBlurb($blurb)
                ->setPercent($pct)
                ->setFreeDays($free)
                ->setMinDays($minD)
                ->setMinAdvanceDays($minAdv)
                ->setStackable($stack)
                ->setExpiresAt($exp)
                ->setSortOrder($sort)
                ->setActive($act);
            $em->persist($promo);
        }

        $bookingsData = [
            [
                $customerEntities[0]->getId(),
                $carEntities[6]->getId(),
                'Toyota Vios',
                'images/toyota-vios.png',
                'Sedan',
                'Sheene Prestin',
                'sheene@customer.com',
                '09175550143',
                date('Y-m-d', strtotime('+8 days')),
                date('Y-m-d', strtotime('+11 days')),
                'Rizal Boulevard, Dumaguete',
                'Rizal Boulevard, Dumaguete',
                '25-29',
                3,
                false,
                'EARLY10',
                6600,
                660,
                5940,
                'pending',
                date('Y-m-d H:i:s', strtotime('-2 hours')),
            ],
            [
                $customerEntities[0]->getId(),
                $carEntities[8]->getId(),
                'Suzuki Swift',
                'images/suzuki-swift.png',
                'Hatchback',
                'Sheene Prestin',
                'sheene@customer.com',
                '09175550143',
                '2026-10-14',
                '2026-10-16',
                'Sibulan Airport',
                'Rizal Boulevard, Dumaguete',
                '25-29',
                2,
                false,
                null,
                3800,
                0,
                3800,
                'completed',
                '2026-10-10 09:30:00',
            ],
            [
                $customerEntities[1]->getId(),
                $carEntities[2]->getId(),
                'Toyota Innova',
                'images/toyota-innova.png',
                'MPV',
                'Marco Santos',
                'marco.santos@gmail.com',
                '09178421190',
                date('Y-m-d', strtotime('-1 day')),
                date('Y-m-d', strtotime('+3 days')),
                'Sibulan Airport',
                'Dauin',
                '30-64',
                4,
                true,
                null,
                14000,
                0,
                14500,
                'confirmed',
                date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                $customerEntities[2]->getId(),
                $carEntities[3]->getId(),
                'Toyota Fortuner',
                'images/toyota-fortuner.png',
                'SUV',
                'Angela Reyes',
                'angela.reyes@yahoo.com',
                '09183304821',
                date('Y-m-d', strtotime('-5 days')),
                date('Y-m-d', strtotime('-2 days')),
                'Valencia',
                'Rizal Boulevard, Dumaguete',
                '30-64',
                3,
                false,
                null,
                13500,
                0,
                13500,
                'confirmed',
                date('Y-m-d H:i:s', strtotime('-6 days')),
            ],
            [
                $customerEntities[3]->getId(),
                $carEntities[7]->getId(),
                'Honda City',
                'images/honda-city.png',
                'Sedan',
                'Carlo Mendoza',
                'carlo.mendoza@outlook.com',
                '09987126604',
                '2026-10-18',
                '2026-10-22',
                'Rizal Boulevard, Dumaguete',
                'Rizal Boulevard, Dumaguete',
                '30-64',
                4,
                false,
                'EARLY10',
                9600,
                960,
                8640,
                'completed',
                '2026-10-10 11:05:00',
            ],
            [
                $customerEntities[4]->getId(),
                $carEntities[9]->getId(),
                'Toyota Wigo',
                'images/toyota-wigo.png',
                'Hatchback',
                'Patricia Lim',
                'patricia.lim@gmail.com',
                '09176019928',
                '2026-10-25',
                '2026-10-27',
                'Bacong',
                'Bacong',
                '21-24',
                2,
                false,
                null,
                3200,
                0,
                3200,
                'cancelled',
                '2026-10-23 19:45:00',
            ],
        ];

        foreach ($bookingsData as [$uId, $cId, $cName, $cImg, $cType, $fName, $email, $phone, $pDate, $rDate, $pLoc, $rLoc, $age, $days, $deliv, $code, $sub, $disc, $tot, $st, $cAt]) {
            $b = (new Booking())
                ->setUserId($uId)
                ->setCarId($cId)
                ->setCarName($cName)
                ->setCarImg($cImg)
                ->setCarType($cType)
                ->setFullName($fName)
                ->setEmail($email)
                ->setPhone($phone)
                ->setPickupDate($pDate)
                ->setReturnDate($rDate)
                ->setPickupLocation($pLoc)
                ->setReturnLocation($rLoc)
                ->setDriverAge($age)
                ->setDays($days)
                ->setDelivery($deliv)
                ->setDiscountCode($code)
                ->setSubtotal($sub)
                ->setDiscount($disc)
                ->setTotal($tot)
                ->setStatus($st)
                ->setCreatedAt($cAt);
            $em->persist($b);
        }

        $repairsData = [
            [$carEntities[0]->getId(), 'Toyota Avanza', 'MPV', 'images/toyota-avanza.png', '2026-11-20', '2026-11-23', 4500, 'Scheduled brake pad replacement and wheel alignment.', false, '2026-11-05 08:30:00'],
            [$carEntities[6]->getId(), 'Toyota Vios', 'Sedan', 'images/toyota-vios.png', '2026-10-12', '2026-10-14', 3200, 'Routine 20,000 km PMS and cabin filter service.', true, '2026-10-12 09:00:00'],
        ];

        foreach ($repairsData as [$cId, $cName, $cType, $cImg, $sDate, $eDate, $cost, $desc, $res, $cAt]) {
            $m = (new MaintenanceRecord())
                ->setCarId($cId)
                ->setCarName($cName)
                ->setCarType($cType)
                ->setCarImg($cImg)
                ->setStartDate($sDate)
                ->setEndDate($eDate)
                ->setCost($cost)
                ->setDescription($desc)
                ->setResolved($res)
                ->setCreatedAt($cAt);
            $em->persist($m);
        }

        $reviewsData = [
            [$customerEntities[1]->getId(), 'Marco Santos', 'marco.santos@gmail.com', 5, 'Picked up the Innova at Sibulan Airport right after landing. Ice-cold aircon, super clean interior, and return was under 5 minutes.', true, '2026-10-29 18:12:00'],
            [$customerEntities[2]->getId(), 'Angela Reyes', 'angela.reyes@yahoo.com', 5, 'Booked the Fortuner for a weekend trip up to Valencia and down to Dauin. Zero hassle and no hidden charges on deposit return.', true, '2026-10-24 10:45:00'],
            [$customerEntities[3]->getId(), 'Carlo Mendoza', 'carlo.mendoza@outlook.com', 5, 'Honda City was fuel-efficient and spotless along Rizal Boulevard. Will definitely rent from Shift again on our next Dumaguete visit.', true, '2026-10-19 14:20:00'],
        ];

        foreach ($reviewsData as [$uId, $fName, $email, $rating, $text, $appr, $cAt]) {
            $r = (new Review())
                ->setUserId($uId)
                ->setFullName($fName)
                ->setEmail($email)
                ->setRating($rating)
                ->setReviewText($text)
                ->setApproved($appr)
                ->setCreatedAt($cAt);
            $em->persist($r);
        }

        $messagesData = [
            ['Roberto Villanueva', 'roberto.v@company.ph', '09179901122', 'Marco Santos', 'Good day! We need two MPV units for a corporate team trip to Valencia and Dauin on November 20–22. Do you deliver to hotels along Rizal Boulevard?', 0, '2026-11-06 16:40:00'],
            ['Hannah Mae Sy', 'hannah.sy@gmail.com', '09284417709', null, 'Hi, our flight lands at Sibulan Airport at 4:30 PM. Can we pick up a Toyota Wigo directly at arrivals?', 0, '2026-11-05 22:15:00'],
            ['Angela Reyes', 'angela.reyes@yahoo.com', '09183304821', 'Angela Reyes', 'Thank you for returning my security deposit so quickly! Will definitely book again next month.', 1, '2026-10-28 09:30:00'],
        ];

        foreach ($messagesData as [$name, $email, $phone, $accName, $body, $read, $cAt]) {
            $msg = (new Message())
                ->setName($name)
                ->setEmail($email)
                ->setPhone($phone)
                ->setAccountName($accName)
                ->setBody($body)
                ->setIsRead($read)
                ->setCreatedAt($cAt);
            $em->persist($msg);
        }

        $em->flush();
    }
}

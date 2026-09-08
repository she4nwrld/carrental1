<?php

function e($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function googleMark(): string
{
    return '<svg viewBox="0 0 48 48" aria-hidden="true">'
        . '<path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.3-.1-2.6-.4-3.9z"/>'
        . '<path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>'
        . '<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.3 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>'
        . '<path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4.1 5.6l6.2 5.2C37.8 39.2 44 34.5 44 24c0-1.3-.1-2.6-.4-3.9z"/>'
        . '</svg>';
}

function specIcons(): array
{
    return [
        'passenger' => '<circle cx="12" cy="7.4" r="3.2"/><path d="M5.6 20a6.4 6.4 0 0 1 12.8 0"/>',
        'kids'      => '<circle cx="12" cy="5.6" r="2.3"/><path d="M12 7.9v5.4"/><path d="M8.6 10.4h6.8"/><path d="M9.9 20l2.1-6.7 2.1 6.7"/>',
        'doors'     => '<path d="M6.5 4h8.2a3 3 0 0 1 3 3v13H6.5z"/><path d="M9.4 12.4h2.6"/>',
        'aircon'    => '<path d="M4 7.6h8.4a2.8 2.8 0 1 0-2.8-2.8"/><path d="M4 12h13.4"/><path d="M4 16.4h8.4a2.8 2.8 0 1 1-2.8 2.8"/>',
        'bagL'      => '<rect x="4.6" y="7.8" width="14.8" height="12.2" rx="2.2"/><path d="M9.2 7.8V5.4A1.4 1.4 0 0 1 10.6 4h2.8a1.4 1.4 0 0 1 1.4 1.4v2.4"/><path d="M12 11.4v5"/>',
        'bagS'      => '<rect x="6.6" y="9.4" width="10.8" height="10.6" rx="2"/><path d="M10.1 9.4V7.3a1.4 1.4 0 0 1 1.4-1.4h1a1.4 1.4 0 0 1 1.4 1.4v2.1"/>',
        'gear'      => '<circle cx="12" cy="12" r="8.4"/><path d="M12 12l3.4-3"/><path d="M12 3.6v2"/>',
    ];
}

function spec(string $key, string $text): string
{
    $icons = specIcons();
    if (!isset($icons[$key])) return '';

    return '<span><span class="ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $icons[$key] . '</svg></span>'
        . '<span class="txt">' . e($text) . '</span></span>';
}

function lookupPromo(PDO $pdo, string $code, int $days = 0, string $pickup = '')
{
    $code = strtoupper(trim($code));
    if ($code === '') return null;

    $stmt = $pdo->prepare(
        "SELECT code, title, percent, free_days, min_days, min_advance_days
         FROM promos
         WHERE code = :code AND active = 1
           AND (expires_at IS NULL OR expires_at >= CURDATE())"
    );

    $stmt->execute([':code' => $code]);

    $promo = $stmt->fetch();
    if (!$promo) return null;

    if ($promo['min_days'] > 0 && $days < $promo['min_days']) {
        return null;
    }

    if ($promo['min_advance_days'] > 0 && $pickup !== '') {
        $daysAhead = (strtotime($pickup) - strtotime(date('Y-m-d'))) / 86400;
        if ($daysAhead < $promo['min_advance_days']) {
            return null;
        }
    }

    return $promo;
}


/* BOOKING HELPERS */

function carCategories(): array
{
    return ['All', 'Hatchback', 'Sedan', 'SUV', 'MPV'];
}

function pickupPoints(): array
{
    return ['Rizal Boulevard, Dumaguete', 'Sibulan Airport', 'Valencia', 'Bacong', 'Dauin'];
}

function ageBrackets(): array
{
    return ['21-24', '25-29', '30-64', '65+'];
}

function deliveryFee(): int
{
    return 500;
}

function peso($amount): string
{
    return '₱' . number_format((float) $amount, 0);
}

function locationImage(string $place): string
{
    $first = explode(' ', trim($place))[0];
    $slug  = strtolower(preg_replace('/[^a-z]/i', '', $first));
    return 'images/loc-' . $slug . '.png';
}


/* DATES */

function isYmd($value): bool
{
    if (!is_string($value) || $value === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

function today(): string
{
    return (new DateTime('today'))->format('Y-m-d');
}

function tripDays($pickup, $return): int
{
    if (!isYmd($pickup) || !isYmd($return)) return 0;

    $a = new DateTime($pickup . ' 00:00:00');
    $b = new DateTime($return . ' 00:00:00');
    if ($b <= $a) return 0;

    return (int) $a->diff($b)->days;
}

function daysUntil($date): int
{
    if (!isYmd($date)) return -1;

    $a    = new DateTime('today');
    $b    = new DateTime($date . ' 00:00:00');
    $diff = (int) $a->diff($b)->days;

    return $b < $a ? -$diff : $diff;
}


/* PROMO CODES */

function findPromos(PDO $pdo, ?string $raw): array
{
    $out = ['promos' => [], 'unknown' => []];

    $raw = trim((string) $raw);
    if ($raw === '') return $out;

    $codes = preg_split('/[\s,+;]+/', strtoupper($raw), -1, PREG_SPLIT_NO_EMPTY);
    $codes = array_values(array_slice(array_unique($codes), 0, 2));
    if (!$codes) return $out;

    $in   = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare(
        "SELECT * FROM promos
         WHERE code IN ($in) AND active = 1
           AND (expires_at IS NULL OR expires_at >= CURDATE())"
    );
    $stmt->execute($codes);
    $rows = $stmt->fetchAll();

    $found = array_column($rows, 'code');
    foreach ($codes as $c) {
        if (!in_array($c, $found, true)) {
            $out['unknown'][] = $c;
        }
    }

    $out['promos'] = $rows;
    return $out;
}

function promoIssues(array $promos, int $days, ?string $pickup): array
{
    $msgs = [];

    if (count($promos) > 1) {
        foreach ($promos as $p) {
            if (!(int) $p['stackable']) {
                $msgs[] = "Code {$p['code']} cannot be combined with other promo codes.";
            }
        }
    }

    foreach ($promos as $p) {
        $min = (int) $p['min_days'];
        if ($min > 0 && $days > 0 && $days < $min) {
            $msgs[] = "Code {$p['code']} needs a rental of at least {$min} days "
                    . "(your dates cover {$days}).";
        }

        $adv = (int) $p['min_advance_days'];
        if ($adv > 0 && $pickup && isYmd($pickup) && daysUntil($pickup) < $adv) {
            $msgs[] = "Code {$p['code']} must be booked at least {$adv} days before pick-up.";
        }
    }

    return array_values(array_unique($msgs));
}

function quotePrice(array $promos, int $rate, int $days, bool $delivery, ?string $pickup = null): array
{
    $q = [
        'days'          => $days,
        'billable_days' => $days,
        'subtotal'      => 0,
        'discount'      => 0,
        'delivery'      => $delivery ? deliveryFee() : 0,
        'total'         => 0,
        'notes'         => [],
        'errors'        => promoIssues($promos, $days, $pickup),
        'applied'       => [],
    ];

    if ($days < 1) return $q;

    $usable = $q['errors'] ? [] : $promos;

    $free = 0;
    foreach ($usable as $p) {
        $free += (int) $p['free_days'];
    }
    if ($free > 0) {
        $free = min($free, $days - 1);
        $q['billable_days'] = $days - $free;
        $q['notes'][] = $free . ' free day' . ($free > 1 ? 's' : '') . ' applied';
    }

    $q['subtotal'] = $rate * $q['billable_days'];

    $pct = 0;
    foreach ($usable as $p) {
        $pct += (int) $p['percent'];
    }
    if ($pct > 0) {
        $pct = min($pct, 50);
        $q['discount'] = (int) round($q['subtotal'] * $pct / 100);
        $q['notes'][]  = $pct . '% off';
    }

    foreach ($usable as $p) {
        $q['applied'][] = $p['code'];
    }

    $q['total'] = max(0, $q['subtotal'] - $q['discount']) + $q['delivery'];
    return $q;
}

function promoJson(PDO $pdo): string
{
    $rows = $pdo->query(
        "SELECT code, percent, free_days, min_days, min_advance_days, stackable
         FROM promos
         WHERE active = 1 AND code IS NOT NULL
           AND (expires_at IS NULL OR expires_at >= CURDATE())"
    )->fetchAll();

    $map = [];
    foreach ($rows as $r) {
        $map[$r['code']] = [
            'percent'   => (int) $r['percent'],
            'freeDays'  => (int) $r['free_days'],
            'minDays'   => (int) $r['min_days'],
            'minAdv'    => (int) $r['min_advance_days'],
            'stackable' => (bool) (int) $r['stackable'],
        ];
    }

    return json_encode($map, JSON_UNESCAPED_UNICODE);
}


/* AVAILABILITY */

function carIsFree(PDO $pdo, int $carId, string $pickup, string $return, ?int $ignoreBookingId = null, bool $lock = false): bool
{
    if (!isYmd($pickup) || !isYmd($return)) return false;

    $sql = "SELECT COUNT(*) FROM bookings
            WHERE car_id = ?
              AND status IN ('pending','confirmed')
              AND pickup_date < ?
              AND return_date > ?";
    $args = [$carId, $return, $pickup];

    if ($ignoreBookingId) {
        $sql   .= " AND id <> ?";
        $args[] = $ignoreBookingId;
    }

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);

    return (int) $stmt->fetchColumn() === 0;
}

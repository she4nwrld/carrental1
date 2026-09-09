<?php

// convention: null = walay sayop, string = mao ni ang error message
function validateRequired(string $value, string $label): ?string
{
    return trim($value) === '' ? "$label is required." : null;
}

function validateEmailFormat(string $value): ?string
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "Enter a valid email address.";
}

// PH mobile ra: 09xxxxxxxxx o +639xxxxxxxxx
function validatePhone(string $value): ?string
{
    // tangtangon ang space, dash ug parenthesis para dili strikto sa format
    $clean = preg_replace('/[\s\-()]/', '', $value);

    return preg_match('/^(09\d{9}|\+639\d{9})$/', $clean)
        ? null
        : "Enter a valid mobile number (e.g. 09171234567).";
}

// bisan kinsa makausab sa HTML, so tan-awon gyud kung sakto ba ang gi-post
function validateInList(string $value, string $label, array $allowed): ?string
{
    return in_array($value, $allowed, true) ? null : "Choose a valid $label.";
}

// dili pwede sobra sa gitakda nga gitas-on
function validateMaxLength(string $value, string $label, int $max): ?string
{
    return mb_strlen(trim($value)) > $max
        ? "$label must be $max characters or fewer."
        : null;
}

function validatePassword(string $value): ?string
{
    if (strlen($value) < 8) {
        return "Password must be at least 8 characters.";
    }
    // dapat naay letra ug numero, dili puro numero o puro letra
    if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
        return "Password must contain both a letter and a number.";
    }
    // dapat naay bisan usa ka special character (dili letra o numero)
    if (!preg_match('/[^A-Za-z0-9]/', $value)) {
        return "Password must contain at least 1 special character (e.g. ! @ # $ %).";
    }
    return null;
}

function validatePasswordMatch(string $password, string $confirm): ?string
{
    return $password === $confirm ? null : "Passwords do not match.";
}

// tan-awon sa database kung gamit na ang email
function emailTaken(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

// parehas sa emailTaken(), pero gilaktawan ang kaugalingong account
function emailTakenByOther(PDO $pdo, string $email, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

// mo-check kung tinuod ba nga petsa ug dili na lumabay
function validateDate(string $value, string $label): ?string {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return "$label must be a valid date.";
    }
    if ($date < new DateTime('today')) {
        return "$label cannot be in the past.";
    }
    return null;
}

// ang return kinahanglan human sa pickup
function validateDateOrder(string $pickup, string $return): ?string {
    $from = DateTime::createFromFormat('Y-m-d', $pickup);
    $to   = DateTime::createFromFormat('Y-m-d', $return);
    if (!$from || !$to) {
        return null;  // sayop na sa format, naa nay sariling error
    }
    return $to <= $from ? "Return date must be after the pick-up date." : null;
}

// pila ka adlaw ang rental, gamiton sa total
function daysBetween(string $pickup, string $return): int {
    $from = new DateTime($pickup);
    $to   = new DateTime($return);
    return (int)$from->diff($to)->days;
}

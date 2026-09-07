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
function validatePassword(string $value): ?string
{
    if (strlen($value) < 8) {
        return "Password must be at least 8 characters.";
    }
    // dapat naay letra ug numero, dili puro numero o puro letra
    if (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
        return "Password must contain both a letter and a number.";
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

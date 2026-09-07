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

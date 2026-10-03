<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $pdo = db();
    $hasAdmin = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
    $ask = static function (string $prompt): string {
        fwrite(STDOUT, $prompt);
        $answer = fgets(STDIN);
        return $answer === false ? '' : rtrim($answer, "\r\n");
    };
    $name = trim($ask($hasAdmin ? 'Administrator full name: ' : 'First administrator full name: '));
    $username = trim($ask('Username: '));
    $password = $ask('Password (minimum 10 characters): ');
    if ($name === '' || $username === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $username) || ctype_digit($username) || strlen($password) < 10) {
        throw new InvalidArgumentException('Enter a valid non-numeric username and name; password must have at least 10 characters.');
    }
    $statement = $pdo->prepare('INSERT INTO users (username, password_hash, role, full_name, is_active) VALUES (?, ?, ?, ?, 1)');
    $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'admin', $name]);
    fwrite(STDOUT, 'Administrator created with account ID #' . $pdo->lastInsertId() . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Could not create administrator: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

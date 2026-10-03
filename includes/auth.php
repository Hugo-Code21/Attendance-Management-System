<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, username, role, full_name, class_name, subject
         FROM users WHERE id = ? AND is_active = 1'
    );
    $statement->execute([$_SESSION['user']['id']]);
    $user = $statement->fetch();
    if (!$user) {
        unset($_SESSION['user']);
        return null;
    }
    $_SESSION['user'] = $user;
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('index.php');
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
    return $user;
}

function sign_in(string $identifier, string $password): bool
{
    $column = ctype_digit($identifier) ? 'id' : 'username';
    $statement = db()->prepare(
        'SELECT id, username, password_hash, role, full_name, class_name, subject
         FROM users WHERE ' . $column . ' = ? AND is_active = 1 LIMIT 1'
    );
    $statement->execute([$identifier]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    return true;
}

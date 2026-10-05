<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
| Wrapped in defined() checks so you can override any value beforehand
| (e.g. from an environment-specific file) without "already defined" warnings.
*/
defined('DB_HOST')     || define('DB_HOST', 'localhost');
defined('DB_NAME')     || define('DB_NAME', 'my_database');
defined('DB_USER')     || define('DB_USER', 'root');
defined('DB_PASSWORD') || define('DB_PASSWORD', 'your_password');

/*
|--------------------------------------------------------------------------
| Connection (lazy singleton)
|--------------------------------------------------------------------------
| The PDO connection is created on the first call and reused afterwards.
*/
function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Log the real error, but don't leak credentials to the user.
        error_log('Database connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database connection failed.');
    }

    return $connection;
}

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

/** Run any query with bound params and return the statement. */
function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch a single row (or null if nothing found). */
function fetch_one(string $sql, array $params = []): ?array
{
    $row = query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Fetch all rows. */
function fetch_all(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

/** Run INSERT/UPDATE/DELETE and return the number of affected rows. */
function execute(string $sql, array $params = []): int
{
    return query($sql, $params)->rowCount();
}

/** Run INSERT and return the new auto-increment ID. */
function insert(string $sql, array $params = []): string
{
    query($sql, $params);
    return db()->lastInsertId();
}

/** Run a callback inside a transaction; rolls back on any error. */
function transaction(callable $callback): mixed
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/*
|--------------------------------------------------------------------------
| Usage examples (uncomment to try)
|--------------------------------------------------------------------------
*/

// SELECT one row
// $user = fetch_one('SELECT * FROM users WHERE email = :email', ['email' => 'test@example.com']);

// SELECT many rows
// $users = fetch_all('SELECT * FROM users WHERE active = ?', [1]);

// INSERT
// $newId = insert('INSERT INTO users (name, email) VALUES (?, ?)', ['Budi', 'budi@example.com']);

// UPDATE
// $affected = execute('UPDATE users SET name = ? WHERE id = ?', ['Andi', 1]);

// DELETE
// $deleted = execute('DELETE FROM users WHERE id = ?', [1]);

// TRANSACTION
// transaction(function (PDO $pdo) {
//     $pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?')->execute([100, 1]);
//     $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?')->execute([100, 2]);
// });
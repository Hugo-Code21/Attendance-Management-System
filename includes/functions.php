<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your form expired. Go back, refresh the page, and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($message) ? $message : null;
}

function current_time(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE));
}

function attendance_window_open(?DateTimeImmutable $now = null): bool
{
    $now ??= current_time();
    [$openHour, $openMinute] = array_map('intval', explode(':', ATTENDANCE_OPENS_AT));
    [$closeHour, $closeMinute] = array_map('intval', explode(':', ATTENDANCE_CLOSES_AT));
    $opens = $now->setTime($openHour, $openMinute);
    $closes = $now->setTime($closeHour, $closeMinute);
    return $now >= $opens && $now < $closes;
}

function attendance_window_closed(?DateTimeImmutable $now = null): bool
{
    $now ??= current_time();
    [$closeHour, $closeMinute] = array_map('intval', explode(':', ATTENDANCE_CLOSES_AT));
    return $now >= $now->setTime($closeHour, $closeMinute);
}

function status_badge(string $status): string
{
    $class = match ($status) {
        'Hadir' => 'status-present',
        'Izin' => 'status-permission',
        'Sakit' => 'status-sick',
        'Alfa' => 'status-absent',
        default => 'status-pending',
    };
    return '<span class="status ' . $class . '">' . e($status) . '</span>';
}

function page_header(string $title): void
{
    $user = current_user();
    $flash = take_flash();
    ?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= e(APP_ICON_PATH) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="dashboard.php"><span class="brand-mark"><img src="<?= e(APP_ICON_PATH) ?>" alt=""></span><span><?= e(APP_NAME) ?></span></a>
    <div class="topbar-right">
        <?php if ($user): ?><span class="user-chip"><?= e($user['full_name']) ?><small><?= e(ucfirst($user['role'])) ?></small></span>
            <form action="logout.php" method="post" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="button button-quiet" type="submit">Sign out</button>
            </form>
        <?php endif; ?>
    </div>
</header>
<div class="app-shell">
    <?php if ($user): ?>
    <aside class="sidebar">
        <p class="nav-label">WORKSPACE</p>
        <a class="nav-link" href="dashboard.php">Overview</a>
        <?php if ($user['role'] === 'admin'): ?>
            <a class="nav-link" href="users.php">People &amp; accounts</a>
            <a class="nav-link" href="attendance.php">Attendance records</a>
        <?php elseif ($user['role'] === 'teacher'): ?>
            <a class="nav-link" href="teacher.php">Attendance</a>
        <?php else: ?>
            <a class="nav-link" href="student.php">My attendance</a>
        <?php endif; ?>
        <a class="nav-link" href="account.php">My account</a>
        <div class="sidebar-note"><span class="live-dot"></span> Manual attendance<br><strong><?= e(ATTENDANCE_OPENS_AT) ?>–<?= e(ATTENDANCE_CLOSES_AT) ?> WIB</strong></div>
    </aside>
    <?php endif; ?>
    <main class="main-content <?= $user ? '' : 'main-auth' ?>">
        <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<?php
}

function page_footer(): void
{
    ?></main>
</div>
</body>
</html>
<?php
}

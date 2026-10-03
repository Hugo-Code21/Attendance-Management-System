<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($identifier !== '' && sign_in($identifier, $password)) {
        redirect('dashboard.php');
    }
    flash('error', 'The account ID/username or password is incorrect.');
    redirect('index.php');
}

page_header('Sign in');
?>
<section class="auth-card">
    <div class="auth-icon"><img src="<?= e(APP_ICON_PATH) ?>" alt=""></div>
    <p class="eyebrow"><?= e(APP_NAME) ?> · ATTENDANCE</p>
    <h1>Welcome back</h1>
    <p class="muted">Sign in with your account ID or username.</p>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Account ID or username<input name="identifier" autocomplete="username" required autofocus></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="button button-primary button-wide" type="submit">Sign in <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-foot">Need an account? Ask your system administrator.</p>
</section>
<?php page_footer(); ?>
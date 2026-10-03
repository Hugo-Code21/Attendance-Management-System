<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $statement = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $statement->execute([$user['id']]);
    $passwordHash = $statement->fetchColumn();

    if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (strlen($newPassword) < 10) {
        $errors[] = 'Your new password must be at least 10 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'The new password and confirmation do not match.';
    }
    if (!$errors && is_string($passwordHash) && password_verify($newPassword, $passwordHash)) {
        $errors[] = 'Choose a new password that is different from your current password.';
    }

    if (!$errors) {
        $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('success', 'Your password has been changed.');
        redirect('account.php');
    }
}

page_header('My account');
?>
<section class="page-heading">
    <div><p class="eyebrow">ACCOUNT SETTINGS</p><h1>My account</h1><p class="muted">Review your sign-in details and keep your password secure.</p></div>
</section>
<section class="account-layout">
    <article class="panel account-summary"><p class="eyebrow">PROFILE</p><div class="profile-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div><h2><?= e($user['full_name']) ?></h2><span class="role-tag role-<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span><dl class="profile-list"><div><dt>Account ID</dt><dd>#<?= e($user['id']) ?></dd></div><div><dt>Username</dt><dd><?= e($user['username']) ?></dd></div><?php if ($user['class_name']): ?><div><dt>Class</dt><dd><?= e($user['class_name']) ?></dd></div><?php endif; ?><?php if ($user['subject']): ?><div><dt>Subject</dt><dd><?= e($user['subject']) ?></dd></div><?php endif; ?></dl></article>
    <article class="panel form-panel password-panel">
        <div class="panel-heading"><div><p class="eyebrow">SIGN-IN SECURITY</p><h2>Change password</h2></div></div>
        <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
            <label>New password<input type="password" name="new_password" minlength="10" autocomplete="new-password" required><small class="field-help">Use at least 10 characters.</small></label>
            <label>Confirm new password<input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></label>
            <button class="button button-primary" type="submit">Update password</button>
        </form>
    </article>
</section>
<?php page_footer(); ?>

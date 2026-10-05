<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$admin = require_role('admin');

if (($_GET['refresh'] ?? '') === '1' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $refreshRole = (string) ($_GET['role'] ?? '');
    $allowedRoles = ['admin', 'teacher', 'student'];
    $accountsQuery = 'SELECT id, username, role, full_name, class_name, subject, is_active FROM users';
    $parameters = [];
    if (in_array($refreshRole, $allowedRoles, true)) {
        $accountsQuery .= ' WHERE role = ?';
        $parameters[] = $refreshRole;
    }
    $accountsQuery .= ' ORDER BY FIELD(role, "admin", "teacher", "student"), full_name';
    $statement = db()->prepare($accountsQuery);
    $statement->execute($parameters);
    $refreshAccounts = $statement->fetchAll();
    $refreshCounts = ['admin' => 0, 'teacher' => 0, 'student' => 0];
    foreach ($refreshAccounts as $account) {
        $refreshCounts[$account['role']]++;
    }
    if ($refreshRole !== '' && in_array($refreshRole, $allowedRoles, true)) {
        $countStatement = db()->query('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
        foreach ($countStatement->fetchAll() as $roleCount) {
            $refreshCounts[$roleCount['role']] = (int) $roleCount['total'];
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode(
        ['accounts' => $refreshAccounts, 'counts' => $refreshCounts],
        JSON_THROW_ON_ERROR
    );
    exit;
}

$errors = [];
$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $statement = db()->prepare("SELECT id, username, role, full_name, class_name, subject, is_active FROM users WHERE id = ?");
    $statement->execute([$editId]);
    $editing = $statement->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete' && $id) {
        if ($id === (int) $admin['id']) {
            $errors[] = 'You cannot delete your own account.';
        } else {
            $statement = db()->prepare('SELECT role FROM users WHERE id = ?');
            $statement->execute([$id]);
            $target = $statement->fetch();
            if (!$target) {
                $errors[] = 'That account no longer exists.';
            } elseif ($target['role'] === 'admin' && (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn() <= 1) {
                $errors[] = 'The last active administrator cannot be deleted.';
            } else {
                $statement = db()->prepare('DELETE FROM users WHERE id = ?');
                $statement->execute([$id]);
                flash('success', 'Account deleted.');
                redirect('users.php');
            }
        }
    } elseif ($action === 'save') {
        $role = (string) ($_POST['role'] ?? '');
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $className = trim((string) ($_POST['class_name'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($role, ['admin', 'teacher', 'student'], true)) {
            $errors[] = 'Choose a valid account role.';
        }
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'Enter a name (up to 120 characters).';
        }
        if ($username === '' || mb_strlen($username) > 60 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username) || ctype_digit($username)) {
            $errors[] = 'Username must use 1–60 letters, numbers, dots, underscores, or hyphens, and cannot be digits only.';
        }
        if (!$id && strlen($password) < 10) {
            $errors[] = 'New accounts need a password of at least 10 characters.';
        } elseif ($password !== '' && strlen($password) < 10) {
            $errors[] = 'Passwords must be at least 10 characters.';
        }
        if ($role === 'student' && $className === '') {
            $errors[] = 'A class is required for student accounts.';
        }
        if ($role === 'teacher' && ($className === '' || $subject === '')) {
            $errors[] = 'A class and subject are required for teacher accounts.';
        }
        if ($id === (int) $admin['id'] && ($role !== 'admin' || !$active)) {
            $errors[] = 'You cannot change your own role or deactivate your account.';
        }

        if (!$errors) {
            try {
                if ($id) {
                    $oldStatement = db()->prepare('SELECT role, is_active FROM users WHERE id = ?');
                    $oldStatement->execute([$id]);
                    $old = $oldStatement->fetch();
                    if (!$old) {
                        throw new RuntimeException('That account no longer exists.');
                    }
                    if ($old['role'] === 'admin' && (int) $old['is_active'] === 1 && ($role !== 'admin' || !$active)) {
                        $activeAdmins = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
                        if ($activeAdmins <= 1) {
                            throw new RuntimeException('The last active administrator must remain active.');
                        }
                    }
                    $sql = 'UPDATE users SET username = ?, role = ?, full_name = ?, class_name = ?, subject = ?, is_active = ?';
                    $params = [$username, $role, $name, $role === 'admin' ? null : $className, $role === 'teacher' ? $subject : null, $active];
                    if ($password !== '') {
                        $sql .= ', password_hash = ?';
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sql .= ' WHERE id = ?';
                    $params[] = $id;
                    db()->prepare($sql)->execute($params);
                    if ($id === (int) $admin['id']) {
                        $_SESSION['user']['username'] = $username;
                        $_SESSION['user']['full_name'] = $name;
                    }
                } else {
                    $statement = db()->prepare('INSERT INTO users (username, password_hash, role, full_name, class_name, subject, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $name, $role === 'admin' ? null : $className, $role === 'teacher' ? $subject : null, $active]);
                }
                flash('success', $id ? 'Account updated.' : 'Account created.');
                redirect('users.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'That username is already in use.';
                } else {
                    throw $exception;
                }
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        $editing = ['id' => $id, 'username' => $username, 'role' => $role, 'full_name' => $name, 'class_name' => $className, 'subject' => $subject, 'is_active' => $active];
    }
}

$filter = (string) ($_GET['role'] ?? '');
$allowedRoles = ['admin', 'teacher', 'student'];
$accountsQuery = 'SELECT id, username, role, full_name, class_name, subject, is_active FROM users';
$accounts = db()->query($accountsQuery . ' ORDER BY FIELD(role, "admin", "teacher", "student"), full_name')->fetchAll();
$counts = ['admin' => 0, 'teacher' => 0, 'student' => 0];
foreach ($accounts as $account) {
    $counts[$account['role']]++;
}
$filteredAccounts = $filter && in_array($filter, $allowedRoles, true)
    ? array_values(array_filter($accounts, static fn(array $account): bool => $account['role'] === $filter))
    : $accounts;

page_header('People & accounts');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">SYSTEM ADMINISTRATION</p>
        <h1>People &amp; accounts</h1>
        <p class="muted">Manage administrators, teachers, and students from one secure workspace.</p>
    </div>
    <a class="button button-primary" href="#account-form">+ Add account</a>
</section>
<section class="stat-grid">
    <article class="stat-card"><span>Administrators</span><strong data-account-count="admin"><?= $counts['admin'] ?></strong><small>Full system access</small></article>
    <article class="stat-card"><span>Teachers</span><strong data-account-count="teacher"><?= $counts['teacher'] ?></strong><small>Class-scoped access</small></article>
    <article class="stat-card"><span>Students</span><strong data-account-count="student"><?= $counts['student'] ?></strong><small>Personal attendance access</small></article>
</section>
<?php if ($errors): ?><div class="alert alert-error"><strong>Please review:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div><?php endif; ?>
<section class="panel" data-live-accounts data-refresh-url="<?= e('users.php?refresh=1&role=' . $filter) ?>" data-csrf-token="<?= e(csrf_token()) ?>" data-admin-id="<?= e($admin['id']) ?>">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">DIRECTORY</p>
            <h2>All accounts</h2>
        </div>
        <div>
        <form method="get" class="filter-form"><label class="sr-only" for="role-filter">Filter by role</label><select id="role-filter" name="role" onchange="this.form.submit()">
                <option value="">All roles</option><?php foreach ($allowedRoles as $role): ?><option value="<?= e($role) ?>" <?= $filter === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option><?php endforeach; ?>
            </select></form>
            <small class="muted" data-live-account-status aria-live="polite">Live updates every 15 seconds.</small>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Person</th>
                    <th>Account ID</th>
                    <th>Role</th>
                    <th>Class / subject</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody data-account-rows>
                <?php foreach ($filteredAccounts as $account): ?><tr>
                        <td><strong><?= e($account['full_name']) ?></strong><small class="table-sub"><?= e($account['username']) ?></small></td>
                        <td>#<?= e($account['id']) ?></td>
                        <td><span class="role-tag role-<?= e($account['role']) ?>"><?= e(ucfirst($account['role'])) ?></span></td>
                        <td><?= e($account['class_name'] ?: '—') ?><?= $account['subject'] ? '<small class="table-sub">' . e($account['subject']) . '</small>' : '' ?></td>
                        <td><span class="status <?= $account['is_active'] ? 'status-present' : 'status-absent' ?>"><?= $account['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td class="row-actions"><a class="text-link" href="users.php?edit=<?= e($account['id']) ?>">Edit</a>
                            <?php if ((int) $account['id'] !== (int) $admin['id']): ?><form method="post" class="inline-form" data-confirm="Delete this account? Its attendance history will also be removed."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($account['id']) ?>"><button class="text-link text-danger" type="submit">Delete</button></form><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$filteredAccounts): ?><tr>
                        <td colspan="6" class="empty-cell">No accounts match this role.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel form-panel" id="account-form">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">ACCOUNT SETUP</p>
            <h2><?= $editing ? 'Edit account #' . e($editing['id']) : 'Create an account' ?></h2>
        </div><?php if ($editing): ?><a class="text-link" href="users.php">Cancel edit</a><?php endif; ?>
    </div>
    <form method="post" class="account-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($editing['id']) ?>"><?php endif; ?>
        <label>Full name<input name="full_name" maxlength="120" required value="<?= e($editing['full_name'] ?? '') ?>"></label>
        <label>Username<input name="username" maxlength="60" autocomplete="off" required value="<?= e($editing['username'] ?? '') ?>"></label>
        <label>Role<select name="role" data-role-select required><?php foreach ($allowedRoles as $role): ?><option value="<?= e($role) ?>" <?= ($editing['role'] ?? 'student') === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option><?php endforeach; ?></select></label>
        <label class="role-field" data-role-field="class_name">Class<input name="class_name" maxlength="80" value="<?= e($editing['class_name'] ?? '') ?>" placeholder="e.g. XII IPA 1"></label>
        <label class="role-field" data-role-field="subject">Subject<input name="subject" maxlength="120" value="<?= e($editing['subject'] ?? '') ?>" placeholder="e.g. Mathematics"></label>
        <label>Password<?= $editing ? ' <span class="muted">(leave blank to keep current)</span>' : '' ?><input type="password" name="password" minlength="10" autocomplete="new-password" <?= $editing ? '' : 'required' ?>></label>
        <label class="checkbox-label"><input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?>> Account active</label>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Create account' ?></button></div>
    </form>
</section>
<?php page_footer(); ?>
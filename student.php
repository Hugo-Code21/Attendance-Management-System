<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$student = require_role('student');
$today = current_time()->format('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, ['Hadir', 'Izin', 'Sakit'], true)) {
        flash('error', 'Choose Hadir, Izin, or Sakit to submit attendance.');
    } elseif (!attendance_window_open()) {
        flash('error', 'Attendance can only be submitted between ' . ATTENDANCE_OPENS_AT . ' and ' . ATTENDANCE_CLOSES_AT . ' WIB.');
    } else {
        try {
            $statement = db()->prepare("INSERT INTO student_attendance (student_id, attendance_date, status, submitted_at) VALUES (?, ?, ?, ?)");
            $statement->execute([$student['id'], $today, $status, current_time()->format('Y-m-d H:i:s')]);
            flash('success', 'Attendance submitted successfully.');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('error', 'Your attendance has already been recorded for today.');
            } else {
                throw $exception;
            }
        }
    }
    redirect('student.php');
}

$statement = db()->prepare('SELECT status, submitted_at FROM student_attendance WHERE student_id = ? AND attendance_date = ?');
$statement->execute([$student['id'], $today]);
$record = $statement->fetch();
$recentStatement = db()->prepare('SELECT attendance_date, status FROM student_attendance WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 7');
$recentStatement->execute([$student['id']]);
$recent = $recentStatement->fetchAll();
$open = attendance_window_open();
$closed = attendance_window_closed();

page_header('My attendance');
?>
<section class="page-heading">
    <div><p class="eyebrow">STUDENT PORTAL</p><h1>Good day, <?= e(explode(' ', trim($student['full_name']))[0]) ?>.</h1><p class="muted">Review your details and record today's attendance.</p></div>
    <span class="date-pill"><?= e(current_time()->format('l, d M Y')) ?></span>
</section>
<div class="student-layout">
    <section class="panel attendance-panel">
        <div class="panel-heading"><div><p class="eyebrow">TODAY'S ATTENDANCE</p><h2>Daily check-in</h2></div><span class="window-indicator <?= $open ? 'window-open' : '' ?>"><span class="live-dot"></span><?= $open ? 'Window open' : ($closed ? 'Window closed' : 'Not open yet') ?></span></div>
        <p class="window-copy">Submit between <strong><?= e(ATTENDANCE_OPENS_AT) ?> and <?= e(ATTENDANCE_CLOSES_AT) ?> WIB</strong>. Your status cannot be changed after submission.</p>
        <?php if ($record): ?>
            <div class="submitted-card"><div><small>Recorded status</small><p><?= status_badge($record['status']) ?></p></div><div><small>Submitted at</small><strong><?= e((new DateTimeImmutable($record['submitted_at']))->format('H:i')) ?> WIB</strong></div></div>
        <?php elseif ($open): ?>
            <form method="post" class="form-stack attendance-form" data-confirm="Submit this attendance status? You cannot change it later.">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Your name<input value="<?= e($student['full_name']) ?>" readonly></label>
                <label>Class<input value="<?= e($student['class_name'] ?: 'Not assigned') ?>" readonly></label>
                <label>Attendance status<select name="status" required><option value="">Choose your status</option><option>Hadir</option><option>Izin</option><option>Sakit</option></select></label>
                <button class="button button-primary button-wide" type="submit">Submit attendance <span aria-hidden="true">→</span></button>
            </form>
        <?php else: ?>
            <div class="locked-card"><span class="lock-icon"><?= $closed ? '✓' : '◷' ?></span><div><strong><?= $closed ? 'Check-in is closed' : 'Check-in has not opened yet' ?></strong><p><?= $closed ? "No attendance is created automatically. Submit the form during tomorrow's attendance window." : 'Your attendance form will be available starting at ' . e(ATTENDANCE_OPENS_AT) . ' WIB.' ?></p></div></div>
        <?php endif; ?>
    </section>
    <aside class="panel profile-panel"><p class="eyebrow">YOUR PROFILE</p><h2>Student details</h2><dl class="profile-list"><div><dt>Student ID</dt><dd>#<?= e($student['id']) ?></dd></div><div><dt>Full name</dt><dd><?= e($student['full_name']) ?></dd></div><div><dt>Username</dt><dd><?= e($student['username']) ?></dd></div><div><dt>Class</dt><dd><?= e($student['class_name'] ?: 'Not assigned') ?></dd></div></dl><p class="profile-help">Contact an administrator if any of your details need to be updated.</p></aside>
</div>
<section class="panel">
    <div class="panel-heading"><div><p class="eyebrow">PERSONAL RECORD</p><h2>Recent attendance</h2></div></div>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Status</th></tr></thead><tbody><?php foreach ($recent as $item): ?><tr><td><?= e((new DateTimeImmutable($item['attendance_date']))->format('D, d M Y')) ?></td><td><?= status_badge($item['status']) ?></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td colspan="2" class="empty-cell">No attendance records yet.</td></tr><?php endif; ?></tbody></table></div>
</section>
<?php page_footer(); ?>

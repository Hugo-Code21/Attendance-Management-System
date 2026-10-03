<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$teacher = require_role('teacher');

$today = current_time()->format('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, ['Hadir', 'Izin', 'Sakit'], true)) {
        flash('error', 'Choose Hadir, Izin, or Sakit to submit your attendance.');
    } elseif (!attendance_window_open()) {
        flash('error', 'Attendance can only be submitted between ' . ATTENDANCE_OPENS_AT . ' and ' . ATTENDANCE_CLOSES_AT . ' WIB.');
    } else {
        try {
            $statement = db()->prepare('INSERT INTO teacher_attendance (teacher_id, attendance_date, status, submitted_at) VALUES (?, ?, ?, ?)');
            $statement->execute([$teacher['id'], $today, $status, current_time()->format('Y-m-d H:i:s')]);
            flash('success', 'Your attendance was submitted successfully.');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash('error', 'Your attendance has already been recorded for today.');
            } else {
                throw $exception;
            }
        }
    }
    redirect('teacher.php');
}

$dateInput = (string) ($_GET['date'] ?? current_time()->format('Y-m-d'));
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateInput);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $dateInput || $dateInput > current_time()->format('Y-m-d')) {
    $dateInput = current_time()->format('Y-m-d');
}
$statement = db()->prepare(
    "SELECT u.id, u.full_name, u.username, u.class_name, a.status, a.submitted_at
     FROM users u
     LEFT JOIN student_attendance a ON a.student_id = u.id AND a.attendance_date = ?
     WHERE u.role = 'student' AND u.is_active = 1 AND u.class_name = ?
     ORDER BY u.full_name"
);
$statement->execute([$dateInput, $teacher['class_name']]);
$students = $statement->fetchAll();
$attendanceFilter = (string) ($_GET['show'] ?? '');
if (!in_array($attendanceFilter, ['', 'present', 'absent', 'awaiting'], true)) {
    $attendanceFilter = '';
}
$counts = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0, 'Belum mengisi' => 0];
foreach ($students as $student) {
    $status = $student['status'] ?? 'Belum mengisi';
    $counts[$status] = ($counts[$status] ?? 0) + 1;
}
$present = $counts['Hadir'];
$absent = $counts['Izin'] + $counts['Sakit'] + $counts['Alfa'];
$visibleStudents = array_values(array_filter(
    $students,
    static function (array $student) use ($attendanceFilter): bool {
        $status = $student['status'] ?? null;
        return match ($attendanceFilter) {
            'present' => $status === 'Hadir',
            'absent' => in_array($status, ['Izin', 'Sakit', 'Alfa'], true),
            'awaiting' => $status === null,
            default => true,
        };
    }
));
$listTitle = match ($attendanceFilter) {
    'present' => 'Present students',
    'absent' => 'Absent students',
    'awaiting' => 'Students awaiting attendance',
    default => 'Students and status',
};
$selfAttendanceStatement = db()->prepare('SELECT status, submitted_at FROM teacher_attendance WHERE teacher_id = ? AND attendance_date = ?');
$selfAttendanceStatement->execute([$teacher['id'], $today]);
$selfAttendance = $selfAttendanceStatement->fetch();
$windowOpen = attendance_window_open();

page_header('Attendance');
?>
<section class="page-heading">
    <div><p class="eyebrow">TEACHER PORTAL · <?= e($teacher['subject'] ?: 'CLASS TEACHER') ?></p><h1>Attendance</h1><p class="muted"><?= e($teacher['class_name'] ?: 'No class assigned') ?> · your assigned students and their daily attendance.</p></div>
    <form method="get" class="date-filter"><label for="attendance-date">Attendance date</label><input type="date" id="attendance-date" name="date" value="<?= e($dateInput) ?>" max="<?= e(current_time()->format('Y-m-d')) ?>"><button class="button button-outline" type="submit">View</button></form>
</section>
<section class="panel teacher-checkin">
    <div class="panel-heading"><div><p class="eyebrow">YOUR ATTENDANCE</p><h2>Teacher check-in</h2></div><span class="window-indicator <?= $windowOpen ? 'window-open' : '' ?>"><span class="live-dot"></span><?= $windowOpen ? 'Window open' : 'Window closed' ?></span></div>
    <div class="teacher-checkin-body">
        <div class="teacher-identity"><span><?= e($teacher['full_name']) ?></span><small><?= e($teacher['subject'] ?: 'Subject not assigned') ?></small></div>
        <?php if ($selfAttendance): ?><div class="submitted-card"><div><small>Recorded status</small><p><?= status_badge($selfAttendance['status']) ?></p></div><div><small>Submitted at</small><strong><?= e((new DateTimeImmutable($selfAttendance['submitted_at']))->format('H:i')) ?> WIB</strong></div></div>
        <?php elseif ($windowOpen): ?><form method="post" class="teacher-checkin-form" data-confirm="Submit this attendance status? You cannot change it later."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label class="sr-only" for="teacher-status">Attendance status</label><select id="teacher-status" name="status" required><option value="">Choose status</option><option>Hadir</option><option>Izin</option><option>Sakit</option></select><button class="button button-primary" type="submit">Submit</button></form>
        <?php else: ?><span class="muted"><?= attendance_window_closed() ? 'Check-in closed for today.' : 'Check-in opens at ' . e(ATTENDANCE_OPENS_AT) . ' WIB.' ?></span><?php endif; ?>
    </div>
</section>
<section class="stat-grid stat-grid-four">
    <article class="stat-card"><span>Students in class</span><strong><?= count($students) ?></strong><small>Total active roster</small></article>
    <a class="stat-card stat-positive stat-card-link" href="teacher.php?date=<?= e(rawurlencode($dateInput)) ?>&amp;show=present#roster-list" aria-label="Show present students"><span>Present</span><strong><?= $present ?></strong><small>Click to see students marked Hadir</small></a>
    <a class="stat-card stat-warning stat-card-link" href="teacher.php?date=<?= e(rawurlencode($dateInput)) ?>&amp;show=absent#roster-list" aria-label="Show absent students"><span>Absent</span><strong><?= $absent ?></strong><small>Click to see Izin, Sakit, or Alfa</small></a>
    <a class="stat-card stat-card-link" href="teacher.php?date=<?= e(rawurlencode($dateInput)) ?>&amp;show=awaiting#roster-list" aria-label="Show students awaiting attendance"><span>Awaiting</span><strong><?= $counts['Belum mengisi'] ?></strong><small>Click to see who has not checked in</small></a>
</section>
<section class="panel" id="roster-list">
    <div class="panel-heading"><div><p class="eyebrow">ROSTER · <?= e((new DateTimeImmutable($dateInput))->format('d M Y')) ?></p><h2><?= e($listTitle) ?></h2></div><span class="count-pill"><?= count($visibleStudents) ?> students</span></div>
    <div class="table-wrap"><table><thead><tr><th>Student</th><th>Student ID</th><th>Class</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
    <?php foreach ($visibleStudents as $student): $status = $student['status'] ?? 'Belum mengisi'; ?><tr><td><strong><?= e($student['full_name']) ?></strong><small class="table-sub"><?= e($student['username']) ?></small></td><td>#<?= e($student['id']) ?></td><td><?= e($student['class_name']) ?></td><td><?= status_badge($status) ?></td><td><?= $student['submitted_at'] ? e((new DateTimeImmutable($student['submitted_at']))->format('H:i') . ' WIB') : '—' ?></td></tr><?php endforeach; ?>
    <?php if (!$visibleStudents): ?><tr><td colspan="5" class="empty-cell"><?= $students ? 'No students match this attendance status.' : 'No active students are assigned to your class. Contact an administrator.' ?></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php page_footer(); ?>

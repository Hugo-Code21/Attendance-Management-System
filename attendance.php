<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$today = current_time()->format('Y-m-d');
$date = (string) ($_GET['date'] ?? $today);
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(APP_TIMEZONE));
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || $date > $today) {
    $date = $today;
    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(APP_TIMEZONE));
}
$className = trim((string) ($_GET['class'] ?? ''));

$classes = db()->query("SELECT DISTINCT class_name FROM users WHERE role = 'student' AND is_active = 1 AND class_name IS NOT NULL AND class_name <> '' ORDER BY class_name")->fetchAll(PDO::FETCH_COLUMN);
$sql = "SELECT u.id, u.full_name, u.username, u.class_name, u.is_active, a.status, a.submitted_at
        FROM users u
        LEFT JOIN student_attendance a ON a.student_id = u.id AND a.attendance_date = ?
        WHERE u.role = 'student' AND u.is_active = 1";
$params = [$date];
if ($className !== '') {
    $sql .= ' AND u.class_name = ?';
    $params[] = $className;
}
$sql .= ' ORDER BY u.class_name, u.full_name';
$statement = db()->prepare($sql);
$statement->execute($params);
$students = $statement->fetchAll();
$teacherStatement = db()->prepare(
    "SELECT u.id, u.full_name, u.username, u.class_name, u.subject, t.status, t.submitted_at
     FROM users u
     LEFT JOIN teacher_attendance t ON t.teacher_id = u.id AND t.attendance_date = ?
     WHERE u.role = 'teacher' AND u.is_active = 1
     ORDER BY u.full_name"
);
$teacherStatement->execute([$date]);
$teachers = $teacherStatement->fetchAll();
$counts = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0, 'Belum mengisi' => 0];
foreach ($students as $student) {
    $status = $student['status'] ?? 'Belum mengisi';
    $counts[$status] = ($counts[$status] ?? 0) + 1;
}

page_header('Attendance records');
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">SYSTEM ADMINISTRATION</p>
        <h1>Attendance records</h1>
        <p class="muted">Review attendance submitted by teachers and students.</p>
    </div>
    <form method="get" class="date-filter">
        <label for="attendance-date">Attendance date</label>
        <input type="date" id="attendance-date" name="date" value="<?= e($date) ?>" max="<?= e($today) ?>">
        <label class="sr-only" for="class-filter">Filter by class</label>
        <select id="class-filter" name="class">
            <option value="">All classes</option><?php foreach ($classes as $class): ?><option value="<?= e($class) ?>" <?= $className === $class ? 'selected' : '' ?>><?= e($class) ?></option><?php endforeach; ?>
        </select>
        <button class="button button-outline" type="submit">View</button>
    </form>
</section>
<section class="stat-grid stat-grid-four">
    <article class="stat-card"><span>Students</span><strong><?= count($students) ?></strong><small><?= $className !== '' ? e($className) : 'All classes' ?></small></article>
    <article class="stat-card stat-positive"><span>Present</span><strong><?= $counts['Hadir'] ?></strong><small>Marked Hadir</small></article>
    <article class="stat-card stat-warning"><span>Absent</span><strong><?= $counts['Izin'] + $counts['Sakit'] + $counts['Alfa'] ?></strong><small>Izin, Sakit, or Alfa</small></article>
    <article class="stat-card"><span>Awaiting</span><strong><?= $counts['Belum mengisi'] ?></strong><small>Not submitted</small></article>
</section>
<?php if ($date === $today && !attendance_window_closed()): ?><div class="alert alert-success">Attendance is entered manually by each teacher and student until <?= e(ATTENDANCE_CLOSES_AT) ?> WIB. Missing submissions are not created automatically.</div><?php endif; ?>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">STUDENT RECORDS · <?= e($parsedDate->format('d M Y')) ?></p>
            <h2>Attendance register</h2>
        </div><span class="count-pill"><?= count($students) ?> students</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): $status = $student['status'] ?? 'Belum mengisi'; ?><tr>
                        <td><strong><?= e($student['full_name']) ?></strong><small class="table-sub"><?= e($student['username']) ?><?= !$student['is_active'] ? ' · Inactive account' : '' ?></small></td>
                        <td>#<?= e($student['id']) ?></td>
                        <td><?= e($student['class_name'] ?: '—') ?></td>
                        <td><?= status_badge($status) ?></td>
                        <td><?= $student['submitted_at'] ? e((new DateTimeImmutable($student['submitted_at']))->format('H:i') . ' WIB') : '—' ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$students): ?><tr>
                        <td colspan="5" class="empty-cell">No students found for this class.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">TEACHER RECORDS · <?= e($parsedDate->format('d M Y')) ?></p>
            <h2>Teacher attendance</h2>
        </div><span class="count-pill"><?= count($teachers) ?> teachers</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>ID</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $teacher): ?><tr>
                        <td><strong><?= e($teacher['full_name']) ?></strong><small class="table-sub"><?= e($teacher['username']) ?></small></td>
                        <td>#<?= e($teacher['id']) ?></td>
                        <td><?= e($teacher['class_name'] ?: '—') ?></td>
                        <td><?= e($teacher['subject'] ?: '—') ?></td>
                        <td><?= status_badge($teacher['status'] ?? 'Belum mengisi') ?></td>
                        <td><?= $teacher['submitted_at'] ? e((new DateTimeImmutable($teacher['submitted_at']))->format('H:i') . ' WIB') : '—' ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$teachers): ?><tr>
                        <td colspan="6" class="empty-cell">No active teacher accounts found.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php page_footer(); ?>
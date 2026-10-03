<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$user = require_login();
if ($user['role'] === 'admin') {
    redirect('attendance.php');
}
if ($user['role'] === 'teacher') {
    redirect('teacher.php');
}
redirect('student.php');

<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'ams_db';
const DB_USER = 'root';
const DB_PASSWORD = '';
// Change APP_NAME to update the browser title, header, and sign-in page.
const APP_NAME = 'Your School Name';
// Replace this path with your own icon file when customizing the app branding.
const APP_ICON_PATH = 'img/icon.svg';
const APP_TIMEZONE = 'Asia/Jakarta';
const ATTENDANCE_OPENS_AT = '00:00';
const ATTENDANCE_CLOSES_AT = '06:45';

date_default_timezone_set(APP_TIMEZONE);

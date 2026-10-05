<?php
declare(strict_types=1);

$environmentFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
if (is_file($environmentFile)) {
    $environment = parse_ini_file($environmentFile, false, INI_SCANNER_RAW);
    if ($environment === false) {
        throw new RuntimeException('The application environment file is invalid.');
    }
} else {
    $environment = [];
}

$setting = static function (string $key, string $default) use ($environment): string {
    $value = getenv($key);
    return $value !== false ? $value : (string) ($environment[$key] ?? $default);
};

define('DB_HOST', $setting('DB_HOST', '127.0.0.1'));
define('DB_NAME', $setting('DB_NAME', 'ams_db'));
define('DB_USER', $setting('DB_USER', 'root'));
define('DB_PASSWORD', $setting('DB_PASSWORD', ''));
// Change APP_NAME to update the browser title, header, and sign-in page.
const APP_NAME = 'Your School Name';
// Replace this path with your own icon file when customizing the app branding.
const APP_ICON_PATH = 'img/icon.svg';
const APP_TIMEZONE = 'Asia/Jakarta';
const ATTENDANCE_OPENS_AT = '00:00';
const ATTENDANCE_CLOSES_AT = '06:45';

date_default_timezone_set(APP_TIMEZONE);

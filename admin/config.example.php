<?php
// Copy to config.php and set your server credentials.
// Do NOT commit config.php — it is server-specific (XAMPP vs VPS).

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'tea';

$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (mysqli_connect_error()) {
    die('Database connection failed: ' . mysqli_connect_error());
}

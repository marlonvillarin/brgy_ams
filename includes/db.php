<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'brgy_ams');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='font-family:Arial;padding:40px;text-align:center'><h2 style='color:#c00'>⚠️ DB Error</h2><p>Import database.sql and start XAMPP.</p><small>" . $conn->connect_error . "</small></div>");
}
$conn->set_charset("utf8");

<?php
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location:/brgy_ams/index.php");
        exit();
    }
}
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header("Location:/brgy_ams/dashboard.php");
        exit();
    }
}
function e($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function clean($conn, $v)
{
    return $conn->real_escape_string(trim($v));
}
function setFlash($t, $m)
{
    $_SESSION['ft'] = $t;
    $_SESSION['fm'] = $m;
}
function showFlash()
{

    if (!empty($_SESSION['fm'])) {
        $t = $_SESSION['ft'] ?? 'info';
        $ico = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
        echo "<div class='flash flash-{$t}'><span>" . ($ico[$t] ?? 'ℹ️') . "</span><span>" . e($_SESSION['fm']) . "</span></div>";
        unset($_SESSION['fm'], $_SESSION['ft']);
    }
}
function getUnread($conn, $uid)
{
    $r = $conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id=$uid AND is_read=0");
    return $r->fetch_assoc()['c'];
}
function notify($conn, $uid, $title, $msg)
{
    $t = clean($conn, $title);
    $m = clean($conn, $msg);
    $conn->query("INSERT INTO notifications(user_id,title,message)VALUES($uid,'$t','$m')");
}

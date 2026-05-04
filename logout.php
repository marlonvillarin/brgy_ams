<?php session_start();
session_destroy();
header("Location:/brgy_ams/index.php");
exit(); ?>
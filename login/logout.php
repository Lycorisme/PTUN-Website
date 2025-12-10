<?php
// =============================================
// LOGOUT HANDLER
// C:\laragon\www\ptun-website\login\logout.php
// =============================================

session_start();
session_destroy();
header('Location: index.php');
exit;
?>
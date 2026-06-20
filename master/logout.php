<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['master_auth']);
header('Location: /master/login.php');
exit;

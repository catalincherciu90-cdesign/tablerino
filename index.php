<?php
session_start();

// Daca e autentificat ca restaurant -> dashboard
if (!empty($_SESSION['restaurant_id'])) {
    header('Location: /admin/index.php');
    exit;
}

// Daca e autentificat ca master -> master panel
if (!empty($_SESSION['master_auth'])) {
    header('Location: /master/index.php');
    exit;
}

// Altfel -> afisam homepage
include __DIR__ . '/home.php';

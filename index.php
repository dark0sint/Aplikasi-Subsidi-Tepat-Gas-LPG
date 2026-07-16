<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!sudahAdaUser($koneksi)) {
    header('Location: setup.php');
} elseif (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;

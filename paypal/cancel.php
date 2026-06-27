<?php
session_start();
require_once __DIR__ . '/../config/config.php';
// Clear donation data
if (isset($_SESSION['donation_data'])) {
    unset($_SESSION['donation_data']);
}
$_SESSION['donation_error'] = 'Your PayPal payment was cancelled. No charge was made.';
header('Location: ' . BASE_URL . '/');
exit;
?>
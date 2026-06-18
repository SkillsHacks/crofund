<?php
session_start();
require_once __DIR__ . '/config/User.php';

$user = new User();
$user->logout();

header("Location: /new/");
exit();
?> 
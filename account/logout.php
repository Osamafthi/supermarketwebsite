<?php
require_once '../includes/init.php';

$user = new User($db);
$user->logout();

// Redirect to homepage or login page
header("Location: ../index.php");
exit();


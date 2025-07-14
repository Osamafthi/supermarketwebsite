<?php
require_once '../includes/init.php';
if (!isset($_GET['id'])) {
    http_response_code(400);
    die("Product ID required");
}

$database = new Database();
$db = $database->connect();

$product = new Product($db);
$success = $product->removeFromFeatured($_GET['id']);

header("Location: products.php?action=unfeatured&success=" . ($success ? '1' : '0'));
exit();
<?php
require_once '../includes/init.php';
$database = new Database();
$db = $database->connect();

$product = new Product($db);

// Collect form data
$id = $_POST['id'];
$name = $_POST['name'];
$price = $_POST['price'];
$quantity = $_POST['quantity'];
$description = $_POST['description'];
$category_id = $_POST['category_id'];
$imageName = null;

// Handle optional image upload
if (!empty($_FILES['image']['name'])) {
  $imageName = time() . '_' . $_FILES['image']['name'];
  $targetPath = "../uploads/" . $imageName;
  move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
}
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token mismatch');
}
// Update product
$product->update($id, $name, $price, $quantity, $description, $category_id, $imageName);

// Redirect back
header("Location: products.php");
exit();


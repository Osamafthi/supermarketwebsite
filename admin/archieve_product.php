<?php

require_once '../includes/init.php';

if (isset($_GET['id'])) {
  $productId = $_GET['id'];

  $database = new Database();
  $db = $database->connect();

  $product = new Product($db);
  $product->archive($productId);

  header("Location: products.php"); // redirect back to the products page
  exit();
}


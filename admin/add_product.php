<?php
 require_once '../includes/init.php';
  
  $database = new Database();
  $db = $database->connect();
  
  $product = new Product($db);
 
  // Assign data from the form to the object's properties
  if (isset($_POST['name'])) {
    echo "Name received: " . $_POST['name'];
  } else {
    echo "No name received.";
  }

  echo "<pre>";
print_r($_POST);
echo "</pre>";

 
 echo $_POST['category_id'];

  
  $product->setName($_POST['name']);

  
  $product->setPrice($_POST['price']);
  $product->setStockQuantity($_POST['quantity']);
  
  $product->setCategoryId($_POST['category_id']);
 
  $product->setDescription($_POST['description']);
  
  $imageName = $_FILES['image']['name'];
  
  $imageTmpPath = $_FILES['image']['tmp_name'];
  echo "Tmp path: " . $imageTmpPath;
  $uploadPath = '../uploads/' . $imageName;
  
  if (file_exists($imageTmpPath)) {
    echo "Tmp file exists!";
    if (move_uploaded_file($imageTmpPath, $uploadPath)) {
       
        $product->setImage($imageName);
    } else {
        echo "Failed to move uploaded file.";
    }
} else {
    echo "No temp file uploaded.";
}



  
  $product->create();

  header("Location: products.php"); // redirect back to the products page
  exit();
  

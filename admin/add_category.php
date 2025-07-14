<?php
require_once '../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->connect();

    $name = $_POST['name'];
    $description = $_POST['description'];
    $image = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/categories/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0755); // Set secure permissions after creation
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image = $filename;
        }
    }

    // Insert new category
    $query = "INSERT INTO categories (name, description, image) VALUES (:name, :description, :image)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':image', $image);

    if ($stmt->execute()) {
        // Get the ID of the newly inserted category
        $newCategoryId = $db->lastInsertId();
        
        // Return the new category info as JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $newCategoryId,
            'name' => $name,
            'image' => $image
        ]);
        exit();
    }
}

// If something went wrong
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Error adding category']);

// Add this after checking for UPLOAD_ERR_OK
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid image format']);
    exit();
}

// Check MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mime, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid image type']);
    exit();
}
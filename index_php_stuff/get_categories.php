<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    $category = new Category($db);
    
    $stmt = $category->readAll();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($categories);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
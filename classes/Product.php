<?php
require_once 'Database.php';

class Product {
    private $conn;

    private $id;
    private $category_id;
    private $name;
    private $description;
    private $price;
    private $image;
    private $stock_quantity;
    private $created_at;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Setters
    public function setId($id){$this->id=$id;}
    public function setCategoryId($category_id) { $this->category_id = $category_id; }
    public function setName($name) { $this->name = $name; }
    public function setDescription($description) { $this->description = $description; }
    public function setPrice($price) { $this->price = $price; }
    public function setImage($image) { $this->image = $image; }
    public function setStockQuantity($stock_quantity) { $this->stock_quantity = $stock_quantity; }
   
        // Getters
   
    public function getId() { return $this->id; }
    public function getCategoryId() { return $this->category_id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPrice() { return $this->price; }
    public function getImage() { return $this->image; }
    public function getStockQuantity() { return $this->stock_quantity; }
    public function getCreatedAt() { return $this->created_at; }

    // Create Product
    public function create() {
        
        $sql = "INSERT INTO products (category_id, name, description, price, image, stock_quantity)
                VALUES (:category_id, :name, :description, :price, :image, :stock_quantity)";
       
        $stmt = $this->conn->prepare($sql);
           
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':stock_quantity', $this->stock_quantity);
        
        if ($stmt->execute()) {
            echo "Query successful!";
            return true;
        } else {
            echo "<pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
            return false;
        }
        
        
    }
    
    // Get All Products
    public function getAll() {
        $sql = "SELECT * FROM products";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getFeaturedProducts($limit = 12) {
        $sql = "SELECT p.*, c.name AS category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' AND p.is_featured = 1
                ORDER BY p.featured_order ASC, p.id DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // In your Product class
public function setAsFeatured($productId) {
    // Get current highest featured_order
    $maxOrder = $this->conn->query("SELECT MAX(featured_order) FROM products WHERE is_featured = 1")
                  ->fetchColumn();
    
    $newOrder = $maxOrder ? $maxOrder + 1 : 1;
    
    $sql = "UPDATE products SET 
            is_featured = 1,
            featured_order = :order
            WHERE id = :id";
    
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        ':order' => $newOrder,
        ':id' => $productId
    ]);
}

public function removeFromFeatured($productId) {
    $sql = "UPDATE products SET 
            is_featured = 0,
            featured_order = NULL
            WHERE id = :id";
    
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([':id' => $productId]);
}
    // In your Product class
public function setFeaturedStatus($productId, $isFeatured, $position = null) {
    $sql = "UPDATE products SET 
            is_featured = :is_featured,
            featured_order = :position
            WHERE id = :id";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':is_featured', $isFeatured, PDO::PARAM_BOOL);
    $stmt->bindValue(':position', $position, PDO::PARAM_INT);
    $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
    
    return $stmt->execute();
}
public function getPaginatedProducts($page = 1, $perPage = 12, $categoryId = null, $searchTerm = null) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'";
    
    $params = [];
    
    // Add category filter if provided
    if ($categoryId) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }
    
    // Add search filter if provided
    if ($searchTerm) {
        $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    $sql .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $perPage;
    $params[':offset'] = $offset;
    
    $stmt = $this->conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }
    
    $stmt->execute();
    
    return [
        'products' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $this->getTotalProductsCount($categoryId, $searchTerm)
    ];
}

public function readOne($id) {
    $query = "SELECT * FROM products WHERE id = ? LIMIT 1";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(1, $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getTotalProductsCount($categoryId = null, $searchTerm = null) {
    $sql = "SELECT COUNT(*) FROM products WHERE status = 'active'";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }
    
    if ($searchTerm) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    $stmt = $this->conn->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchColumn();
}
public function readAll($status = null) {
    $sql = "SELECT products.*, categories.name AS category_name, 
                   products.is_featured AS is_featured
            FROM products 
            LEFT JOIN categories ON products.category_id = categories.id";
    
    // Add WHERE clause if status is specified
    if ($status && in_array($status, ['active', 'archived'])) {
        $sql .= " WHERE products.status = :status";
    }
    
    $sql .= " ORDER BY products.id DESC";
  
    $stmt = $this->conn->prepare($sql);
    
    // Bind parameter if status is specified
    if ($status && in_array($status, ['active', 'archived'])) {
        $stmt->bindParam(':status', $status);
    }
    
    $stmt->execute();
  
    return $stmt;
}

    // Get Product by ID
    public function getById($id) {
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function read_single() {
        // Prepare query
        $query = 'SELECT 
                    p.id, 
                    p.category_id, 
                    p.name, 
                    p.description, 
                    p.price, 
                    p.image, 
                    p.stock_quantity,
                    p.created_at
                  FROM 
                    products p
                  WHERE 
                    p.id = ?
                  LIMIT 1';

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID parameter
        $stmt->bindParam(1, $this->id);

        // Execute query
        $stmt->execute();

        // Check if product exists
        if ($stmt->rowCount() == 0) {
            return false;
        }

        // Fetch row and set properties
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->name = $row['name'];
        $this->category_id = $row['category_id'];
        $this->description = $row['description'];
        $this->price = $row['price'];
        $this->image = $row['image'];
        $this->stock_quantity = $row['stock_quantity'];
        $this->created_at = $row['created_at'];

        return true;
    }

    // Update Product
    public function update($id, $name, $price, $quantity, $description, $category_id, $imageName = null) {
        $sql = "UPDATE products SET name = :name, price = :price, stock_quantity = :quantity, 
                description = :description, category_id = :category_id";
        
        if ($imageName) {
          $sql .= ", image = :image";
        }
      
        $sql .= " WHERE id = :id";
      
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':id', $id);
      
        if ($imageName) {
          $stmt->bindParam(':image', $imageName);
        }
      
        $stmt->execute();
      }

      public function archive($id) {
        $query = "UPDATE products SET status = 'archived' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
      }
      
        
      public function readByCategory($category_id) {
        $sql = "SELECT products.*, categories.name AS category_name 
                FROM products 
                LEFT JOIN categories ON products.category_id = categories.id
                WHERE products.category_id = :category_id
                ORDER BY products.id DESC";
      
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
      
        return $stmt;
    }
    public function search($term) {
        $sql = "SELECT products.*, categories.name AS category_name 
                FROM products 
                LEFT JOIN categories ON products.category_id = categories.id
                WHERE products.name LIKE :term 
                OR products.description LIKE :term
                ORDER BY products.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $searchTerm = '%' . $term . '%';
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        
        return $stmt;
    }
    // Delete Product
    public function delete($id) {
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
    public function readByStatus($status) {
        $sql = "SELECT products.*, categories.name AS category_name 
                FROM products 
                LEFT JOIN categories ON products.category_id = categories.id
                WHERE products.status = :status
                ORDER BY products.id DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt;
    }
    public function activate($id) {
        $query = "UPDATE products SET status = 'active' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getRecommendedProducts($currentProductId, $limit = 8) {
        // First try to get products from the same category
        $sql = "SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' 
                AND p.id != :current_id
                AND p.category_id = (
                    SELECT category_id FROM products WHERE id = :current_id_2
                )
                ORDER BY p.stock_quantity DESC, p.id DESC
                LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':current_id', $currentProductId, PDO::PARAM_INT);
        $stmt->bindValue(':current_id_2', $currentProductId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we don't have enough recommendations from the same category,
        // fill with other active products
        if (count($recommendations) < $limit) {
            $remaining = $limit - count($recommendations);
            $existingIds = array_column($recommendations, 'id');
            $existingIds[] = $currentProductId;
            
            $placeholders = str_repeat('?,', count($existingIds) - 1) . '?';

$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' 
        AND p.id NOT IN ($placeholders)
        ORDER BY p.stock_quantity DESC, p.id DESC
        LIMIT ?";  // Changed to positional parameter

$stmt = $this->conn->prepare($sql);

// Bind the existing IDs
foreach ($existingIds as $index => $id) {
    $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
}
// Bind the limit parameter
$stmt->bindValue(count($existingIds) + 1, $remaining, PDO::PARAM_INT);
            
            $stmt->execute();
            $additionalProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $recommendations = array_merge($recommendations, $additionalProducts);
        }
        
        return $recommendations;
    }
    
    /**
     * Get related products by category
     */
    public function getRelatedByCategory($categoryId, $excludeId = null, $limit = 8) {
        $sql = "SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active' 
                AND p.category_id = :category_id";
        
        if ($excludeId) {
            $sql .= " AND p.id != :exclude_id";
        }
        
        $sql .= " ORDER BY p.stock_quantity DESC, p.id DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recently viewed products (if you implement view tracking)
     */
    public function getRecentlyViewed($excludeId = null, $limit = 8) {
        $sql = "SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'";
        
        if ($excludeId) {
            $sql .= " AND p.id != :exclude_id";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


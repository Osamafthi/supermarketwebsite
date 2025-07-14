<?php
// File: admin/orders_controller.php
require_once '../includes/init.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    // For AJAX requests, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
    header("Location: ../account/login.php");
    exit();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    switch ($_POST['action']) {
        case 'update_status':
            $orderId = $_POST['order_id'];
            $newStatus = $_POST['new_status'];
            $trackingNumber = $_POST['tracking_number'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // Validate status
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid status']);
                    exit();
                }
                header("Location: orders.php?error=" . urlencode("Invalid status"));
                exit();
            }
            
            try {
                $db->beginTransaction();
                
                // Update order status
                $stmt = $db->prepare("UPDATE orders SET order_status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $notes, $orderId]);
                
                // Create tracking message
                $message = "Order status updated to: " . ucfirst($newStatus);
                if ($notes) {
                    $message .= " - " . $notes;
                }
                
                // Insert tracking record
                $stmt = $db->prepare("INSERT INTO order_tracking (order_id, status, message, tracking_number, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$orderId, $newStatus, $message, $trackingNumber]);
                
                $db->commit();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Order status updated successfully',
                        'new_status' => $newStatus
                    ]);
                    exit();
                }
                
                header("Location: orders.php?success=1");
                
            } catch (Exception $e) {
                $db->rollback();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit();
                }
                
                header("Location: orders.php?error=" . urlencode($e->getMessage()));
            }
            exit();
            
        case 'update_payment':
            $orderId = $_POST['order_id'];
            $paymentStatus = $_POST['payment_status'];
            
            // Validate payment status
            $validPaymentStatuses = ['pending', 'completed', 'failed'];
            if (!in_array($paymentStatus, $validPaymentStatuses)) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid payment status']);
                    exit();
                }
                header("Location: orders.php?error=" . urlencode("Invalid payment status"));
                exit();
            }
            
            try {
                $stmt = $db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$paymentStatus, $orderId]);
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Payment status updated successfully',
                        'new_payment_status' => $paymentStatus
                    ]);
                    exit();
                }
                
                header("Location: orders.php?success=1");
                
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit();
                }
                
                header("Location: orders.php?error=" . urlencode($e->getMessage()));
            }
            exit();
    }
}

// If we get here, it's likely a GET request or invalid action
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

header("Location: orders.php");
exit();
?>
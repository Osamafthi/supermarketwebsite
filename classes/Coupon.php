<?php
// File: classes/Coupon.php

class Coupon {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all active coupons for a user
     */
    public function getUserCoupons($userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                uc.used_at,
                CASE 
                    WHEN c.expires_at < NOW() THEN 'expired'
                    WHEN c.usage_count >= c.usage_limit THEN 'used_up'
                    WHEN uc.used_at IS NOT NULL THEN 'used'
                    ELSE 'active'
                END as status
            FROM coupons c
            LEFT JOIN user_coupons uc ON c.id = uc.coupon_id AND uc.user_id = ?
            WHERE c.is_active = 1 AND (c.is_public = 1 OR uc.user_id = ?)
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Validate a coupon code
     */
    public function validateCoupon($code, $userId, $orderTotal = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.*,
                uc.used_at
            FROM coupons c
            LEFT JOIN user_coupons uc ON c.id = uc.coupon_id AND uc.user_id = ?
            WHERE c.code = ? AND c.is_active = 1
        ");
        $stmt->execute([$userId, $code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            throw new Exception("Invalid coupon code");
        }
        
        // Check if expired
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            throw new Exception("Coupon has expired");
        }
        
        // Check usage limit
        if ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']) {
            throw new Exception("Coupon usage limit reached");
        }
        
        // Check if already used by user (for non-public coupons)
        if (!$coupon['is_public'] && $coupon['used_at']) {
            throw new Exception("Coupon already used");
        }
        
        // Check minimum order amount
        if ($coupon['min_order_amount'] && $orderTotal < $coupon['min_order_amount']) {
            throw new Exception("Minimum order amount of $" . number_format($coupon['min_order_amount'], 2) . " required");
        }
        
        return $coupon;
    }
    
    /**
     * Calculate discount amount
     */
    public function calculateDiscount($coupon, $orderTotal) {
        $discount = 0;
        
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($orderTotal * $coupon['discount_value']) / 100;
            
            // Apply max discount limit if set
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Don't let discount exceed order total
        if ($discount > $orderTotal) {
            $discount = $orderTotal;
        }
        
        return round($discount, 2);
    }
    
    /**
     * Apply coupon to order
     */
    public function applyCoupon($code, $userId, $orderTotal) {
        try {
            $coupon = $this->validateCoupon($code, $userId, $orderTotal);
            $discount = $this->calculateDiscount($coupon, $orderTotal);
            
            return [
                'valid' => true,
                'coupon' => $coupon,
                'discount' => $discount,
                'new_total' => $orderTotal - $discount
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Use coupon (mark as used and increment usage count)
     */
    public function useCoupon($couponId, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Insert user coupon record (or update if exists)
            $stmt = $this->conn->prepare("
                INSERT INTO user_coupons (user_id, coupon_id, used_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE used_at = NOW()
            ");
            $stmt->execute([$userId, $couponId]);
            
            // Increment usage count
            $stmt = $this->conn->prepare("
                UPDATE coupons 
                SET usage_count = usage_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$couponId]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    
    /**
     * Create a new coupon
     */
    public function createCoupon($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO coupons (
                code, description, discount_type, discount_value, 
                min_order_amount, max_discount, usage_limit, 
                is_public, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['code'],
            $data['description'],
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order_amount'] ?? null,
            $data['max_discount'] ?? null,
            $data['usage_limit'] ?? null,
            $data['is_public'] ?? 1,
            $data['expires_at'] ?? null
        ]);
    }
    
    /**
     * Get coupon by ID
     */
    public function getCouponById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all coupons (admin)
     */
    public function getAllCoupons() {
        $stmt = $this->conn->prepare("
            SELECT *, 
                CASE 
                    WHEN expires_at < NOW() THEN 'expired'
                    WHEN usage_count >= usage_limit THEN 'used_up'
                    WHEN is_active = 0 THEN 'inactive'
                    ELSE 'active'
                END as status
            FROM coupons 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update coupon
     */
    public function updateCoupon($id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE coupons SET 
                code = ?, description = ?, discount_type = ?, 
                discount_value = ?, min_order_amount = ?, max_discount = ?, 
                usage_limit = ?, is_public = ?, is_active = ?, expires_at = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $data['code'],
            $data['description'],
            $data['discount_type'],
            $data['discount_value'],
            $data['min_order_amount'] ?? null,
            $data['max_discount'] ?? null,
            $data['usage_limit'] ?? null,
            $data['is_public'] ?? 1,
            $data['is_active'] ?? 1,
            $data['expires_at'] ?? null,
            $id
        ]);
    }
    
    /**
     * Delete coupon
     */
    public function deleteCoupon($id) {
        $stmt = $this->conn->prepare("DELETE FROM coupons WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Assign coupon to user
     */
    public function assignCouponToUser($couponId, $userId) {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO user_coupons (user_id, coupon_id) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$userId, $couponId]);
    }
    
    /**
     * Get coupon usage statistics
     */
    public function getCouponStats($couponId) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.code,
                c.usage_count,
                c.usage_limit,
                COUNT(DISTINCT uc.user_id) as unique_users,
                SUM(CASE WHEN uc.used_at IS NOT NULL THEN 1 ELSE 0 END) as times_used
            FROM coupons c
            LEFT JOIN user_coupons uc ON c.id = uc.coupon_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$couponId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<?php
global $cart;
$items = $cart->getCartItems();
$subtotal = 0;
$itemCount = 0;

foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $itemCount += $item['quantity'];
}

$tax = $subtotal * 0.1; // 10% tax
$shippingCost = isset($_SESSION['checkout']['shipping']['shippingMethod']) && 
                $_SESSION['checkout']['shipping']['shippingMethod'] === 'express' ? 12.99 : 5.99;
$total = $subtotal + $tax + $shippingCost;
?>

<h5>Items (<?= $itemCount ?>)</h5>
<ul class="list-unstyled">
    <?php foreach ($items as $item): ?>
    <li class="d-flex justify-content-between py-2 border-bottom">
        <div>
            <?= htmlspecialchars($item['name']) ?> 
            <small class="text-muted">x<?= $item['quantity'] ?></small>
        </div>
        <div>$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
    </li>
    <?php endforeach; ?>
</ul>

<div class="d-flex justify-content-between py-2 border-bottom">
    <div>Subtotal</div>
    <div>$<?= number_format($subtotal, 2) ?></div>
</div>
<div class="d-flex justify-content-between py-2 border-bottom">
    <div>Tax (10%)</div>
    <div>$<?= number_format($tax, 2) ?></div>
</div>
<div class="d-flex justify-content-between py-2 border-bottom">
    <div>Shipping</div>
    <div>$<?= number_format($shippingCost, 2) ?></div>
</div>
<div class="d-flex justify-content-between py-2 font-weight-bold">
    <div>Total</div>
    <div>$<?= number_format($total, 2) ?></div>
</div>
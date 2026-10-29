<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action     = $_POST['action']     ?? $_GET['action']     ?? '';
$product_id = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));
$cart_id    = (int)($_POST['cart_id'] ?? 0);

switch ($action) {
    case 'add':
        if (!$product_id) { echo json_encode(['success' => false, 'message' => 'Invalid product.']); exit; }
        $ok = addToCart($conn, $product_id, $quantity);
        echo json_encode([
            'success'    => $ok,
            'message'    => $ok ? 'Added to your bag.' : "Couldn't add that to your bag.",
            'cart_count' => getCartCount($conn),
        ]);
        break;

    case 'update':
        if (!$cart_id) { echo json_encode(['success' => false]); exit; }
        updateCartQty($conn, $cart_id, $quantity);
        $items = getCartItems($conn);
        $total = getCartTotal($conn);
        echo json_encode([
            'success'    => true,
            'cart_count' => getCartCount($conn),
            'cart_total' => formatPrice($total),
        ]);
        break;

    case 'remove':
        if (!$cart_id) { echo json_encode(['success' => false]); exit; }
        removeFromCart($conn, $cart_id);
        echo json_encode([
            'success'    => true,
            'cart_count' => getCartCount($conn),
            'cart_total' => formatPrice(getCartTotal($conn)),
        ]);
        break;

    case 'count':
        echo json_encode(['count' => getCartCount($conn)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

<?php

require_once __DIR__ . '/includes/functions.php';

// Ensure this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product = getProduct($productId);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Return product data as JSON
echo json_encode([
    'id' => $product['id'],
    'name' => $product['name'],
    'description' => $product['description'],
    'price' => (float)$product['price'],
    'image' => $product['image'],
    'category' => $product['category']
]);

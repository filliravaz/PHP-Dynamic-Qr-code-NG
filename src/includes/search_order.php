<?php
require_once BASE_PATH . '/config/config.php';

// Get Input data from query string
$order_by	= filter_input(INPUT_GET, 'order_by', FILTER_DEFAULT);
$order_dir	= filter_input(INPUT_GET, 'order_dir', FILTER_DEFAULT);
$search_str	= filter_input(INPUT_GET, 'search_str', FILTER_DEFAULT);

$allowed_columns = (isset($select) && is_array($select)) ? $select : ['id'];

// Whitelist order_by column against table schema columns
if (!$order_by || !in_array($order_by, $allowed_columns, true)) {
	$order_by = 'id';
}

// Whitelist order direction strictly to ASC or DESC
$order_dir = (strtoupper((string)$order_dir) === 'ASC') ? 'ASC' : 'DESC';

// Build search query safely with parameter binding and whitelisted fields
if (!empty($search_str) && isset($search_fields) && is_array($search_fields)) {
    foreach ($search_fields as $value) {
        if (in_array($value, $allowed_columns, true)) {
            $db->orwhere($value, '%' . $search_str . '%', 'like');
        }
    }
}

// Apply whitelisted ordering to query
$db->orderBy($order_by, $order_dir);
?>

<?php

// Get Input data from query string
$order_by	= filter_input(INPUT_GET, 'order_by', FILTER_DEFAULT);
$order_dir	= filter_input(INPUT_GET, 'order_dir', FILTER_DEFAULT);
$search_str	= filter_input(INPUT_GET, 'search_str', FILTER_DEFAULT);

$allowed_columns = (isset($allowed_columns) && is_array($allowed_columns)) ? $allowed_columns : ((isset($select) && is_array($select)) ? $select : ['id']);

// Whitelist order_by column against table schema columns
if (!$order_by || !in_array($order_by, $allowed_columns, true)) {
	$order_by = 'id';
}

// Whitelist order direction strictly to ASC or DESC
$order_dir = (strtoupper((string)$order_dir) === 'ASC') ? 'ASC' : 'DESC';

// Build search query safely with grouped parenthesized conditions and parameter binding
if (!empty($search_str) && isset($search_fields) && is_array($search_fields)) {
    $searchClauses = [];
    $searchParams = [];
    foreach ($search_fields as $value) {
        if (in_array($value, $allowed_columns, true)) {
            $colPrefix = (!empty($order_table)) ? "`{$order_table}`." : '';
            $searchClauses[] = "{$colPrefix}`{$value}` LIKE ?";
            $searchParams[] = '%' . $search_str . '%';
        }
    }
    if (!empty($searchClauses)) {
        $db->where('(' . implode(' OR ', $searchClauses) . ')', $searchParams);
    }
}

// Apply whitelisted ordering to query (with table prefix if joining, to avoid ambiguity)
$orderCol = (!empty($order_table)) ? "`{$order_table}`.`{$order_by}`" : $order_by;
$db->orderBy($orderCol, $order_dir);
?>

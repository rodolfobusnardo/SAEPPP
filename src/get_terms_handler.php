<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Ensure only logged-in users can access this data
require_login(); 

header('Content-Type: application/json');

const ITEMS_PER_PAGE = 10;

// --- LÓGICA PARA TERMOS DE DEVOLUÇÃO ---
$current_page_dev = filter_input(INPUT_GET, 'page_dev', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset_dev = ($current_page_dev - 1) * ITEMS_PER_PAGE;
$filter_owner_name = $_GET['filter_owner_name'] ?? '';
$filter_item_name_dev = $_GET['filter_item_name_dev'] ?? '';

$base_sql_dev = "FROM devolution_documents dd 
                 LEFT JOIN users u ON dd.returned_by_user_id = u.id 
                 LEFT JOIN items i ON dd.item_id = i.id";
$conditions_dev = [];
$params_dev = [];
$types_dev = "";

if (!empty($filter_owner_name)) { $conditions_dev[] = "dd.owner_name LIKE ?"; $params_dev[] = "%" . $filter_owner_name . "%"; $types_dev .= "s"; }
if (!empty($filter_item_name_dev)) { $conditions_dev[] = "i.name LIKE ?"; $params_dev[] = "%" . $filter_item_name_dev . "%"; $types_dev .= "s"; }

$where_clause_dev = !empty($conditions_dev) ? " WHERE " . implode(" AND ", $conditions_dev) : "";

// Get total count
$sql_count_dev = "SELECT COUNT(dd.id) AS total " . $base_sql_dev . $where_clause_dev;
$stmt_count_dev = $conn->prepare($sql_count_dev);
if ($stmt_count_dev && !empty($types_dev)) { $stmt_count_dev->bind_param($types_dev, ...$params_dev); }
if($stmt_count_dev) {
    $stmt_count_dev->execute();
    $total_devolution_terms = (int)$stmt_count_dev->get_result()->fetch_assoc()['total'];
    $stmt_count_dev->close();
} else { $total_devolution_terms = 0; }

// Get paginated data
$devolution_terms_list = [];
if ($total_devolution_terms > 0) {
    $sql_dev_terms = "SELECT dd.id, dd.devolution_timestamp, dd.owner_name, i.name AS item_name, u.full_name AS returned_by, u.username AS returned_by_username
                      " . $base_sql_dev . $where_clause_dev . " 
                      ORDER BY dd.devolution_timestamp DESC LIMIT ? OFFSET ?";
    $stmt_dev_terms = $conn->prepare($sql_dev_terms);
    if ($stmt_dev_terms) {
        $types_full_dev = $types_dev . 'ii';
        $bind_params_dev = array_merge($params_dev, [ITEMS_PER_PAGE, $offset_dev]);
        $stmt_dev_terms->bind_param($types_full_dev, ...$bind_params_dev);
        $stmt_dev_terms->execute();
        $result_dev_terms = $stmt_dev_terms->get_result();
        while ($term = $result_dev_terms->fetch_assoc()) {
            // Format date before sending
            $term['devolution_timestamp_formatted'] = date('d/m/Y H:i', strtotime($term['devolution_timestamp']));
            $devolution_terms_list[] = $term;
        }
        $stmt_dev_terms->close();
    }
}
$total_pages_dev = $total_devolution_terms > 0 ? ceil($total_devolution_terms / ITEMS_PER_PAGE) : 1;


// --- LÓGICA PARA TERMOS DE DOAÇÃO ---
$current_page_don = filter_input(INPUT_GET, 'page_don', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset_don = ($current_page_don - 1) * ITEMS_PER_PAGE;
$filter_status = $_GET['filter_status'] ?? '';
$filter_company = $_GET['filter_company'] ?? '';
$filter_start_date = $_GET['filter_start_date'] ?? '';
$filter_end_date = $_GET['filter_end_date'] ?? '';

$base_sql_don = "FROM donation_terms dt 
                 LEFT JOIN users u ON dt.user_id = u.id 
                 LEFT JOIN companies c ON dt.company_id = c.id";
$conditions_don = [];
$params_don = [];
$types_don = "";

if (!empty($filter_status)) { $conditions_don[] = "dt.status = ?"; $params_don[] = $filter_status; $types_don .= "s"; }
if (!empty($filter_company)) { $conditions_don[] = "dt.company_id = ?"; $params_don[] = $filter_company; $types_don .= "i"; }
if (!empty($filter_start_date)) { $conditions_don[] = "DATE(dt.created_at) >= ?"; $params_don[] = $filter_start_date; $types_don .= "s"; }
if (!empty($filter_end_date)) { $conditions_don[] = "DATE(dt.created_at) <= ?"; $params_don[] = $filter_end_date; $types_don .= "s"; }

$where_clause_don = !empty($conditions_don) ? " WHERE " . implode(" AND ", $conditions_don) : "";

// Get total count
$sql_count_don = "SELECT COUNT(dt.term_id) AS total " . $base_sql_don . $where_clause_don;
$stmt_count_don = $conn->prepare($sql_count_don);
if ($stmt_count_don && !empty($types_don)) { $stmt_count_don->bind_param($types_don, ...$params_don); }
if($stmt_count_don) {
    $stmt_count_don->execute();
    $total_donation_terms = (int)$stmt_count_don->get_result()->fetch_assoc()['total'];
    $stmt_count_don->close();
} else { $total_donation_terms = 0; }

// Get paginated data
$donation_terms_list = [];
if ($total_donation_terms > 0) {
    $sql_don_terms = "SELECT dt.term_id, dt.created_at, dt.status, u.full_name AS registered_by, u.username as registered_by_username, c.name AS company_name
                      " . $base_sql_don . $where_clause_don . " 
                      ORDER BY dt.created_at DESC LIMIT ? OFFSET ?";
    $stmt_don_terms = $conn->prepare($sql_don_terms);
    if ($stmt_don_terms) {
        $types_full = $types_don . 'ii';
        $bind_params_don = array_merge($params_don, [ITEMS_PER_PAGE, $offset_don]);
        $stmt_don_terms->bind_param($types_full, ...$bind_params_don);
        $stmt_don_terms->execute();
        $result_don_terms = $stmt_don_terms->get_result();
        while ($term = $result_don_terms->fetch_assoc()) {
            // Format date and create status slug before sending
            $term['created_at_formatted'] = date('d/m/Y H:i', strtotime($term['created_at']));
            $status_map = ['Em aprovação' => 'em-aprovacao', 'Aprovado' => 'aprovado', 'Negado' => 'negado', 'Doado' => 'doado'];
            $term['status_slug'] = $status_map[$term['status']] ?? 'desconhecido';
            $donation_terms_list[] = $term;
        }
        $stmt_don_terms->close();
    }
}
$total_pages_don = $total_donation_terms > 0 ? ceil($total_donation_terms / ITEMS_PER_PAGE) : 1;


// --- RESPOSTA JSON ---
echo json_encode([
    'devolution' => [
        'terms' => $devolution_terms_list,
        'pagination' => [
            'currentPage' => $current_page_dev,
            'totalPages' => $total_pages_dev,
            'totalItems' => $total_devolution_terms,
        ]
    ],
    'donation' => [
        'terms' => $donation_terms_list,
        'pagination' => [
            'currentPage' => $current_page_don,
            'totalPages' => $total_pages_don,
            'totalItems' => $total_donation_terms,
        ]
    ]
]);

$conn->close();
?>
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$data = [];

date_default_timezone_set('Asia/Jakarta');
$today = date('Ymd');

// OPTIMASI: Gunakan query yang lebih efisien
try {
    // Query untuk mendapatkan data multiple days sekaligus
    $dateList = [];
    for ($i = $days; $i >= 0; $i--) {
        $dateList[] = date('Ymd', strtotime("-$i days"));
    }
    
    // Convert ke string untuk IN clause
    $datePlaceholders = str_repeat('?,', count($dateList) - 1) . '?';
    
    // Query T_ORDER sekaligus untuk semua tanggal
    $sqlOrder = "
    SELECT 
        DELV_DATE,
        ISNULL(SUM(ORD_QTY), 0) as total_order
    FROM T_ORDER 
    WHERE DELV_DATE IN ($datePlaceholders)
    AND ORD_QTY > 0
    GROUP BY DELV_DATE
    ORDER BY DELV_DATE
    ";
    
    $stmtOrder = sqlsrv_query($conn, $sqlOrder, $dateList);
    
    if ($stmtOrder === false) {
        error_log("Order query error: " . print_r(sqlsrv_errors(), true));
        throw new Exception('Failed to query order data');
    }
    
    $orderData = [];
    while ($row = sqlsrv_fetch_array($stmtOrder, SQLSRV_FETCH_ASSOC)) {
        $orderData[$row['DELV_DATE']] = (int)$row['total_order'];
    }
    sqlsrv_free_stmt($stmtOrder);
    
    // Query T_UPDATE_BO sekaligus untuk semua tanggal
    $sqlIncoming = "
    SELECT 
        ub.DATE,
        ISNULL(SUM(last_qty), 0) as total_incoming
    FROM (
        SELECT 
            DATE,
            PART_NO,
            MAX(TRAN_QTY) as last_qty
        FROM T_UPDATE_BO 
        WHERE DATE IN ($datePlaceholders)
        GROUP BY DATE, PART_NO
    ) as ub
    GROUP BY ub.DATE
    ORDER BY ub.DATE
    ";
    
    $stmtIncoming = sqlsrv_query($conn, $sqlIncoming, $dateList);
    
    if ($stmtIncoming === false) {
        error_log("Incoming query error: " . print_r(sqlsrv_errors(), true));
        throw new Exception('Failed to query incoming data');
    }
    
    $incomingData = [];
    while ($row = sqlsrv_fetch_array($stmtIncoming, SQLSRV_FETCH_ASSOC)) {
        $incomingData[$row['DATE']] = (int)$row['total_incoming'];
    }
    sqlsrv_free_stmt($stmtIncoming);
    
    // Build response data
    foreach ($dateList as $date) {
        $displayDate = date('d M', strtotime($date));
        
        $totalOrder = $orderData[$date] ?? 0;
        $totalIncoming = $incomingData[$date] ?? 0;
        
        $completionRate = $totalOrder > 0 ? round(($totalIncoming / $totalOrder) * 100, 1) : 0;
        
        $data[] = [
            'date' => $displayDate,
            'full_date' => $date,
            'target_qty' => $totalOrder,
            'actual_qty' => $totalIncoming,
            'completion_rate' => $completionRate
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'query_info' => [
            'days' => $days,
            'today' => $today,
            'date_count' => count($dateList)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Performance trend error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
        'count' => 0
    ]);
}
?>
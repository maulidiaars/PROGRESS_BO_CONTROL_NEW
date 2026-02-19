<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$date = $_GET['date'] ?? date('Ymd');

try {
    // ✅ TOTAL ORDER = ORD_QTY + ADD_DS + ADD_NS (REGULER + ADD)
    $sqlOrder = "
    SELECT 
        ISNULL(SUM(ISNULL(ORD_QTY, 0) + ISNULL(ADD_DS, 0) + ISNULL(ADD_NS, 0)), 0) as total_order
    FROM T_ORDER 
    WHERE DELV_DATE = ?
    ";
    
    $stmtOrder = sqlsrv_query($conn, $sqlOrder, [$date]);
    $rowOrder = sqlsrv_fetch_array($stmtOrder, SQLSRV_FETCH_ASSOC);
    $totalOrder = (int)($rowOrder['total_order'] ?? 0);
    sqlsrv_free_stmt($stmtOrder);
    
    // ✅ TOTAL INCOMING = PURE DARI BO, PAKAI LOGIKA SELISIH!
    $sqlAll = "SELECT PART_NO, HOUR, TRAN_QTY FROM T_UPDATE_BO WHERE DATE = ? ORDER BY PART_NO, HOUR";
    $stmtAll = sqlsrv_query($conn, $sqlAll, [$date]);
    
    $allData = [];
    $totalIncoming = 0;
    $dsIncoming = 0;
    
    if ($stmtAll) {
        while ($row = sqlsrv_fetch_array($stmtAll, SQLSRV_FETCH_ASSOC)) {
            $partNo = $row['PART_NO'];
            $hour = (int)$row['HOUR'];
            $qty = (int)$row['TRAN_QTY'];
            
            if (!isset($allData[$partNo])) {
                $allData[$partNo] = [];
            }
            $allData[$partNo][$hour] = $qty;
        }
        sqlsrv_free_stmt($stmtAll);
    }
    
    // Hitung incremental per jam
    foreach ($allData as $partNo => $hourData) {
        $prevQty = 0;
        $hours = array_keys($hourData);
        sort($hours);
        
        foreach ($hours as $hour) {
            $currentQty = $hourData[$hour];
            $incoming = $currentQty - $prevQty;
            $incoming = max(0, $incoming); // Jangan minus
            
            $totalIncoming += $incoming;
            
            // DS: jam 7-20
            if ($hour >= 7 && $hour <= 20) {
                $dsIncoming += $incoming;
            }
            
            $prevQty = $currentQty;
        }
    }
    
    $nsIncoming = $totalIncoming - $dsIncoming;
    $achievement = $totalOrder > 0 ? round(($totalIncoming / $totalOrder) * 100, 1) : 0;
    $balance = max($totalOrder - $totalIncoming, 0);
    
    echo json_encode([
        'date' => $date,
        'total_order' => $totalOrder,      // ✅ Termasuk ADD DS + ADD NS
        'total_incoming' => $totalIncoming, // ✅ PURE dari BO
        'ds_incoming' => $dsIncoming,
        'ns_incoming' => $nsIncoming,
        'achievement' => $achievement,
        'balance' => $balance,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
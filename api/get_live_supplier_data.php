<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$date = $_GET['date'] ?? date('Ymd');

try {
    // ===========================================
    // QUERY FINAL - HITUNG REMAIN DENGAN BENAR!
    // ===========================================
    $sql = "
    WITH OrderSummary AS (
        SELECT 
            o.SUPPLIER_CODE,
            MAX(o.SUPPLIER_NAME) as SUPPLIER_NAME,
            
            -- REGULAR ORDER (dari Excel)
            SUM(ISNULL(o.ORD_QTY, 0)) as REGULAR_ORDER_QTY,
            
            -- ADD ORDER
            SUM(ISNULL(o.ADD_DS, 0)) as ADD_DS,
            SUM(ISNULL(o.ADD_NS, 0)) as ADD_NS,
            
            -- TOTAL ORDER = REGULAR + ADD
            SUM(ISNULL(o.ORD_QTY, 0)) + 
            SUM(ISNULL(o.ADD_DS, 0)) + 
            SUM(ISNULL(o.ADD_NS, 0)) as TOTAL_ORDER_QTY,
            
            MAX(o.REMARK_DS) as REMARK_DS,
            MAX(o.REMARK_NS) as REMARK_NS
            
        FROM T_ORDER o
        WHERE o.DELV_DATE = ?
        AND o.SUPPLIER_CODE IS NOT NULL
        AND o.SUPPLIER_CODE != ''
        GROUP BY o.SUPPLIER_CODE
    ),
    
    SupplierPIC AS (
        SELECT 
            SUPPLIER_CODE,
            MAX(PIC_ORDER) as PIC_ORDER
        FROM M_PART_NO
        WHERE SUPPLIER_CODE IN (SELECT SUPPLIER_CODE FROM OrderSummary)
        AND PIC_ORDER IS NOT NULL 
        AND PIC_ORDER != ''
        GROUP BY SUPPLIER_CODE
    ),
    
    -- INCOMING per PART dulu, baru di-SUM per SUPPLIER
    IncomingByPart AS (
        SELECT 
            o.SUPPLIER_CODE,
            ub.PART_NO,
            SUM(CASE WHEN ub.HOUR BETWEEN 7 AND 20 THEN ub.TRAN_QTY ELSE 0 END) as DS_INCOMING,
            SUM(CASE WHEN ub.HOUR BETWEEN 21 AND 23 OR ub.HOUR BETWEEN 0 AND 6 THEN ub.TRAN_QTY ELSE 0 END) as NS_INCOMING,
            SUM(ub.TRAN_QTY) as TOTAL_INCOMING
        FROM T_ORDER o
        INNER JOIN T_UPDATE_BO ub ON 
            ub.DATE = o.DELV_DATE 
            AND ub.PART_NO = o.PART_NO
        WHERE o.DELV_DATE = ?
        GROUP BY o.SUPPLIER_CODE, ub.PART_NO
    ),
    
    IncomingSummary AS (
        SELECT 
            SUPPLIER_CODE,
            SUM(DS_INCOMING) as DS_INCOMING,
            SUM(NS_INCOMING) as NS_INCOMING,
            SUM(TOTAL_INCOMING) as TOTAL_INCOMING
        FROM IncomingByPart
        GROUP BY SUPPLIER_CODE
    )
    
    SELECT 
        o.SUPPLIER_CODE,
        o.SUPPLIER_NAME,
        ISNULL(p.PIC_ORDER, 'System') as PIC_ORDER,
        
        -- ORDER
        o.REGULAR_ORDER_QTY,
        o.ADD_DS,
        o.ADD_NS,
        o.TOTAL_ORDER_QTY,
        
        -- INCOMING
        ISNULL(i.DS_INCOMING, 0) as DS_INCOMING,
        ISNULL(i.NS_INCOMING, 0) as NS_INCOMING,
        ISNULL(i.TOTAL_INCOMING, 0) as TOTAL_INCOMING,
        
        -- COMPLETION RATE = INCOMING / TOTAL_ORDER
        CASE 
            WHEN o.TOTAL_ORDER_QTY > 0 
            THEN ROUND((ISNULL(i.TOTAL_INCOMING, 0) * 100.0) / o.TOTAL_ORDER_QTY, 1)
            ELSE 0 
        END as COMPLETION_RATE,
        
        -- BALANCE = TOTAL_ORDER - INCOMING (BUKAN REGULAR ORDER!)
        o.TOTAL_ORDER_QTY - ISNULL(i.TOTAL_INCOMING, 0) as BALANCE,
        
        -- DS COMPLETION
        CASE 
            WHEN o.TOTAL_ORDER_QTY > 0 
            THEN ROUND((ISNULL(i.DS_INCOMING, 0) * 100.0) / o.TOTAL_ORDER_QTY, 0)
            ELSE 0 
        END as DS_COMPLETION,
        
        -- NS COMPLETION
        CASE 
            WHEN o.TOTAL_ORDER_QTY > 0 
            THEN ROUND((ISNULL(i.NS_INCOMING, 0) * 100.0) / o.TOTAL_ORDER_QTY, 0)
            ELSE 0 
        END as NS_COMPLETION,
        
        o.REMARK_DS,
        o.REMARK_NS
        
    FROM OrderSummary o
    LEFT JOIN SupplierPIC p ON o.SUPPLIER_CODE = p.SUPPLIER_CODE
    LEFT JOIN IncomingSummary i ON o.SUPPLIER_CODE = i.SUPPLIER_CODE
    WHERE o.TOTAL_ORDER_QTY > 0
    ORDER BY o.SUPPLIER_CODE
    ";
    
    $stmt = sqlsrv_query($conn, $sql, [$date, $date]);
    
    if (!$stmt) {
        throw new Exception('Query failed: ' . print_r(sqlsrv_errors(), true));
    }
    
    $data = [];
    $totalOrderAll = 0;
    $totalIncomingAll = 0;
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $supplierCode = $row['SUPPLIER_CODE'] ?? '';
        if (empty($supplierCode)) continue;
        
        // AMBIL NILAI DARI QUERY
        $regularOrder = (int)$row['REGULAR_ORDER_QTY'];
        $addDS = (int)$row['ADD_DS'];
        $addNS = (int)$row['ADD_NS'];
        $totalOrder = (int)$row['TOTAL_ORDER_QTY'];
        $totalIncoming = (int)$row['TOTAL_INCOMING'];
        $dsIncoming = (int)$row['DS_INCOMING'];
        $nsIncoming = (int)$row['NS_INCOMING'];
        
        // HITUNG ULANG PASTI AMAN
        $totalOrder = $regularOrder + $addDS + $addNS;
        $balance = $totalOrder - $totalIncoming;
        $balance = max($balance, 0); // Jangan negatif
        
        $completionRate = $totalOrder > 0 ? round(($totalIncoming / $totalOrder) * 100, 1) : 0;
        $dsCompletion = $totalOrder > 0 ? round(($dsIncoming / $totalOrder) * 100, 0) : 0;
        $nsCompletion = $totalOrder > 0 ? round(($nsIncoming / $totalOrder) * 100, 0) : 0;
        
        // STATUS LOGIC
        $status = 'ON_PROGRESS';
        if ($totalIncoming >= $totalOrder) {
            $status = $totalIncoming > $totalOrder ? 'OVER' : 'OK';
        } else {
            $currentHour = (int)date('H');
            if ($currentHour >= 16 && $completionRate < 100) {
                $status = 'DELAY';
            } else if ($completionRate < 70) {
                $status = 'DELAY';
            }
        }
        
        $totalOrderAll += $totalOrder;
        $totalIncomingAll += $totalIncoming;
        
        $data[] = [
            'supplier_code' => $supplierCode,
            'supplier_name' => $row['SUPPLIER_NAME'] ?? '',
            'pic_order' => $row['PIC_ORDER'] ?? 'System',
            'total_order' => $totalOrder,
            'regular_order' => $regularOrder,
            'add_ds' => $addDS,
            'add_ns' => $addNS,
            'total_incoming' => $totalIncoming,
            'ds_incoming' => $dsIncoming,
            'ns_incoming' => $nsIncoming,
            'ds_completion' => min($dsCompletion, 100),
            'ns_completion' => min($nsCompletion, 100),
            'completion_rate' => min($completionRate, 100),
            'balance' => $balance,
            'remark_ds' => $row['REMARK_DS'] ?? '',
            'remark_ns' => $row['REMARK_NS'] ?? '',
            'timestamp' => date('H:i:s'),
            'date' => $date,
            'STATUS' => $status
        ];
    }
    
    sqlsrv_free_stmt($stmt);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total_order_all' => $totalOrderAll,
        'total_incoming_all' => $totalIncomingAll,
        'count' => count($data),
        'date' => $date
    ], JSON_NUMERIC_CHECK);
    
} catch (Exception $e) {
    error_log("LIVE DASHBOARD ERROR: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => $e->getMessage(),
        'count' => 0
    ]);
}

@sqlsrv_close($conn);
?>
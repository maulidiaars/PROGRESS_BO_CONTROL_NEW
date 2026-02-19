<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/status_logic.php'; // IMPORT STATUS LOGIC!

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$date = $_GET['date'] ?? date('Ymd');

try {
    // ===========================================
    // QUERY FINAL - HITUNG INCOMING DENGAN BENAR (SELISIH PER JAM)
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
            
            MAX(o.ETA) as ETA, -- AMBIL ETA UNTUK STATUS
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
    
    -- INCOMING per PART: hitung INCREMENTAL (SELISIH) dulu, baru di-SUM per SUPPLIER
    IncomingByPart AS (
        SELECT 
            o.SUPPLIER_CODE,
            ub.PART_NO,
            ub.DATE,
            ub.HOUR,
            ub.TRAN_QTY,
            ROW_NUMBER() OVER (
                PARTITION BY ub.DATE, ub.PART_NO, ub.HOUR 
                ORDER BY ub.ID_ORDER DESC
            ) as rn
        FROM T_ORDER o
        INNER JOIN T_UPDATE_BO ub ON 
            ub.DATE = o.DELV_DATE 
            AND ub.PART_NO = o.PART_NO
        WHERE o.DELV_DATE = ?
        AND o.SUPPLIER_CODE IS NOT NULL
    ),
    
    LatestIncoming AS (
        SELECT * FROM IncomingByPart WHERE rn = 1
    ),
    
    IncomingWithDelta AS (
        SELECT 
            DATE,
            PART_NO,
            SUPPLIER_CODE,
            HOUR,
            TRAN_QTY,
            CASE 
                WHEN HOUR = 7 THEN TRAN_QTY
                WHEN HOUR = 21 THEN TRAN_QTY
                ELSE 
                    TRAN_QTY - ISNULL(LAG(TRAN_QTY, 1) OVER (
                        PARTITION BY DATE, PART_NO 
                        ORDER BY HOUR
                    ), 0)
            END as INCOMING_QTY
        FROM LatestIncoming
    ),
    
    IncomingSummary AS (
        SELECT 
            SUPPLIER_CODE,
            SUM(CASE WHEN HOUR BETWEEN 7 AND 20 THEN INCOMING_QTY ELSE 0 END) as DS_INCOMING,
            SUM(CASE WHEN HOUR BETWEEN 21 AND 23 OR HOUR BETWEEN 0 AND 6 THEN INCOMING_QTY ELSE 0 END) as NS_INCOMING,
            SUM(INCOMING_QTY) as TOTAL_INCOMING
        FROM IncomingWithDelta
        GROUP BY SUPPLIER_CODE
    )
    
    SELECT 
        o.SUPPLIER_CODE,
        o.SUPPLIER_NAME,
        ISNULL(p.PIC_ORDER, 'System') as PIC_ORDER,
        o.ETA,
        
        -- ORDER
        o.REGULAR_ORDER_QTY,
        o.ADD_DS,
        o.ADD_NS,
        o.TOTAL_ORDER_QTY,
        
        -- INCOMING (SUDAH PAKE LOGIKA SELISIH)
        ISNULL(i.DS_INCOMING, 0) as DS_INCOMING,
        ISNULL(i.NS_INCOMING, 0) as NS_INCOMING,
        ISNULL(i.TOTAL_INCOMING, 0) as TOTAL_INCOMING,
        
        -- COMPLETION RATE = INCOMING / TOTAL_ORDER
        CASE 
            WHEN o.TOTAL_ORDER_QTY > 0 
            THEN ROUND((ISNULL(i.TOTAL_INCOMING, 0) * 100.0) / o.TOTAL_ORDER_QTY, 1)
            ELSE 0 
        END as COMPLETION_RATE,
        
        -- BALANCE = TOTAL_ORDER - INCOMING
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
    ORDER BY 
        -- PRIORITAS: DELAY > OVER > ON PROGRESS > OK
        CASE 
            WHEN o.TOTAL_ORDER_QTY - ISNULL(i.TOTAL_INCOMING, 0) > 0 
                 AND o.TOTAL_ORDER_QTY > ISNULL(i.TOTAL_INCOMING, 0) 
            THEN 1
            WHEN ISNULL(i.TOTAL_INCOMING, 0) > o.TOTAL_ORDER_QTY THEN 2
            WHEN ISNULL(i.TOTAL_INCOMING, 0) = o.TOTAL_ORDER_QTY THEN 4
            ELSE 3
        END,
        o.SUPPLIER_CODE
    ";
    
    $stmt = sqlsrv_query($conn, $sql, [$date, $date]);
    
    if (!$stmt) {
        throw new Exception('Query failed: ' . print_r(sqlsrv_errors(), true));
    }
    
    $data = [];
    $totalOrderAll = 0;
    $totalIncomingAll = 0;
    
    $currentHour = intval(date('H'));
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $supplierCode = $row['SUPPLIER_CODE'] ?? '';
        if (empty($supplierCode)) continue;
        
        // PASTIKAN NILAI
        $regularOrder = (int)$row['REGULAR_ORDER_QTY'];
        $addDS = (int)$row['ADD_DS'];
        $addNS = (int)$row['ADD_NS'];
        $totalOrder = (int)$row['TOTAL_ORDER_QTY'];
        $totalIncoming = (int)$row['TOTAL_INCOMING'];
        $dsIncoming = (int)$row['DS_INCOMING'];
        $nsIncoming = (int)$row['NS_INCOMING'];
        $eta = $row['ETA'] ?? '';
        
        // ✅ TOTAL ORDER = REGULER + ADD
        $totalOrder = $regularOrder + $addDS + $addNS;
        
        // ✅ BALANCE = TOTAL ORDER - INCOMING (JANGAN NEGATIF)
        $balance = max($totalOrder - $totalIncoming, 0);
        
        // ✅ COMPLETION RATE (0-100)
        $completionRate = $totalOrder > 0 ? round(($totalIncoming / $totalOrder) * 100, 1) : 0;
        $dsCompletion = $totalOrder > 0 ? round(($dsIncoming / $totalOrder) * 100, 0) : 0;
        $nsCompletion = $totalOrder > 0 ? round(($nsIncoming / $totalOrder) * 100, 0) : 0;
        
        // ========== 🛡️ STATUS LOGIC - TAMENG JAM 16:00 ==========
        $status = 'ON_PROGRESS'; // DEFAULT
        
        // RULE 1: OVER (Incoming > Order)
        if ($totalIncoming > $totalOrder) {
            $status = 'OVER';
        }
        // RULE 2: COMPLETED (Incoming = Order)
        elseif ($totalIncoming == $totalOrder && $totalOrder > 0) {
            $status = 'OK';
        }
        // RULE 3: NO ORDERS AT ALL
        elseif ($totalOrder == 0) {
            $status = 'OK';
        }
        // RULE 4: INCOMING < ORDER (POTENSI DELAY)
        elseif ($totalIncoming < $totalOrder) {
            
            // ✅ CEK APAKAH TANGGAL INI HARI INI?
            if (isToday($date)) {
                // ✅ TAMENG HANYA UNTUK HARI INI
                if ($currentHour < 16) {
                    $status = 'ON_PROGRESS'; // SEBELUM JAM 16:00 → ON PROGRESS
                } else {
                    $status = 'DELAY'; // SESUDAH JAM 16:00 → DELAY
                }
            } else {
                // ❌ BUKAN HARI INI → LANGSUNG DELAY (TIDAK ADA TAMENG)
                $status = 'DELAY';
            }
        }
        
        // ========== STATUS D/S & N/S (TAMENG JUGA) ==========
        $dsStatus = 'ON_PROGRESS';
        $nsStatus = 'ON_PROGRESS';
        
        // DS STATUS
        if ($dsIncoming > ($regularOrder + $addDS)) {
            $dsStatus = 'OVER';
        } elseif ($dsIncoming == ($regularOrder + $addDS) && ($regularOrder + $addDS) > 0) {
            $dsStatus = 'OK';
        } elseif (($regularOrder + $addDS) == 0) {
            $dsStatus = 'OK';
        } elseif ($dsIncoming < ($regularOrder + $addDS)) {
            if (isToday($date) && $currentHour < 16) {
                $dsStatus = 'ON_PROGRESS';
            } else {
                $dsStatus = 'DELAY';
            }
        }
        
        // NS STATUS
        if ($nsIncoming > ($regularOrder ? 0 : 0 + $addNS)) { // Simplified
            $nsStatus = 'OVER';
        } elseif ($nsIncoming == ($regularOrder ? 0 : 0 + $addNS) && ($regularOrder ? 0 : 0 + $addNS) > 0) {
            $nsStatus = 'OK';
        } elseif (($regularOrder ? 0 : 0 + $addNS) == 0) {
            $nsStatus = 'OK';
        } elseif ($nsIncoming < ($regularOrder ? 0 : 0 + $addNS)) {
            if (isToday($date) && $currentHour < 16) {
                $nsStatus = 'ON_PROGRESS';
            } else {
                $nsStatus = 'DELAY';
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
            'STATUS' => $status,
            'DS_STATUS' => $dsStatus,
            'NS_STATUS' => $nsStatus,
            'is_today' => isToday($date) ? 'YES' : 'NO',
            'current_hour' => $currentHour
        ];
    }
    
    sqlsrv_free_stmt($stmt);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total_order_all' => $totalOrderAll,
        'total_incoming_all' => $totalIncomingAll,
        'count' => count($data),
        'date' => $date,
        'current_hour' => $currentHour,
        'tameng_active' => ($currentHour < 16) ? 'ON' : 'OFF'
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
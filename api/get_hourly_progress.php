<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$date = $_GET['date'] ?? date('Ymd');
$shift = $_GET['shift'] ?? 'DS';

try {
    $data = [];
    
    // ✅ STEP 1: Hitung TARGET per jam dari ETA + ADD ORDER!
    $targetPerHour = [];
    
    if ($shift === 'DS') {
        for ($hour = 7; $hour <= 20; $hour++) {
            $targetPerHour[$hour] = 0;
        }
    } else {
        for ($hour = 21; $hour <= 23; $hour++) $targetPerHour[$hour] = 0;
        for ($hour = 0; $hour <= 6; $hour++) $targetPerHour[$hour] = 0;
    }
    
    // Ambil target dari ETA (REGULER ORDER)
    $sqlTargetETA = "
    SELECT 
        TRY_CAST(LEFT(ETA, 2) AS INT) as HOUR,
        SUM(ORD_QTY) as TOTAL_ORDER
    FROM T_ORDER
    WHERE DELV_DATE = ?
        AND ETA IS NOT NULL 
        AND ETA != ''
        AND TRY_CAST(LEFT(ETA, 2) AS INT) IS NOT NULL
    ";
    
    if ($shift === 'DS') {
        $sqlTargetETA .= " AND TRY_CAST(LEFT(ETA, 2) AS INT) BETWEEN 7 AND 20";
    } else {
        $sqlTargetETA .= " AND (TRY_CAST(LEFT(ETA, 2) AS INT) >= 21 OR TRY_CAST(LEFT(ETA, 2) AS INT) BETWEEN 0 AND 6)";
    }
    
    $sqlTargetETA .= " GROUP BY TRY_CAST(LEFT(ETA, 2) AS INT)";
    
    $stmtTarget = sqlsrv_query($conn, $sqlTargetETA, [$date]);
    if ($stmtTarget) {
        while ($row = sqlsrv_fetch_array($stmtTarget, SQLSRV_FETCH_ASSOC)) {
            $hour = (int)$row['HOUR'];
            if (isset($targetPerHour[$hour])) {
                $targetPerHour[$hour] += (int)$row['TOTAL_ORDER'];
            }
        }
        sqlsrv_free_stmt($stmtTarget);
    }
    
    // ✅ TAMBAH ADD ORDER DISTRIBUTION!
    $sqlAddOrder = "
    SELECT HOUR, SUM(QUANTITY) as ADD_QTY
    FROM T_ADD_ORDER_DISTRIBUTION
    WHERE DATE = ? AND TYPE = ?
    GROUP BY HOUR
    ";
    
    $shiftType = $shift === 'DS' ? 'DS' : 'NS';
    $stmtAdd = sqlsrv_query($conn, $sqlAddOrder, [$date, $shiftType]);
    
    if ($stmtAdd) {
        while ($row = sqlsrv_fetch_array($stmtAdd, SQLSRV_FETCH_ASSOC)) {
            $hour = (int)$row['HOUR'];
            if (isset($targetPerHour[$hour])) {
                $targetPerHour[$hour] += (int)$row['ADD_QTY']; // ✅ NAMBAH ADD ORDER!
            }
        }
        sqlsrv_free_stmt($stmtAdd);
    }
    
    // ✅ STEP 2: Ambil semua incoming untuk hari ini (PURE DARI BO)
    $allData = [];
    $sqlAll = "SELECT PART_NO, HOUR, TRAN_QTY FROM T_UPDATE_BO WHERE DATE = ? ORDER BY PART_NO, HOUR";
    $stmtAll = sqlsrv_query($conn, $sqlAll, [$date]);
    
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
    
    // ✅ STEP 3: Hitung incoming kumulatif per jam (PAKAI SELISIH!)
    if ($shift === 'DS') {
        $cumulativeIncoming = 0;
        
        for ($hour = 7; $hour <= 20; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourlyIncoming = 0;
            
            foreach ($allData as $partNo => $hourData) {
                if (isset($hourData[$hour])) {
                    $latestQty = $hourData[$hour];
                    $prevQty = 0;
                    
                    for ($h = $hour - 1; $h >= 7; $h--) {
                        if (isset($hourData[$h])) {
                            $prevQty = $hourData[$h];
                            break;
                        }
                    }
                    
                    $hourlyIncoming += max(0, $latestQty - $prevQty);
                }
            }
            
            $cumulativeIncoming += $hourlyIncoming;
            
            $cumulativeTarget = 0;
            for ($h = 7; $h <= $hour; $h++) {
                $cumulativeTarget += $targetPerHour[$h] ?? 0;
            }
            
            $data[] = [
                'hour' => $hourStr,
                'incoming' => $hourlyIncoming,           // ✅ PURE dari BO
                'cumulative_incoming' => $cumulativeIncoming,
                'target' => $targetPerHour[$hour] ?? 0,  // ✅ SUDAH + ADD ORDER
                'cumulative_target' => $cumulativeTarget
            ];
        }
    } else {
        // Night shift
        $cumulativeIncoming = 0;
        $cumulativeTarget = 0;
        $nsHours = array_merge(range(21, 23), range(0, 6));
        sort($nsHours);
        
        foreach ($nsHours as $hour) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourlyIncoming = 0;
            
            foreach ($allData as $partNo => $hourData) {
                if (isset($hourData[$hour])) {
                    $latestQty = $hourData[$hour];
                    $prevQty = 0;
                    
                    // Cari nilai sebelumnya di NS
                    if ($hour >= 21) {
                        for ($h = $hour - 1; $h >= 21; $h--) {
                            if (isset($hourData[$h])) {
                                $prevQty = $hourData[$h];
                                break;
                            }
                        }
                    } else {
                        // Cek di jam 23,22,21 dulu
                        for ($h = 23; $h >= 21; $h--) {
                            if (isset($hourData[$h])) {
                                $prevQty = $hourData[$h];
                                break;
                            }
                        }
                        // Kalo gak ada, cek jam sebelumnya di 0-6
                        if ($prevQty == 0) {
                            for ($h = $hour - 1; $h >= 0; $h--) {
                                if (isset($hourData[$h])) {
                                    $prevQty = $hourData[$h];
                                    break;
                                }
                            }
                        }
                    }
                    
                    $hourlyIncoming += max(0, $latestQty - $prevQty);
                }
            }
            
            $cumulativeIncoming += $hourlyIncoming;
            $cumulativeTarget += $targetPerHour[$hour] ?? 0;
            
            $data[] = [
                'hour' => $hourStr,
                'incoming' => $hourlyIncoming,
                'cumulative_incoming' => $cumulativeIncoming,
                'target' => $targetPerHour[$hour] ?? 0,
                'cumulative_target' => $cumulativeTarget
            ];
        }
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
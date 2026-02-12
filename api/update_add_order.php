<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

session_start();
$currentUser = $_SESSION['name'] ?? 'SYSTEM';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $date = $_POST['date'] ?? '';
    $supplier_code = $_POST['supplier_code'] ?? '';
    $part_no = $_POST['part_no'] ?? '';
    $remark = $_POST['remark'] ?? '';
    $action = $_POST['action'] ?? 'add';
    
    $hours_data = $_POST['hours_data'] ?? '{}';
    
    if (empty($type) || empty($date) || empty($supplier_code) || empty($part_no)) {
        $response['message'] = 'Data tidak lengkap';
        echo json_encode($response);
        exit;
    }
    
    $remark = trim($remark);
    
    $conn = Database::getConnection();
    
    if ($conn === false) {
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit;
    }
    
    try {
        if ($action === 'add' || $action === 'update') {
            $hours_array = json_decode($hours_data, true);
            
            if (empty($hours_array) || !is_array($hours_array)) {
                $response['message'] = 'Tidak ada jam yang dipilih';
                echo json_encode($response);
                exit;
            }
            
            // ========== PROSES PER JAM - UPDATE ATAU INSERT ==========
            $type_upper = strtoupper($type);
            
            foreach ($hours_array as $hour => $qty) {
                $hour_int = intval($hour);
                $qty_int = intval($qty);
                
                if ($qty_int > 0) {
                    // Cek apakah sudah ada data untuk jam ini
                    $check_sql = "SELECT ID, QUANTITY FROM T_ADD_ORDER_DISTRIBUTION 
                                  WHERE DATE = ? AND SUPPLIER_CODE = ? 
                                  AND PART_NO = ? AND TYPE = ? AND HOUR = ?";
                    $check_params = [$date, $supplier_code, $part_no, $type_upper, $hour_int];
                    $check_stmt = sqlsrv_query($conn, $check_sql, $check_params);
                    
                    if ($check_stmt && sqlsrv_has_rows($check_stmt)) {
                        // ✅ UPDATE - TAMBAH quantity (tidak replace!)
                        $row = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);
                        $new_qty = $row['QUANTITY'] + $qty_int;
                        
                        $update_sql = "UPDATE T_ADD_ORDER_DISTRIBUTION 
                                      SET QUANTITY = ?, 
                                          UPDATED_AT = GETDATE(), 
                                          CREATED_BY = ?
                                      WHERE ID = ?";
                        $update_params = [$new_qty, $currentUser, $row['ID']];
                        sqlsrv_query($conn, $update_sql, $update_params);
                        
                    } else {
                        // ✅ INSERT - jam baru
                        $insert_sql = "INSERT INTO T_ADD_ORDER_DISTRIBUTION 
                                      (DATE, SUPPLIER_CODE, PART_NO, TYPE, HOUR, QUANTITY, CREATED_BY, CREATED_AT)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())";
                        $insert_params = [$date, $supplier_code, $part_no, $type_upper, 
                                        $hour_int, $qty_int, $currentUser];
                        sqlsrv_query($conn, $insert_sql, $insert_params);
                    }
                }
            }
            
            // ========== HITUNG TOTAL DARI DATABASE ==========
            $total_sql = "SELECT SUM(QUANTITY) as TOTAL_QTY 
                          FROM T_ADD_ORDER_DISTRIBUTION 
                          WHERE DATE = ? AND SUPPLIER_CODE = ? 
                          AND PART_NO = ? AND TYPE = ?";
            $total_params = [$date, $supplier_code, $part_no, $type_upper];
            $total_stmt = sqlsrv_query($conn, $total_sql, $total_params);
            $total_row = sqlsrv_fetch_array($total_stmt, SQLSRV_FETCH_ASSOC);
            $total_qty_db = intval($total_row['TOTAL_QTY'] ?? 0);
            
            // ========== UPDATE T_ORDER ==========
            // Cek apakah data sudah ada
            $check_order_sql = "SELECT ID_UPDATE_BO FROM T_ORDER 
                               WHERE DELV_DATE = ? AND SUPPLIER_CODE = ? AND PART_NO = ?";
            $check_order_params = [$date, $supplier_code, $part_no];
            $check_order_stmt = sqlsrv_query($conn, $check_order_sql, $check_order_params);
            $order_exists = ($check_order_stmt && sqlsrv_has_rows($check_order_stmt));
            
            if ($type === 'ds') {
                if ($order_exists) {
                    $sql = "UPDATE T_ORDER 
                           SET ADD_DS = ?, 
                               REMARK_DS = ?,
                               LAST_ADD_DS_QTY = ?,
                               LAST_ADD_DS_BY = ?,
                               LAST_ADD_DS_AT = GETDATE()
                           WHERE DELV_DATE = ? 
                           AND SUPPLIER_CODE = ? 
                           AND PART_NO = ?";
                    $params = [$total_qty_db, $remark, $total_qty_db, $currentUser, $date, $supplier_code, $part_no];
                } else {
                    // Ambil PART_NAME
                    $part_sql = "SELECT PART_NAME FROM M_PART_NO WHERE PART_NO = ?";
                    $part_stmt = sqlsrv_query($conn, $part_sql, [$part_no]);
                    $part_row = sqlsrv_fetch_array($part_stmt, SQLSRV_FETCH_ASSOC);
                    $part_name = $part_row['PART_NAME'] ?? '';
                    
                    $sql = "INSERT INTO T_ORDER 
                           (DELV_DATE, SUPPLIER_CODE, PART_NO, PART_NAME,
                            ADD_DS, REMARK_DS,
                            LAST_ADD_DS_QTY, LAST_ADD_DS_BY, LAST_ADD_DS_AT)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
                    $params = [$date, $supplier_code, $part_no, $part_name, 
                              $total_qty_db, $remark, $total_qty_db, $currentUser];
                }
            } else {
                if ($order_exists) {
                    $sql = "UPDATE T_ORDER 
                           SET ADD_NS = ?, 
                               REMARK_NS = ?,
                               LAST_ADD_NS_QTY = ?,
                               LAST_ADD_NS_BY = ?,
                               LAST_ADD_NS_AT = GETDATE()
                           WHERE DELV_DATE = ? 
                           AND SUPPLIER_CODE = ? 
                           AND PART_NO = ?";
                    $params = [$total_qty_db, $remark, $total_qty_db, $currentUser, $date, $supplier_code, $part_no];
                } else {
                    $part_sql = "SELECT PART_NAME FROM M_PART_NO WHERE PART_NO = ?";
                    $part_stmt = sqlsrv_query($conn, $part_sql, [$part_no]);
                    $part_row = sqlsrv_fetch_array($part_stmt, SQLSRV_FETCH_ASSOC);
                    $part_name = $part_row['PART_NAME'] ?? '';
                    
                    $sql = "INSERT INTO T_ORDER 
                           (DELV_DATE, SUPPLIER_CODE, PART_NO, PART_NAME,
                            ADD_NS, REMARK_NS,
                            LAST_ADD_NS_QTY, LAST_ADD_NS_BY, LAST_ADD_NS_AT)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
                    $params = [$date, $supplier_code, $part_no, $part_name,
                              $total_qty_db, $remark, $total_qty_db, $currentUser];
                }
            }
            
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception('Gagal menyimpan data: ' . ($errors[0]['message'] ?? 'Unknown'));
            }
            
            $response['success'] = true;
            $response['message'] = 'Add order berhasil disimpan! Total: ' . $total_qty_db . ' pcs';
            $response['total_qty'] = $total_qty_db;
            $response['hours_data'] = $hours_array;
            
        } else if ($action === 'reset') {
            // ========== RESET ==========
            $type_upper = strtoupper($type);
            
            // Hapus semua distribusi
            $delete_dist_sql = "DELETE FROM T_ADD_ORDER_DISTRIBUTION 
                               WHERE DATE = ? AND SUPPLIER_CODE = ? 
                               AND PART_NO = ? AND TYPE = ?";
            $delete_dist_params = [$date, $supplier_code, $part_no, $type_upper];
            sqlsrv_query($conn, $delete_dist_sql, $delete_dist_params);
            
            // Update T_ORDER ke 0
            if ($type === 'ds') {
                $sql = "UPDATE T_ORDER 
                       SET ADD_DS = 0, 
                           REMARK_DS = ?,
                           LAST_ADD_DS_QTY = 0,
                           LAST_ADD_DS_BY = ?,
                           LAST_ADD_DS_AT = GETDATE()
                       WHERE DELV_DATE = ? AND SUPPLIER_CODE = ? AND PART_NO = ?";
            } else {
                $sql = "UPDATE T_ORDER 
                       SET ADD_NS = 0, 
                           REMARK_NS = ?,
                           LAST_ADD_NS_QTY = 0,
                           LAST_ADD_NS_BY = ?,
                           LAST_ADD_NS_AT = GETDATE()
                       WHERE DELV_DATE = ? AND SUPPLIER_CODE = ? AND PART_NO = ?";
            }
            
            $params = [$remark, $currentUser, $date, $supplier_code, $part_no];
            $stmt = sqlsrv_query($conn, $sql, $params);
            
            if ($stmt !== false) {
                $response['success'] = true;
                $response['message'] = 'Add order berhasil direset ke 0';
            }
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
exit;
?>
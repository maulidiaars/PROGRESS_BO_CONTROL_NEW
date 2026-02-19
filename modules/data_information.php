<?php
// modules/data_information.php - VERSION FIX DUAL MODE + PIC TO MUNCUL
session_start();
ob_clean();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/week_logic.php';

if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database belum terkoneksi"]);
    exit;
}

$type = isset($_POST["type"]) ? strtolower(trim($_POST["type"])) : 
       (isset($_GET["type"]) ? strtolower(trim($_GET["type"])) : '');

$response = ["success" => false, "message" => "Aksi tidak dikenal"];
$currentUser = $_SESSION['name'] ?? '';

try {
    // ========================= INPUT DATA INFORMATION =========================
    if ($type === "input") {
        
        $DATE      = date('Ymd');
        $TIME_FROM = $_POST["txt-time1"] ?? date('H:i');
        $PIC_FROM  = $currentUser;
        $ITEM      = trim($_POST["txt-item"] ?? '');
        $REQUEST   = trim($_POST["txt-request"] ?? '');
        $recipients = $_POST["recipients"] ?? '';
        
        // Validasi
        if (empty($ITEM) || empty($REQUEST)) {
            $response["message"] = 'Item dan Request tidak boleh kosong';
            echo json_encode($response);
            exit;
        }
        
        if (empty($recipients)) {
            $response["message"] = 'Pilih minimal satu penerima';
            echo json_encode($response);
            exit;
        }
        
        // Parse recipients
        $recipientArray = [];
        if (is_string($recipients)) {
            if (strtoupper($recipients) === 'ALL') {
                $sqlUsers = "SELECT DISTINCT name FROM M_USER 
                            WHERE name IS NOT NULL 
                            AND LTRIM(RTRIM(name)) != ''
                            AND name != ?
                            ORDER BY name";
                $stmtUsers = sqlsrv_query($conn, $sqlUsers, [$currentUser]);
                if ($stmtUsers) {
                    while ($row = sqlsrv_fetch_array($stmtUsers, SQLSRV_FETCH_ASSOC)) {
                        if (!empty($row['name'])) {
                            $recipientArray[] = trim($row['name']);
                        }
                    }
                    sqlsrv_free_stmt($stmtUsers);
                }
            } else {
                $decoded = json_decode($recipients, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $recipientArray = $decoded;
                } else {
                    $recipientArray = array_filter(
                        array_map('trim', explode(',', $recipients)),
                        function($val) { return !empty($val); }
                    );
                }
            }
        } elseif (is_array($recipients)) {
            $recipientArray = array_filter(
                array_map('trim', $recipients),
                function($val) { return !empty($val); }
            );
        }
        
        // Remove current user from recipients
        $recipientArray = array_filter($recipientArray, function($recipient) use ($currentUser) {
            return $recipient !== $currentUser && !empty($recipient);
        });
        
        $recipientArray = array_unique($recipientArray);
        
        if (empty($recipientArray)) {
            $response["message"] = 'Tidak ada penerima yang valid';
            echo json_encode($response);
            exit;
        }
        
        sort($recipientArray);
        $PIC_TO_COMBINED = implode(', ', $recipientArray);
        
        // Cek duplikasi hanya untuk minggu ini
        $weekInfo = getCurrentWeekInfo();
        $weekStart = $weekInfo['start_date'];
        
        $checkSql = "SELECT COUNT(*) as count FROM T_INFORMATION 
                     WHERE DATE >= ?
                     AND PIC_FROM = ? 
                     AND ITEM = ? 
                     AND PIC_TO = ?";
        
        $checkStmt = sqlsrv_query($conn, $checkSql, [$weekStart, $PIC_FROM, $ITEM, $PIC_TO_COMBINED]);
        $duplicateCount = 0;
        
        if ($checkStmt) {
            $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
            $duplicateCount = (int)$row['count'];
            sqlsrv_free_stmt($checkStmt);
        }
        
        if ($duplicateCount > 0) {
            $response["success"] = false;
            $response["message"] = 'Anda sudah mengirim informasi yang sama dalam minggu ini.';
            $response["duplicate"] = true;
            $response["week_info"] = $weekInfo;
            echo json_encode($response);
            exit;
        }
        
        // Simpan ke T_INFORMATION
        $sql = "INSERT INTO T_INFORMATION 
                (DATE, TIME_FROM, PIC_FROM, PIC_TO, ITEM, REQUEST, STATUS) 
                VALUES (?, ?, ?, ?, ?, ?, 'Open')";
        
        $params = [$DATE, $TIME_FROM, $PIC_FROM, $PIC_TO_COMBINED, $ITEM, $REQUEST];
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt) {
            // Get inserted ID
            $idSql = "SELECT @@IDENTITY AS id";
            $idStmt = sqlsrv_query($conn, $idSql);
            $new_id = 0;
            
            if ($idStmt) {
                $idRow = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC);
                $new_id = (int)($idRow['id'] ?? 0);
                sqlsrv_free_stmt($idStmt);
            }
            
            // Insert ke user_notification_read untuk setiap recipient
            foreach ($recipientArray as $recipient) {
                if (empty($recipient)) continue;
                $notifSql = "INSERT INTO user_notification_read (user_id, notification_id, created_at) 
                             VALUES (?, ?, GETDATE())";
                sqlsrv_query($conn, $notifSql, [$recipient, $new_id]);
            }
            
            // Insert ke history
            $historySql = "INSERT INTO information_status_history 
                           (information_id, status, changed_by, remark, changed_at) 
                           VALUES (?, 'Open', ?, 'Informasi baru dibuat', GETDATE())";
            sqlsrv_query($conn, $historySql, [$new_id, $currentUser]);
            
            $response["success"] = true;
            $response["message"] = 'Data berhasil dikirim ke ' . count($recipientArray) . ' penerima';
            $response["id"] = $new_id;
            $response["recipient_count"] = count($recipientArray);
            
        } else {
            $errors = sqlsrv_errors();
            $response["message"] = "SQL Error: " . print_r($errors, true);
        }
        
        echo json_encode($response);
        exit;
    }
    
    // ========================= UPDATE FROM (PENGIRIM) =========================
    else if ($type === "update-from") {
        
        $ID_INFORMATION = (int)($_POST["txt-id-information"] ?? 0);
        $TIME_FROM = $_POST["txt-timefrom-update"] ?? date('H:i');
        $PIC_FROM = $_POST["txt-picfrom-update"] ?? $currentUser;
        $ITEM = trim($_POST["txt-item-update"] ?? '');
        $REQUEST = trim($_POST["txt-request-update"] ?? '');
        
        if ($ID_INFORMATION <= 0) {
            $response["message"] = 'ID Information tidak valid';
            echo json_encode($response);
            exit;
        }
        
        if (empty($ITEM) || empty($REQUEST)) {
            $response["message"] = 'Item dan Request tidak boleh kosong';
            echo json_encode($response);
            exit;
        }
        
        // Cek data
        $checkSql = "SELECT PIC_FROM, STATUS, DATE FROM T_INFORMATION 
                     WHERE ID_INFORMATION = ?";
        
        $checkStmt = sqlsrv_query($conn, $checkSql, [$ID_INFORMATION]);
        
        if (!$checkStmt) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        $info = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if (!$info) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        // Validasi: hanya PIC_FROM yang bisa update
        if ($info['PIC_FROM'] !== $currentUser) {
            $response["message"] = 'Anda tidak berhak mengedit informasi ini';
            echo json_encode($response);
            exit;
        }
        
        // Validasi: tidak bisa edit jika sudah On Progress atau Closed
        if ($info['STATUS'] === 'On Progress' || $info['STATUS'] === 'Closed') {
            $response["message"] = 'Tidak bisa mengedit informasi yang sudah diproses atau ditutup';
            echo json_encode($response);
            exit;
        }
        
        // Update informasi
        $updateSql = "UPDATE T_INFORMATION 
                      SET TIME_FROM = ?, 
                          ITEM = ?, 
                          REQUEST = ?
                      WHERE ID_INFORMATION = ?";
        
        $params = [$TIME_FROM, $ITEM, $REQUEST, $ID_INFORMATION];
        $updateStmt = sqlsrv_query($conn, $updateSql, $params);
        
        if ($updateStmt) {
            // Insert ke history
            $historySql = "INSERT INTO information_status_history 
                           (information_id, status, changed_by, remark, changed_at) 
                           VALUES (?, ?, ?, 'Informasi diedit oleh pengirim', GETDATE())";
            sqlsrv_query($conn, $historySql, [$ID_INFORMATION, $info['STATUS'], $currentUser]);
            
            $response["success"] = true;
            $response["message"] = 'Informasi berhasil diupdate';
        } else {
            $response["message"] = 'Gagal update informasi';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // ========================= UPDATE TO (PENERIMA) =========================
    else if ($type === "update-to") {
        
        $ID_INFORMATION = (int)($_POST["txt-id-information2"] ?? 0);
        $TIME_TO = $_POST["txt-timeto-update"] ?? date('H:i');
        $PIC_TO = $_POST["txt-picto-update"] ?? $currentUser;
        $REMARK = trim($_POST["txt-remark-update"] ?? '');
        $ACTION_TYPE = $_POST["action_type"] ?? 'on_progress';
        
        if ($ID_INFORMATION <= 0) {
            $response["message"] = 'ID Information tidak valid';
            echo json_encode($response);
            exit;
        }
        
        // Cek data
        $checkSql = "SELECT PIC_TO, STATUS, ITEM, REQUEST, PIC_FROM, DATE 
                     FROM T_INFORMATION 
                     WHERE ID_INFORMATION = ?";
        
        $checkStmt = sqlsrv_query($conn, $checkSql, [$ID_INFORMATION]);
        
        if (!$checkStmt) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        $info = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if (!$info) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        // Cek apakah user adalah salah satu penerima
        $recipients = explode(', ', $info['PIC_TO']);
        $isRecipient = in_array($currentUser, $recipients);
        
        if (!$isRecipient) {
            $response["message"] = 'Anda tidak berhak mengupdate informasi ini';
            echo json_encode($response);
            exit;
        }
        
        // Cek status
        if ($info['STATUS'] === 'Closed') {
            $response["message"] = 'Informasi ini sudah ditutup';
            echo json_encode($response);
            exit;
        }
        
        // Tentukan status baru
        $new_status = ($ACTION_TYPE === 'closed') ? 'Closed' : 'On Progress';
        
        // Validasi: untuk Closed, remark wajib
        if ($ACTION_TYPE === 'closed' && empty($REMARK)) {
            $response["message"] = 'Remark wajib diisi untuk menutup informasi';
            echo json_encode($response);
            exit;
        }
        
        // Update informasi
        $updateSql = "UPDATE T_INFORMATION 
                      SET TIME_TO = ?, 
                          REMARK = ?, 
                          STATUS = ?
                      WHERE ID_INFORMATION = ?";
        
        $params = [$TIME_TO, $REMARK, $new_status, $ID_INFORMATION];
        $updateStmt = sqlsrv_query($conn, $updateSql, $params);
        
        if ($updateStmt) {
            // Insert ke history
            $historySql = "INSERT INTO information_status_history 
                           (information_id, status, changed_by, remark, changed_at) 
                           VALUES (?, ?, ?, ?, GETDATE())";
            sqlsrv_query($conn, $historySql, [$ID_INFORMATION, $new_status, $currentUser, $REMARK]);
            
            // Update notifikasi untuk user ini
            $notifSql = "UPDATE user_notification_read SET read_at = GETDATE() 
                         WHERE user_id = ? AND notification_id = ?";
            sqlsrv_query($conn, $notifSql, [$currentUser, $ID_INFORMATION]);
            
            // Jika status Closed, update notifikasi untuk semua recipient lainnya
            if ($new_status === 'Closed') {
                foreach ($recipients as $recipient) {
                    if ($recipient !== $currentUser) {
                        $notifAllSql = "UPDATE user_notification_read SET read_at = GETDATE() 
                                       WHERE user_id = ? AND notification_id = ? AND read_at IS NULL";
                        sqlsrv_query($conn, $notifAllSql, [$recipient, $ID_INFORMATION]);
                    }
                }
            }
            
            $response["success"] = true;
            $response["message"] = "Status berhasil diupdate ke " . $new_status;
            $response["new_status"] = $new_status;
        } else {
            $response["message"] = 'Gagal update status';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // ========================= DELETE INFORMATION =========================
    else if ($type === "delete") {
        
        $ID_INFORMATION = (int)($_POST["id_information"] ?? 0);
        
        if ($ID_INFORMATION <= 0) {
            $response["message"] = 'ID Information tidak valid';
            echo json_encode($response);
            exit;
        }
        
        $checkSql = "SELECT PIC_FROM FROM T_INFORMATION WHERE ID_INFORMATION = ?";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$ID_INFORMATION]);
        
        if (!$checkStmt) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        $info = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        if (!$info) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        // Validasi: hanya PIC_FROM yang bisa delete
        if ($info['PIC_FROM'] !== $currentUser) {
            $response["message"] = 'Anda tidak berhak menghapus informasi ini';
            echo json_encode($response);
            exit;
        }
        
        // Delete dari history dulu
        $deleteHistorySql = "DELETE FROM information_status_history WHERE information_id = ?";
        sqlsrv_query($conn, $deleteHistorySql, [$ID_INFORMATION]);
        
        // Delete dari user_notification_read
        $deleteNotifSql = "DELETE FROM user_notification_read WHERE notification_id = ?";
        sqlsrv_query($conn, $deleteNotifSql, [$ID_INFORMATION]);
        
        // Delete informasi
        $deleteSql = "DELETE FROM T_INFORMATION WHERE ID_INFORMATION = ?";
        $deleteStmt = sqlsrv_query($conn, $deleteSql, [$ID_INFORMATION]);
        
        if ($deleteStmt) {
            $response["success"] = true;
            $response["message"] = 'Informasi berhasil dihapus';
        } else {
            $response["message"] = 'Gagal menghapus informasi';
        }
        
        echo json_encode($response);
        exit;
    }
    
    else if ($type === "fetch") {
        
        $DATE1 = $_GET["date1"] ?? '';
        $DATE2 = $_GET["date2"] ?? '';
        
        // ========== LOGIC FILTER TANGGAL ==========
        $filterMode = 'default'; // default = minggu ini
        
        // Cek apakah user melakukan filter manual
        $userFiltered = !empty($DATE1) && !empty($DATE2);
        
        // Cek apakah user mengubah date picker dari default
        $today = date('Y-m-d');
        $isDefaultDate = ($DATE1 === $today && $DATE2 === $today);
        
        if ($userFiltered && !$isDefaultDate) {
            // USER MELAKUKAN FILTER MANUAL (bukan hari ini)
            $filterMode = 'manual';
            $startDate = str_replace('-', '', $DATE1);
            $endDate = str_replace('-', '', $DATE2);
        } else {
            // DEFAULT: TAMPILKAN SENIN SAMPAI MINGGU MINGGU INI
            $weekRange = getCurrentWeekRange();
            $startDate = $weekRange['start']; // Format: 20260216
            $endDate = $weekRange['end'];     // Format: 20260222
        }
        
        // ========== BUILD QUERY ==========
        $sql = "SELECT
                    ID_INFORMATION, 
                    DATE, 
                    TIME_FROM, 
                    PIC_FROM,
                    PIC_TO, 
                    ITEM, 
                    REQUEST, 
                    TIME_TO, 
                    STATUS, 
                    REMARK,
                    CASE
                        WHEN CHARINDEX(?, PIC_TO) > 0 THEN 'recipient'
                        WHEN PIC_FROM = ? THEN 'sender'
                        ELSE 'viewer'
                    END as user_role,
                    (SELECT TOP 1 read_at FROM user_notification_read 
                     WHERE user_id = ? AND notification_id = ID_INFORMATION) as read_at
                FROM T_INFORMATION
                WHERE DATE BETWEEN ? AND ?
                ORDER BY DATE DESC, TIME_FROM DESC";
        
        $params = [
            $currentUser,  // untuk CHARINDEX
            $currentUser,  // untuk PIC_FROM = ?
            $currentUser,  // untuk user_notification_read
            $startDate,    // DATE BETWEEN start
            $endDate       // DATE BETWEEN end
        ];
        
        // Eksekusi query
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        
        if (!$stmt) {
            $response["error"] = sqlsrv_errors();
            echo json_encode($response);
            exit;
        }
        
        sqlsrv_execute($stmt);
        
        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Format date buat display (tambah strip biar cantik)
            if (isset($row['DATE']) && is_numeric($row['DATE'])) {
                $d = (string)$row['DATE'];
                if (strlen($d) === 8) {
                    $row['DATE'] = substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2);
                }
            }
            
            $row['TIME_TO'] = $row['TIME_TO'] ?: '-';
            $row['REMARK'] = $row['REMARK'] ?: '-';
            
            // Is unread? - Hanya untuk penerima dan bukan dari diri sendiri
            $isRecipient = ($row['user_role'] === 'recipient');
            $isFromSelf = ($row['PIC_FROM'] === $currentUser);
            $row['IS_UNREAD'] = ($row['read_at'] === null && $isRecipient && !$isFromSelf && $row['STATUS'] !== 'Closed') ? 1 : 0;
            
            $data[] = $row;
        }
        
        $response["success"] = true;
        $response["data"] = $data;
        $response["count"] = count($data);
        $response["current_user"] = $currentUser;
        $response["filter_mode"] = $filterMode;
        $response["date_range"] = [
            'start' => $startDate,
            'end' => $endDate,
            'start_formatted' => date('Y-m-d', strtotime($startDate)),
            'end_formatted' => date('Y-m-d', strtotime($endDate))
        ];
        
        // Tambah info minggu untuk debugging
        if ($filterMode === 'default') {
            $weekInfo = getCurrentWeekInfo();
            $response["week_info"] = $weekInfo;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // ========================= GET RECIPIENTS =========================
    else if ($type === "get-recipients") {
        
        $sql = "SELECT DISTINCT name FROM M_USER 
                WHERE name IS NOT NULL 
                AND LTRIM(RTRIM(name)) != ''
                AND name != ?
                ORDER BY name";
        
        $stmt = sqlsrv_query($conn, $sql, [$currentUser]);
        
        $users = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $users[] = [
                    'name' => trim($row['name']),
                    'value' => trim($row['name'])
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        
        $response["success"] = true;
        $response["users"] = $users;
        $response["count"] = count($users);
        
        echo json_encode($response);
        exit;
    }
    
    // ========================= GET SINGLE INFORMATION =========================
    else if ($type === "get-single") {
        
        $id = (int)($_GET["id"] ?? 0);
        
        if ($id <= 0) {
            $response["message"] = 'ID tidak valid';
            echo json_encode($response);
            exit;
        }
        
        $sql = "SELECT * FROM T_INFORMATION WHERE ID_INFORMATION = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        
        $info = null;
        if ($stmt) {
            $info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
        }
        
        if (!$info) {
            $response["message"] = 'Data tidak ditemukan';
            echo json_encode($response);
            exit;
        }
        
        // Format date
        if (isset($info['DATE']) && is_numeric($info['DATE'])) {
            $d = (string)$info['DATE'];
            if (strlen($d) === 8) {
                $info['DATE'] = substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2);
            }
        }
        
        // Cek user role
        $recipients = explode(', ', $info['PIC_TO']);
        $info['user_role'] = in_array($currentUser, $recipients) ? 'recipient' : 
                           ($info['PIC_FROM'] === $currentUser ? 'sender' : 'viewer');
        
        $response["success"] = true;
        $response["data"] = $info;
        
        echo json_encode($response);
        exit;
    }
    
    else {
        $response["message"] = "Tipe aksi tidak dikenal: $type";
        echo json_encode($response);
        exit;
    }

} catch (Exception $e) {
    $response["message"] = 'Server error: ' . $e->getMessage();
    $response["trace"] = $e->getTraceAsString();
    echo json_encode($response);
    exit;
}

if ($conn) {
    sqlsrv_close($conn);
}
?>
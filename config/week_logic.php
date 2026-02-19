<?php
// config/week_logic.php - VERSION DENGAN AUTO-RESET NOTIF

/**
 * Fungsi untuk menentukan awal dan akhir minggu (Senin-Minggu)
 */
function getWeekRange($date = null) {
    if (!$date) $date = date('Ymd');
    
    $timestamp = strtotime($date);
    if (!$timestamp) $timestamp = time();
    
    $monday = date('Ymd', strtotime('monday this week', $timestamp));
    $sunday = date('Ymd', strtotime('sunday this week', $timestamp));
    
    return [
        'start' => $monday,
        'end' => $sunday,
        'current_date' => date('Ymd', $timestamp),
        'week_number' => date('W', $timestamp),
        'year' => date('Y', $timestamp)
    ];
}

/**
 * Dapatkan range minggu dalam format YYYYMMDD (untuk query)
 */
function getCurrentWeekRange() {
    $weekRange = getWeekRange();
    return [
        'start' => $weekRange['start'], // Format: 20260216
        'end' => $weekRange['end']      // Format: 20260222
    ];
}

/**
 * Dapatkan range minggu dalam format YYYY-MM-DD (untuk display)
 */
function getCurrentWeekRangeFormatted() {
    $weekRange = getWeekRange();
    return [
        'start' => date('Y-m-d', strtotime($weekRange['start'])),
        'end' => date('Y-m-d', strtotime($weekRange['end']))
    ];
}

/**
 * Cek apakah tanggal termasuk dalam minggu saat ini
 */
function isInCurrentWeek($dateString) {
    $weekRange = getWeekRange();
    
    if (strlen($dateString) === 8 && is_numeric($dateString)) {
        $dateFormatted = $dateString;
    } else {
        $dateFormatted = date('Ymd', strtotime($dateString));
    }
    
    return ($dateFormatted >= $weekRange['start'] && $dateFormatted <= $weekRange['end']);
}

/**
 * Cek apakah hari ini adalah Senin
 */
function isMonday() {
    return date('N') == 1; // 1 = Senin
}

/**
 * RESET SEMUA NOTIFIKASI MINGGU LALU (MARK AS READ)
 * Fungsi ini akan menjadikan semua notifikasi minggu lalu sebagai TERBACA
 * DIPANGGIL OTOMATIS SETIAP HARI SENIN
 */
function resetOldNotifications($conn) {
    $result = [
        'success' => false,
        'message' => '',
        'reset_count' => 0
    ];
    
    if (!$conn) {
        $result['message'] = 'No database connection';
        return $result;
    }
    
    try {
        // Dapatkan tanggal Senin minggu ini
        $weekInfo = getCurrentWeekInfo();
        $thisMonday = $weekInfo['start_date']; // Format YYYYMMDD
        
        // ==================== RESET NOTIFIKASI MINGGU LALU ====================
        // Cari semua ID_INFORMATION dari minggu LALU (DATE < Senin minggu ini)
        $sql_find = "SELECT ID_INFORMATION FROM T_INFORMATION 
                     WHERE DATE < ?";
        
        $stmt_find = sqlsrv_query($conn, $sql_find, [$thisMonday]);
        
        $oldInfoIds = [];
        if ($stmt_find) {
            while ($row = sqlsrv_fetch_array($stmt_find, SQLSRV_FETCH_ASSOC)) {
                $oldInfoIds[] = $row['ID_INFORMATION'];
            }
            sqlsrv_free_stmt($stmt_find);
        }
        
        // Kalau ada notif lama, reset semuanya
        if (!empty($oldInfoIds)) {
            // Buat placeholder untuk IN clause
            $placeholders = implode(',', array_fill(0, count($oldInfoIds), '?'));
            
            // Update semua notif yang BELUM DIBACA (read_at IS NULL) menjadi TERBACA
            $sql_reset = "UPDATE user_notification_read 
                          SET read_at = GETDATE() 
                          WHERE notification_id IN ($placeholders)
                          AND read_at IS NULL";
            
            $stmt_reset = sqlsrv_query($conn, $sql_reset, $oldInfoIds);
            
            if ($stmt_reset) {
                $rowsAffected = sqlsrv_rows_affected($stmt_reset);
                $result['reset_count'] = $rowsAffected;
                $result['success'] = true;
                $result['message'] = "Successfully reset $rowsAffected old notifications";
                sqlsrv_free_stmt($stmt_reset);
            } else {
                $result['message'] = 'Failed to reset notifications';
            }
        } else {
            $result['success'] = true;
            $result['message'] = 'No old notifications to reset';
        }
        
    } catch (Exception $e) {
        $result['message'] = 'Error: ' . $e->getMessage();
    }
    
    return $result;
}

/**
 * Get informasi minggu saat ini
 */
function getCurrentWeekInfo() {
    $weekRange = getWeekRange();
    
    return [
        'week_number' => date('W'),
        'start_date' => $weekRange['start'],
        'end_date' => $weekRange['end'],
        'start_formatted' => date('Y-m-d', strtotime($weekRange['start'])),
        'end_formatted' => date('Y-m-d', strtotime($weekRange['end'])),
        'display_text' => 'Minggu ' . date('W') . ' (' . 
                         date('d M', strtotime($weekRange['start'])) . ' - ' . 
                         date('d M Y', strtotime($weekRange['end'])) . ')'
    ];
}

// Session initialization untuk tracking mingguan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-reset flag setiap minggu
$currentWeek = date('W');
if (!isset($_SESSION['current_week']) || $_SESSION['current_week'] != $currentWeek) {
    $_SESSION['current_week'] = $currentWeek;
    $_SESSION['weekly_reset_done'] = false;
}
?>
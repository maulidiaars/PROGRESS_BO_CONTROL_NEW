
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>🚀 LIVE DASHBOARD - BO CONTROL MONITORING REAL-TIME</title>
    
    <!-- FAVICON -->
    <link href="assets/img/favicon.png" rel="icon">
    
    <!-- BOOTSTRAP 5 -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- BOOTSTRAP ICONS -->
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- APEXCHARTS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">
    
    <style>
        /* ========== GLOBAL STYLES ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            font-family: 'Roboto', 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
        }
        
        /* ========== HEADER ========== */
        .dashboard-header {
            background: rgba(15, 52, 96, 0.95);
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            border-bottom: 3px solid #00adb5;
            backdrop-filter: blur(10px);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-container img {
            height: 45px;
            width: auto;
        }

        .header-title {
            display: flex;
            flex-direction: column;
        }

        .main-title {
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .sub-title {
            font-size: 14px;
            color: #00adb5;
            font-weight: 600;
            margin-top: 2px;
        }

        .datetime-display {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            color: #ffffff;
        }

        .date-display {
            font-size: 16px;
            font-weight: 500;
            color: #a9b7c6;
        }

        .time-display {
            font-family: 'Inter', monospace;
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.8px;
            line-height: 1.2;
            margin-top: 2px;
        }

        .dashboard-container {
            display: flex;
            height: calc(100vh - 80px);
            margin-top: 80px;
            padding: 20px;
            gap: 20px;
        }
        
        .live-charts-container {
            width: 35%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .live-gauge-card, .hourly-card, .info-card {
            background: rgba(22, 33, 62, 0.9);
            border-radius: 12px;
            padding: 15px 20px 20px 20px;
            border: 1px solid rgba(0, 173, 181, 0.3);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }

        .gauge-container {
            text-align: center;
            padding-top: 20px;
        }

        #todayGauge {
            height: 180px !important;
            margin: 0 auto;
        }

        .gauge-header, .hourly-header, .info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 173, 181, 0.2);
        }
        
        .gauge-header h6, .hourly-header h6, .info-header h6 {
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .live-time {
            font-size: 12px;
            color: #00adb5;
            font-family: monospace;
        }
        
        .shift-badge {
            background: linear-gradient(135deg, #00adb5 0%, #00838f 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
    
        .gauge-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat {
            text-align: center;
        }
        
        .stat .label {
            display: block;
            font-size: 11px;
            color: #a9b7c6;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .stat .value {
            font-size: 18px;
            font-weight: 800;
            display: block;
            color: #ffffff;
        }
        
        .stat .value.text-success {
            color: #2ecc71;
        }
        
        .info-card {
            max-height: 300px;
            min-height: 250px;
            display: flex;
            flex-direction: column;
        }
        
        .info-list {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #00adb5 #0f3460;
        }
        
        .info-list::-webkit-scrollbar {
            width: 4px;
        }
        
        .info-list::-webkit-scrollbar-track {
            background: #0f3460;
            border-radius: 3px;
        }
        
        .info-list::-webkit-scrollbar-thumb {
            background: #00adb5;
            border-radius: 3px;
        }
        
        .info-item {
            background: rgba(0, 173, 181, 0.1);
            border-left: 4px solid #00adb5;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            cursor: default;
            animation: fadeIn 0.5s ease;
        }

        .info-item.urgent {
            border-left-color: #ff416c;
            background: rgba(255, 65, 108, 0.1);
        }

        .info-item.assigned {
            border-left-color: #ffa726;
            background: rgba(255, 167, 38, 0.1);
        }

        .info-content {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .info-icon {
            width: 24px;
            height: 24px;
            min-width: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background: rgba(0, 173, 181, 0.2);
            color: #00adb5;
            margin-top: 2px;
        }

        .info-item.urgent .info-icon {
            background: rgba(255, 65, 108, 0.2);
            color: #ff416c;
        }

        .info-item.assigned .info-icon {
            background: rgba(255, 167, 38, 0.2);
            color: #ffa726;
        }

        .info-details {
            flex: 1;
            min-width: 0;
        }

        .info-from-to {
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .info-message {
            color: #d1d9e6;
            font-size: 11px;
            line-height: 1.4;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .info-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #a9b7c6;
        }
        
        .main-data-panel {
            width: 65%;
            background: rgba(22, 33, 62, 0.9);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 173, 181, 0.3);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 173, 181, 0.2);
        }
        
        .panel-title {
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .panel-title i {
            color: #00adb5;
            font-size: 22px;
        }
        
        .live-badge {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 65, 108, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0); }
        }
        
        .live-dot {
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
            animation: blink 1s infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .panel-stats {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 12px;
            background: rgba(15, 52, 96, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(0, 173, 181, 0.3);
            min-width: 80px;
        }
        
        .stat-label {
            color: #a9b7c6;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
        }
        
        .stat-value {
            color: #ffffff;
            font-size: 16px;
            font-weight: 800;
            margin-top: 4px;
        }
        
        .table-wrapper {
            flex: 1;
            overflow: hidden;
            border-radius: 8px;
            position: relative;
        }
        
        .table-container {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .table-fixed-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(15, 52, 96, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #00adb5;
        }
        
        .table-fixed-header table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
        }
        
        .table-fixed-header th {
            padding: 14px 8px;
            text-align: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-right: 1px solid rgba(0, 173, 181, 0.2);
        }
        
        .table-fixed-header th:last-child {
            border-right: none;
        }
        
        .table-fixed-header th i {
            margin-right: 5px;
            color: #00adb5;
            font-size: 10px;
        }
        
        /* ===== KOLOM WIDTH PAKAI CLASS ===== */
        .col-code { width: 70px; }
        .col-supplier { width: 160px; }
        .col-pic { width: 60px; }
        .col-day { width: 110px; }
        .col-night { width: 110px; }
        .col-order { width: 85px; }
        .col-incoming { width: 85px; }
        .col-remain { width: 85px; }
        .col-rate { width: 80px; }
        .col-status { width: 80px; }
        
        .table-scroll-body {
            position: absolute;
            top: 48px;
            left: 0;
            right: 0;
            bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #00adb5 #0f3460;
        }
        
        .table-scroll-body::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        
        .table-scroll-body::-webkit-scrollbar-track {
            background: #0f3460;
            border-radius: 10px;
        }
        
        .table-scroll-body::-webkit-scrollbar-thumb {
            background: #00adb5;
            border-radius: 10px;
        }
        
        .scrolling-content {
            width: 100%;
            position: relative;
        }
        
        .scrolling-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
        }
        
        .scrolling-content td {
            padding: 12px 6px;
            text-align: left;
            border-bottom: 1px solid rgba(15, 52, 96, 0.3);
            border-right: 1px solid rgba(0, 173, 181, 0.15);
            color: #e4e6eb;
            font-size: 11px;
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        
        .scrolling-content td:last-child {
            border-right: none;
        }
        
        .scrolling-content td:nth-child(1) { width: 70px; }
        .scrolling-content td:nth-child(2) { width: 160px; }
        .scrolling-content td:nth-child(3) { width: 60px; }
        .scrolling-content td:nth-child(4) { width: 110px; }
        .scrolling-content td:nth-child(5) { width: 110px; }
        .scrolling-content td:nth-child(6) { width: 85px; }
        .scrolling-content td:nth-child(7) { width: 85px; }
        .scrolling-content td:nth-child(8) { width: 85px; }
        .scrolling-content td:nth-child(9) { width: 80px; }
        .scrolling-content td:nth-child(10) { width: 80px; }
        
        .scrolling-content tr {
            background: transparent;
            transition: all 0.2s ease;
        }
        
        .scrolling-content tr:hover {
            background: rgba(0, 173, 181, 0.08);
        }
        
        .scrolling-content tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .supplier-code {
            color: #ffffff;
            font-weight: 700;
            font-size: 11px;
            background: rgba(0, 173, 181, 0.1);
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid rgba(0, 173, 181, 0.3);
            display: inline-block;
            text-align: center;
            width: 100%;
        }
        
        .supplier-name {
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 500;
            color: #d1d9e6;
            font-size: 11px;
            padding-left: 5px;
        }
        
        .pic-badge {
            background: linear-gradient(135deg, #8e44ad 0%, #9b59b6 100%);
            color: white;
            padding: 4px 5px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            width: 100%;
        }
        
        /* ========== PROGRESS BAR SUPER TEBEL - ANGKA DI TENGAH BANGET ========== */
        .progress-cell {
            width: 110px;
            position: relative;
        }
        
        .progress-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 4px;
        }
        
        .progress-info {
            width: 100%;
            position: relative;
        }
        
.progress-bar-horizontal {
    width: 100%;
    height: 36px;
    background: rgba(15, 52, 96, 0.7);
    border-radius: 18px;
    position: relative; /* PENTING */
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 18px 0 0 18px; /* kiri bulet, kanan rata */
    transition: width 0.6s ease;
}

.progress-fill.full {
    border-radius: 18px; /* baru bulet semua */
}


.progress-fill.ds {
    background: linear-gradient(90deg, #00adb5, #007c9e);
}

.progress-fill.ns {
    background: linear-gradient(90deg, #ff6b6b, #c0392b);
}

.progress-text {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    z-index: 3;

    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.3px;

    /* ✨ BLEND KE BAR */
    background: rgba(0, 0, 0, 0.25);
    padding: 2px 10px;
    border-radius: 12px;

    /* ✨ BIAR KONTRAS TANPA STICKER */
    text-shadow: 0 1px 4px rgba(0,0,0,0.8);

    pointer-events: none;
}

.progress-bar-horizontal:has(.progress-fill[style*="width: 0"]) .progress-text {
    opacity: 0.75;
}

        
        /* Background khusus saat progress 0% atau kecil */
        .progress-bar-horizontal .progress-text {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #00adb5;
        }
        
        .progress-label, .progress-info small {
            display: none !important;
        }
        
        /* ========== QUANTITY CELLS - WARNA JELAS & TIDAK NABRAK ========== */
        .quantity-cell {
            width: 85px;
        }
        
        .quantity-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 5px 6px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            width: 100%;
        }
        
        .quantity-value {
            font-weight: 800;
            font-size: 12px;
            color: #ffffff;
            margin-bottom: 1px;
        }
        
        .quantity-label {
            font-size: 8px;
            color: #a9b7c6;
            text-transform: uppercase;
        }
        
        /* ORDER - BIRU (NETRAL) */
        .quantity-order {
            border-color: rgba(52, 152, 219, 0.4);
            background: rgba(52, 152, 219, 0.1);
        }
        .quantity-order .quantity-value {
            color: #3498db;
        }
        
        /* INCOMING - HIJAU (POSITIF) */
        .quantity-incoming {
            border-color: rgba(46, 204, 113, 0.4);
            background: rgba(46, 204, 113, 0.1);
        }
        .quantity-incoming .quantity-value {
            color: #2ecc71;
        }
        
        /* REMAIN - ORANGE (PERHATIAN) */
        .quantity-remain {
            border-color: rgba(241, 196, 15, 0.4);
            background: rgba(241, 196, 15, 0.1);
        }
        .quantity-remain .quantity-value {
            color: #f1c40f;
        }
        
        /* REMAIN NEGATIVE (BALANCE MINUS) - MERAH (KRITIS) */
        .quantity-remain-negative {
            border-color: rgba(231, 76, 60, 0.4);
            background: rgba(231, 76, 60, 0.1);
        }
        .quantity-remain-negative .quantity-value {
            color: #e74c3c;
        }
        
        /* ========== STATUS BADGE - WARNA JELAS & TIDAK NABRAK ========== */
        .status-badge {
            padding: 6px 6px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: 100%;
        }
        
        /* COMPLETE - HIJAU (SELESAI) */
        .status-complete {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        /* ON PROGRESS - BIRU (SEDANG BERJALAN) */
        .status-progress {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        /* DELAY - KUNING/ORANGE (BUTUH PERHATIAN) */
        .status-delay {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        /* OVER - MERAH (KRITIS) */
        .status-over {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        /* ========== RATE DISPLAY - WARNA SESUAI LEVEL ========== */
        .rate-display {
            font-weight: 700;
            font-size: 13px;
            color: #ffffff;
            text-align: center;
            display: block;
        }
        
        /* RATE ≥ 90% - HIJAU (EXCELLENT) */
        .rate-excellent {
            color: #2ecc71;
            text-shadow: 0 0 8px rgba(46, 204, 113, 0.6);
        }
        
        /* RATE 70-89% - BIRU (GOOD) */
        .rate-good {
            color: #3498db;
            text-shadow: 0 0 8px rgba(52, 152, 219, 0.6);
        }
        
        /* RATE 50-69% - KUNING (WARNING) */
        .rate-warning {
            color: #f1c40f;
            text-shadow: 0 0 8px rgba(241, 196, 15, 0.6);
        }
        
        /* RATE < 50% - MERAH (DANGER) */
        .rate-danger {
            color: #e74c3c;
            text-shadow: 0 0 8px rgba(231, 76, 60, 0.6);
        }
        
        .scroll-controls {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 200;
            display: flex;
            gap: 8px;
        }
        
        .scroll-btn {
            background: rgba(0, 173, 181, 0.8);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .scroll-btn:hover {
            background: rgba(0, 173, 181, 1);
            transform: translateY(-1px);
        }
        
        .control-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .control-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .control-btn-primary {
            background: linear-gradient(135deg, #00adb5 0%, #00838f 100%);
            color: white;
        }
        
        .control-btn-primary:hover {
            background: linear-gradient(135deg, #00838f 0%, #006064 100%);
            transform: translateY(-2px);
        }
        
        .control-btn-secondary {
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
            color: #a9b7c6;
            border: 1px solid #00adb5;
        }
        
        .control-btn-secondary:hover {
            background: linear-gradient(135deg, #1a1a2e 0%, #0f3460 100%);
            transform: translateY(-2px);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .home-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #00adb5 0%, #00838f 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(0, 173, 181, 0.4);
            z-index: 1001;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .home-button:hover {
            background: linear-gradient(135deg, #00838f 0%, #006064 100%);
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 173, 181, 0.5);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a9b7c6;
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.3;
        }
        
        .empty-state p {
            font-size: 14px;
            font-weight: 500;
        }

/* ========== APEXCHARTS AREA FILL GRADIENT ENHANCEMENT ========== */
.apexcharts-area-series .apexcharts-series path {
    transition: all 0.25s ease;
}

.apexcharts-area-series .apexcharts-series:hover {
    opacity: 0.95;
}

/* Gradient fill akan terlihat lebih smooth */
.apexcharts-area-series .apexcharts-series[rel="1"] path {
    filter: drop-shadow(0 4px 6px rgba(0, 173, 181, 0.15));
}

/* Tooltip modern */
.apexcharts-tooltip {
    background: linear-gradient(145deg, rgba(15, 52, 96, 0.98), rgba(10, 35, 65, 0.98)) !important;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0, 173, 181, 0.6) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(0, 173, 181, 0.2) inset !important;
    padding: 8px 12px !important;
}

.apexcharts-tooltip-title {
    background: rgba(0, 173, 181, 0.2) !important;
    border-bottom: 1px solid rgba(0, 173, 181, 0.4) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    padding: 8px 14px !important;
    margin: -8px -12px 8px -12px !important;
    border-radius: 12px 12px 0 0 !important;
    letter-spacing: 0.3px;
}

/* Legend dengan efek glassmorphism */
.apexcharts-legend-series {
    background: rgba(0, 173, 181, 0.08) !important;
    padding: 5px 20px !important;
    border-radius: 40px !important;
    margin: 0 8px !important;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0, 173, 181, 0.2);
    transition: all 0.2s ease;
}

.apexcharts-legend-series:hover {
    background: rgba(0, 173, 181, 0.2) !important;
    border-color: rgba(0, 173, 181, 0.6);
    transform: translateY(-1px);
}

.apexcharts-legend-text {
    color: #ffffff !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    padding-left: 10px !important;
    letter-spacing: 0.2px;
}

/* Marker bulat dengan glow */
.apexcharts-legend-marker {
    width: 14px !important;
    height: 14px !important;
    border-radius: 7px !important;
    margin-right: 6px !important;
    box-shadow: 0 0 10px currentColor;
}

/* Grid lebih soft */
.apexcharts-gridline {
    stroke: rgba(255, 255, 255, 0.05);
}

/* X-axis & Y-axis */
.apexcharts-xaxis-label, 
.apexcharts-yaxis-label {
    fill: #b0bec5;
    font-weight: 500;
}
        
        @media (max-width: 1200px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .live-charts-container,
            .main-data-panel {
                width: 100%;
            }
            
            .main-data-panel {
                height: 60vh;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 0 15px;
                height: auto;
                min-height: 70px;
                flex-wrap: wrap;
            }
            
            .main-title {
                font-size: 18px;
            }
            
            .time-display {
                font-size: 22px;
            }
            
            .dashboard-container {
                margin-top: 120px;
                padding: 10px;
                gap: 10px;
            }
            
            .panel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .panel-stats {
                width: 100%;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .stat-item {
                flex: 1;
                min-width: calc(50% - 5px);
            }
        }
    </style>
</head>
<body>
    
    <!-- HEADER -->
    <header class="dashboard-header">
        <div class="logo-container">
            <img src="assets/img/logo-denso.png" alt="DENSO Logo" onerror="this.style.display='none'">
            <div class="header-title">
                <div class="main-title">🚀 LIVE DASHBOARD - BO CONTROL MONITORING</div>
                <div class="sub-title">Real-time Tracking • Operator View • Instant Updates</div>
            </div>
        </div>
        
        <div class="datetime-display">
            <div class="date-display" id="dateDisplay">Loading date...</div>
            <div class="time-display" id="timeDisplay">00:00:00</div>
        </div>
    </header>
        
    <!-- MAIN DASHBOARD -->
    <div class="dashboard-container">
        
        <!-- LEFT PANEL -->
        <div class="live-charts-container">
            
            <!-- TODAY'S TARGET GAUGE -->
            <div class="live-gauge-card">
                <div class="gauge-header">
                    <h6><i class="fas fa-tachometer-alt"></i> TODAY'S ACHIEVEMENT</h6>
                    <span class="live-time" id="lastUpdateTime">
                        <span class="loading-spinner"></span> Updating...
                    </span>
                </div>
                <div class="gauge-container">
                    <div id="todayGauge" style="height: 180px; margin-top: -10px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-3 text-white small">Loading achievement data...</p>
                        </div>
                    </div>
                    <div class="gauge-stats" style="margin-top: -60px;">
                        <div class="stat">
                            <span class="label">Target</span>
                            <span class="value" id="targetQty">0 pcs</span>
                        </div>
                        <div class="stat">
                            <span class="label">Incoming</span>
                            <span class="value text-success" id="incomingQty">0 pcs</span>
                        </div>
                        <div class="stat">
                            <span class="label">Balance</span>
                            <span class="value" id="balanceQty">0 pcs</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- HOURLY PROGRESS -->
            <div class="hourly-card">
                <div class="hourly-header">
                    <h6><i class="fas fa-clock"></i> HOURLY INCOMING PROGRESS (pcs)</h6>
                    <div class="shift-badge" id="currentShift">D/S: 07:00-20:00</div>
                </div>
                <div id="hourlyChart" style="height: 200px;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-3 text-white small">Loading hourly data...</p>
                    </div>
                </div>
            </div>
            
            <!-- LIVE INFORMATION -->
            <div class="info-card">
                <div class="info-header">
                    <h6><i class="fas fa-bullhorn"></i> LIVE INFORMATION</h6>
                    <span class="badge bg-warning rounded-pill px-2" id="infoCount">0</span>
                </div>
                
                <div class="info-list" id="informationList">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <p class="mt-2 text-white small">Loading live information...</p>
                        <small class="text-muted">Fetching from last 7 days</small>
                    </div>
                </div>
            </div>
            
            <!-- CONTROL BUTTONS -->
            <div class="control-buttons">
                <button class="control-btn control-btn-primary" onclick="refreshAllData()" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i> <span id="refreshText">Refresh Now</span>
                </button>
                <button class="control-btn control-btn-primary" onclick="toggleAutoRefresh()" id="autoRefreshBtn">
                    <i class="fas fa-power-off"></i> <span id="autoRefreshText">Auto: ON</span>
                </button>
            </div>
        </div>
        
        <!-- RIGHT PANEL - LIVE SUPPLIER TABLE -->
        <div class="main-data-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <i class="fas fa-truck-loading"></i> LIVE SUPPLIER DELIVERY STATUS
                    <div class="live-badge">
                        <div class="live-dot"></div>
                        LIVE • <span id="todayDate">Today</span>
                    </div>
                </div>
                
                <div class="panel-stats">
                    <div class="stat-item">
                        <span class="stat-label">TOTAL</span>
                        <span class="stat-value" id="totalSuppliers">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">COMPLETED</span>
                        <span class="stat-value text-success" id="completedCount">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">ON PROGRESS</span>
                        <span class="stat-value text-primary" id="onProgressCount">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">DELAYED</span>
                        <span class="stat-value text-warning" id="delayedCount">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">OVER</span>
                        <span class="stat-value text-danger" id="overCount">0</span>
                    </div>
                </div>
            </div>
            
            <!-- TABLE CONTAINER -->
            <div class="table-wrapper">
                <div class="table-container">
                    <div class="table-fixed-header">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-code"><i class="fas fa-barcode"></i> CODE</th>
                                    <th class="col-supplier"><i class="fas fa-warehouse"></i> SUPPLIER NAME</th>
                                    <th class="col-pic"><i class="fas fa-user"></i> PIC</th>
                                    <th class="col-day"><i class="fas fa-sun"></i> DAY SHIFT</th>
                                    <th class="col-night"><i class="fas fa-moon"></i> NIGHT SHIFT</th>
                                    <th class="col-order"><i class="fas fa-truck"></i> ORDER</th>
                                    <th class="col-incoming"><i class="fas fa-box"></i> INCOMING</th>
                                    <th class="col-remain"><i class="fas fa-balance-scale"></i> REMAIN</th>
                                    <th class="col-rate"><i class="fas fa-chart-line"></i> RATE</th>
                                    <th class="col-status"><i class="fas fa-flag"></i> STATUS</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="table-scroll-body" id="tableScrollBody">
                        <div class="scrolling-content" id="scrollingContent">
                            <div class="empty-state">
                                <i class="fas fa-database"></i>
                                <p>Loading supplier data...</p>
                                <small>Please wait while data is being loaded</small>
                            </div>
                        </div>
                    </div>
                    <div class="scroll-controls">
                        <button class="scroll-btn" onclick="toggleAutoScroll()" id="autoScrollBtn">
                            <i class="fas fa-pause"></i> Pause Scroll
                        </button>
                        <button class="scroll-btn" onclick="scrollFaster()">
                            <i class="fas fa-forward"></i> Faster
                        </button>
                        <button class="scroll-btn" onclick="scrollSlower()">
                            <i class="fas fa-backward"></i> Slower
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- HOME BUTTON -->
    <a href="index.php" class="home-button" title="Back to Main Dashboard">
        <i class="fas fa-home"></i>
    </a>
    
    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>

    <script>
    // ========== GLOBAL VARIABLES ==========
    var todayGauge = null;
    var hourlyChart = null;
    var autoRefreshInterval = null;
    var isAutoRefresh = true;
    var today = new Date().toISOString().split('T')[0].replace(/-/g, '');
    
    // ========== SCROLLING VARIABLES ==========
    var autoScrollInterval = null;
    var isAutoScrolling = true;
    var scrollSpeed = 0.8;
    var scrollPosition = 0;
    var lastKnownScrollPosition = 0;
    var isRefreshing = false;
    
    // ========== INFORMATION SCROLL VARIABLES ==========
    var infoScrollInterval = null;
    var isInfoScrolling = true;
    var infoScrollPosition = 0;
    var infoScrollSpeed = 0.5;
    
    // ========== DATE TIME FUNCTIONS ==========
    function updateDateTime() {
        var now = new Date();
        var dateStr = now.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        var timeStr = now.toLocaleTimeString('en-US', { 
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        document.getElementById('dateDisplay').innerText = dateStr;
        document.getElementById('timeDisplay').innerText = timeStr;
        document.getElementById('todayDate').innerText = now.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        
        var hour = now.getHours();
        var shiftBadge = document.getElementById('currentShift');
        if (hour >= 7 && hour <= 20) {
            shiftBadge.innerText = 'D/S: 07:00-20:00';
            shiftBadge.style.background = 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)';
        } else {
            shiftBadge.innerText = 'N/S: 21:00-06:00';
            shiftBadge.style.background = 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)';
        }
    }
    
    // ========== SCROLL POSITION MEMORY ==========
    function saveScrollPosition() {
        var scrollBody = document.getElementById('tableScrollBody');
        if (scrollBody) {
            lastKnownScrollPosition = scrollBody.scrollTop;
            scrollPosition = scrollBody.scrollTop;
        }
    }
    
    function restoreScrollPosition() {
        var scrollBody = document.getElementById('tableScrollBody');
        if (scrollBody && lastKnownScrollPosition > 0) {
            scrollBody.scrollTop = lastKnownScrollPosition;
            scrollPosition = lastKnownScrollPosition;
        }
    }
    
    // ========== AUTO SCROLL ==========
    function startAutoScroll() {
        if (autoScrollInterval) clearInterval(autoScrollInterval);
        
        var scrollBody = document.getElementById('tableScrollBody');
        var content = document.getElementById('scrollingContent');
        
        if (!scrollBody || !content) return;
        
        scrollPosition = scrollBody.scrollTop;
        
        autoScrollInterval = setInterval(function() {
            if (!isAutoScrolling) return;
            
            var containerHeight = scrollBody.clientHeight;
            var contentHeight = content.scrollHeight;
            var maxScroll = Math.max(0, contentHeight - containerHeight);
            
            scrollPosition += scrollSpeed;
            
            if (scrollPosition >= maxScroll) {
                scrollPosition = 0;
            }
            
            scrollBody.scrollTop = scrollPosition;
            
        }, 16);
    }
    
    function stopAutoScroll() {
        if (autoScrollInterval) {
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;
        }
        saveScrollPosition();
    }
    
    function toggleAutoScroll() {
        isAutoScrolling = !isAutoScrolling;
        var btn = document.getElementById('autoScrollBtn');
        
        if (isAutoScrolling) {
            startAutoScroll();
            btn.innerHTML = '<i class="fas fa-pause"></i> Pause Scroll';
            btn.classList.remove('active');
        } else {
            stopAutoScroll();
            btn.innerHTML = '<i class="fas fa-play"></i> Play Scroll';
            btn.classList.add('active');
        }
    }
    
    function scrollFaster() {
        scrollSpeed = Math.min(scrollSpeed + 0.2, 3);
    }
    
    function scrollSlower() {
        scrollSpeed = Math.max(scrollSpeed - 0.2, 0.5);
    }
    
    // ========== INFORMATION SECTION ==========
    function updateInformation() {
        $.ajax({
            url: 'api/get_live_information.php',
            type: 'GET',
            dataType: 'json',
            timeout: 8000,
            success: function(response) {
                if (!response || !response.success || !response.informations || response.informations.length === 0) {
                    showNoInformation();
                    return;
                }
                
                var informations = response.informations;
                var html = '';
                
                for (var i = 0; i < informations.length; i++) {
                    var info = informations[i];
                    var statusClass = '';
                    var statusBadge = '';
                    var icon = 'info-circle';
                    
                    if (info.STATUS === 'Open') {
                        statusClass = 'urgent';
                        statusBadge = 'bg-danger';
                        icon = 'exclamation-triangle';
                    } else if (info.STATUS === 'On Progress') {
                        statusClass = 'assigned';
                        statusBadge = 'bg-warning';
                        icon = 'clock';
                    }
                    
                    var fromToText = info.PIC_FROM + ' → ' + info.PIC_TO;
                    var messageText = info.REQUEST || info.ITEM || 'Tidak ada isi';
                    var timeFormatted = info.time_formatted || '';
                    var dateFormatted = info.date_formatted || '';
                    
                    html += '<div class="info-item ' + statusClass + '">';
                    html += '    <div class="info-content">';
                    html += '        <div class="info-icon">';
                    html += '            <i class="fas fa-' + icon + '"></i>';
                    html += '        </div>';
                    html += '        <div class="info-details">';
                    html += '            <div class="info-from-to">';
                    html += '                <strong>' + escapeHtml(fromToText) + '</strong>';
                    html += '            </div>';
                    html += '            <div class="info-status-badge">';
                    html += '                <span class="badge ' + statusBadge + '">' + (info.status_text || 'OPEN') + '</span>';
                    html += '            </div>';
                    html += '            <div class="info-message">';
                    html +=                 escapeHtml(messageText);
                    html += '            </div>';
                    html += '            <div class="info-meta">';
                    html += '                <div class="info-time">';
                    html += '                    <i class="far fa-clock"></i> ' + escapeHtml(timeFormatted);
                    html += '                </div>';
                    html += '                <div class="info-date">';
                    html +=                     escapeHtml(dateFormatted);
                    html += '                </div>';
                    html += '            </div>';
                    html += '        </div>';
                    html += '    </div>';
                    html += '</div>';
                }
                
                document.getElementById('informationList').innerHTML = html;
                document.getElementById('infoCount').innerText = informations.length;
                
                startInfoAutoScroll();
            },
            error: function() {
                document.getElementById('informationList').innerHTML = 
                    '<div class="text-center py-4 text-warning">' +
                    '    <i class="fas fa-exclamation-triangle"></i>' +
                    '    <p class="mt-2 text-white small">Gagal load informasi</p>' +
                    '    <button onclick="updateInformation()" class="btn btn-sm btn-outline-warning mt-2">' +
                    '        <i class="fas fa-redo"></i> Retry' +
                    '    </button>' +
                    '</div>';
            }
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function showNoInformation() {
        document.getElementById('informationList').innerHTML = 
            '<div class="text-center py-5">' +
            '    <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>' +
            '    <p class="mt-3 text-white">Tidak ada informasi Open/On Progress</p>' +
            '    <small class="text-muted">Semua informasi sudah selesai</small>' +
            '</div>';
        document.getElementById('infoCount').innerText = '0';
    }
    
    function startInfoAutoScroll() {
        var container = document.getElementById('informationList');
        if (!container) return;
        
        if (infoScrollInterval) clearInterval(infoScrollInterval);
        
        infoScrollPosition = container.scrollTop || 0;
        
        infoScrollInterval = setInterval(function() {
            if (!isInfoScrolling) return;
            
            var containerHeight = container.clientHeight;
            var contentHeight = container.scrollHeight;
            
            infoScrollPosition += infoScrollSpeed;
            
            if (infoScrollPosition > contentHeight - containerHeight) {
                infoScrollPosition = 0;
            }
            
            container.scrollTop = infoScrollPosition;
        }, 50);
    }
    
    // ========== TODAY'S GAUGE ==========
    function updateTodayGauge() {
        $.ajax({
            url: 'api/get_today_performance.php',
            type: 'GET',
            data: { date: today },
            dataType: 'json',
            timeout: 5000,
            beforeSend: function() {
                document.getElementById('lastUpdateTime').innerHTML = '<span class="loading-spinner"></span> Updating...';
            },
            success: function(response) {
                var totalOrder = parseInt(response.total_order) || 0;
                var totalIncoming = parseInt(response.total_incoming) || 0;
                var achievement = totalOrder > 0 ? Math.min(Math.round((totalIncoming / totalOrder) * 100), 100) : 0;
                var balance = totalOrder - totalIncoming;
                
                var gaugeColor = achievement >= 90 ? '#2ecc71' : 
                                achievement >= 70 ? '#f1c40f' : 
                                achievement >= 50 ? '#e67e22' : '#e74c3c';
                
                var gradientToColor = achievement >= 90 ? '#27ae60' : 
                                     achievement >= 70 ? '#f39c12' : 
                                     achievement >= 50 ? '#d35400' : '#c0392b';
                
                if (!todayGauge) {
                    var options = {
                        series: [achievement],
                        chart: { 
                            type: 'radialBar', 
                            height: 180,
                            animations: { enabled: true, speed: 800 }
                        },
                        plotOptions: {
                            radialBar: {
                                startAngle: -90,
                                endAngle: 90,
                                hollow: { size: '65%' },
                                track: { background: 'rgba(255, 255, 255, 0.1)' },
                                dataLabels: {
                                    name: { show: false },
                                    value: { 
                                        fontSize: '32px',
                                        fontWeight: 'bold',
                                        offsetY: 5,
                                        formatter: function(val) { 
                                            return Math.min(val, 100) + '%'; 
                                        },
                                        color: '#ffffff'
                                    }
                                }
                            }
                        },
                        colors: [gaugeColor],
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'dark',
                                type: 'horizontal',
                                gradientToColors: [gradientToColor],
                                stops: [0, 100]
                            }
                        }
                    };
                    
                    todayGauge = new ApexCharts(document.querySelector("#todayGauge"), options);
                    todayGauge.render();
                } else {
                    todayGauge.updateSeries([achievement]);
                    todayGauge.updateOptions({
                        colors: [gaugeColor],
                        fill: {
                            gradient: {
                                gradientToColors: [gradientToColor]
                            }
                        }
                    });
                }
                
                document.getElementById('targetQty').innerHTML = totalOrder.toLocaleString() + ' pcs';
                document.getElementById('incomingQty').innerHTML = totalIncoming.toLocaleString() + ' pcs';
                document.getElementById('balanceQty').innerHTML = balance.toLocaleString() + ' pcs';
                
                var now = new Date();
                var timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                document.getElementById('lastUpdateTime').innerHTML = 'Last: ' + timeStr;
            },
            error: function() {
                document.getElementById('lastUpdateTime').innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Failed';
            }
        });
    }
    
// ========== HOURLY CHART - INCOMING VS TARGET DENGAN AREA FILL GRADIENT ==========
function updateHourlyChart() {
    var currentHour = new Date().getHours();
    var isDayShift = currentHour >= 7 && currentHour <= 20;
    
    $.ajax({
        url: 'api/get_hourly_progress.php',
        type: 'GET',
        data: { 
            date: today,
            shift: isDayShift ? 'DS' : 'NS'
        },
        dataType: 'json',
        timeout: 5000,
        success: function(response) {
            if (!response || !Array.isArray(response) || response.length === 0) {
                document.getElementById('hourlyChart').innerHTML = 
                    '<div class="text-center py-5">' +
                    '<i class="fas fa-clock text-muted" style="font-size: 2rem;"></i>' +
                    '<p class="mt-2 text-white small">No hourly data available</p>' +
                    '</div>';
                return;
            }
            
            var hours = [];
            var incomingCumulative = [];
            var targetCumulative = [];
            
            for (var i = 0; i < response.length; i++) {
                hours.push(response[i].hour + ':00');
                incomingCumulative.push(response[i].cumulative_incoming || 0);
                targetCumulative.push(response[i].cumulative_target || 0);
            }
            
            var options = {
                series: [
                    {
                        name: '📦 Incoming (Cumulative)',
                        data: incomingCumulative,
                        type: 'area'
                    },
                    {
                        name: '🎯 Target (Cumulative)',
                        data: targetCumulative,
                        type: 'area'
                    }
                ],
                chart: {
                    height: 220,
                    type: 'area',
                    stacked: false,
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 800, dynamicAnimation: { enabled: true, speed: 350 } },
                    zoom: { enabled: false },
                    background: 'transparent',
                    foreColor: '#a9b7c6',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 0,
                        blur: 8,
                        color: ['#00adb5', '#ffa726'],
                        opacity: 0.3
                    }
                },
                stroke: {
                    width: [3.2, 2.8],
                    curve: 'smooth',
                    dashArray: [0, 6],
                    lineCap: 'round',
                    colors: ['#00e0ff', '#ffb74d']
                },
                fill: {
                    type: ['gradient', 'gradient'],
                    gradient: {
                        shade: 'dark',
                        type: 'vertical',
                        shadeIntensity: 0.8,
                        gradientToColors: ['#006064', '#f57c00'],
                        inverseColors: false,
                        opacityFrom: [0.75, 0.45],
                        opacityTo: [0.15, 0.08],
                        stops: [0, 70, 100]
                    }
                },
                colors: ['#00e0ff', '#ffb74d'],
                dataLabels: { enabled: false },
                grid: {
                    borderColor: 'rgba(255, 255, 255, 0.12)',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } },
                    padding: { left: 10, right: 10, top: 10, bottom: 10 }
                },
                markers: {
                    size: 5,
                    hover: { size: 8, sizeOffset: 2 },
                    colors: ['#00e0ff', '#ffb74d'],
                    strokeColors: '#ffffff',
                    strokeWidth: 2,
                    strokeOpacity: 0.9,
                    radius: 4,
                    offsetX: 0,
                    offsetY: 0,
                    shape: 'circle'
                },
                xaxis: {
                    categories: hours,
                    labels: { 
                        style: { colors: '#b0bec5', fontSize: '11px', fontWeight: 500, fontFamily: 'Inter, sans-serif' },
                        rotate: -45,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        trim: true,
                        maxHeight: 80
                    },
                    axisBorder: { show: false, color: 'rgba(255,255,255,0.1)' },
                    axisTicks: { show: false },
                    crosshairs: { 
                        show: true,
                        width: 1.5,
                        position: 'back',
                        stroke: { color: 'rgba(0, 173, 181, 0.5)', width: 1.5, dashArray: 4 }
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: { 
                        style: { colors: '#b0bec5', fontSize: '11px', fontWeight: 500, fontFamily: 'Inter, sans-serif' },
                        formatter: function(val) { 
                            return val >= 1000 ? (val/1000).toFixed(1) + 'k' : val; 
                        },
                        offsetX: -5
                    },
                    title: { 
                        text: 'Quantity (pcs)', 
                        style: { color: '#b0bec5', fontSize: '11px', fontWeight: 600, fontFamily: 'Inter, sans-serif' } 
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 6
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: 'dark',
                    x: { 
                        show: true,
                        format: 'HH:mm',
                        formatter: function(val, opts) {
                            return 'Jam: ' + hours[opts.dataPointIndex];
                        }
                    },
                    y: {
                        formatter: function(val, { seriesIndex, dataPointIndex }) {
                            if (seriesIndex === 0) {
                                var current = incomingCumulative[dataPointIndex];
                                var previous = dataPointIndex > 0 ? incomingCumulative[dataPointIndex-1] : 0;
                                var increment = current - previous;
                                return current.toLocaleString() + ' pcs (+' + increment.toLocaleString() + ')';
                            }
                            return val.toLocaleString() + ' pcs';
                        }
                    },
                    marker: { show: true },
                    style: { fontSize: '12px', fontFamily: 'Inter, sans-serif' },
                    background: 'rgba(15, 52, 96, 0.98)',
                    borderColor: '#00adb5',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    shadow: {
                        enabled: true,
                        top: 2,
                        left: 2,
                        blur: 8,
                        color: '#000',
                        opacity: 0.35
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    labels: { colors: '#ffffff' },
                    fontSize: '12px',
                    fontWeight: 600,
                    fontFamily: 'Inter, sans-serif',
                    markers: { 
                        width: 14, 
                        height: 14, 
                        strokeWidth: 0,
                        radius: 7,
                        offsetX: 0,
                        offsetY: 0
                    },
                    itemMargin: { horizontal: 16, vertical: 6 },
                    onItemClick: { toggleDataSeries: true },
                    onItemHover: { highlightDataSeries: true }
                }
            };
            
            if (!hourlyChart) {
                hourlyChart = new ApexCharts(document.querySelector("#hourlyChart"), options);
                hourlyChart.render();
            } else {
                hourlyChart.updateOptions(options);
                hourlyChart.updateSeries(options.series);
            }
        },
        error: function() {
            document.getElementById('hourlyChart').innerHTML = 
                '<div class="text-center py-5 text-warning">' +
                '<i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; opacity: 0.8;"></i>' +
                '<p class="mt-3 text-white fw-semibold">Failed to load hourly data</p>' +
                '<button onclick="updateHourlyChart()" class="btn btn-sm px-4 py-2 mt-2" ' +
                'style="background: rgba(0,173,181,0.2); border: 1px solid #00adb5; color: white; border-radius: 30px;">' +
                '<i class="fas fa-redo me-1"></i> Retry</button>' +
                '</div>';
        }
    });
}
    
// ========== RENDER SUPPLIER TABLE - SUPER CLEAN, TANPA TAMENG ==========
function renderSupplierTable(data) {
    if (!data || !Array.isArray(data) || data.length === 0) {
        showNoDataState();
        return;
    }
    
    // Sort by status priority (DELAY paling atas)
    data.sort(function(a, b) {
        var statusOrder = { 'DELAY': 1, 'OVER': 2, 'ON_PROGRESS': 3, 'OK': 4 };
        var statusA = statusOrder[a.STATUS] || 99;
        var statusB = statusOrder[b.STATUS] || 99;
        return statusA - statusB;
    });
    
    // ========== HEADER TABLE ==========
    var html = '';
    html += '<table>';
    html += '    <thead>';
    html += '        <tr>';
    html += '            <th class="col-code">CODE</th>';
    html += '            <th class="col-supplier">SUPPLIER NAME</th>';
    html += '            <th class="col-pic">PIC</th>';
    html += '            <th class="col-day">DAY SHIFT</th>';
    html += '            <th class="col-night">NIGHT SHIFT</th>';
    html += '            <th class="col-order">ORDER</th>';
    html += '            <th class="col-incoming">INCOMING</th>';
    html += '            <th class="col-remain">REMAIN</th>';
    html += '            <th class="col-rate">RATE</th>';
    html += '            <th class="col-status">STATUS</th>';
    html += '        </tr>';
    html += '    </thead>';
    html += '    <tbody>';
    
    var totalSuppliers = data.length;
    var completedCount = 0;
    var overCount = 0;
    var delayedCount = 0;
    var onProgressCount = 0;
    
    for (var i = 0; i < data.length; i++) {
        var item = data[i];
        
        var totalOrder = item.total_order || 0;
        var addDS = item.add_ds || 0;
        var addNS = item.add_ns || 0;
        var totalIncoming = item.total_incoming || 0;
        var dsIncoming = item.ds_incoming || 0;
        var nsIncoming = item.ns_incoming || 0;
        var dsCompletion = Math.min(item.ds_completion || 0, 100);
        var nsCompletion = Math.min(item.ns_completion || 0, 100);
        var completionRate = parseFloat(item.completion_rate) || 0;
        var balance = item.balance || 0;
        var status = item.STATUS || 'ON_PROGRESS';
        
        // Count stats
        if (status === 'OK') completedCount++;
        else if (status === 'OVER') overCount++;
        else if (status === 'DELAY') delayedCount++;
        else onProgressCount++;
        
        // ========== STATUS BADGE - PURE STATUS DOANG ==========
        var statusClass = 'status-progress';
        var statusText = status;
        
        if (status === 'OK') {
            statusClass = 'status-complete';
            statusText = 'COMPLETED';
        } else if (status === 'OVER') {
            statusClass = 'status-over';
            statusText = 'OVER';
        } else if (status === 'DELAY') {
            statusClass = 'status-delay';
            statusText = 'DELAY';
        } else if (status === 'ON_PROGRESS') {
            statusClass = 'status-progress';
            statusText = 'ON PROGRESS';
        }
        
        // Rate class
        var rateClass = 'rate-danger';
        if (completionRate >= 90) rateClass = 'rate-excellent';
        else if (completionRate >= 70) rateClass = 'rate-good';
        else if (completionRate >= 50) rateClass = 'rate-warning';
        
        // Quantity classes
        var orderClass = 'quantity-order';
        var incomingClass = 'quantity-incoming';
        var remainClass = balance > 0 ? 'quantity-remain' : 'quantity-remain-negative';
        
        var addOrderBadge = '';
        if (addDS > 0 || addNS > 0) {
            var totalAdd = addDS + addNS;
            addOrderBadge = '<br><small style="color: #ffa726; font-size: 8px;">+' + totalAdd.toLocaleString() + ' add</small>';
        }
        
        // ========== RENDER BARIS ==========
        html += '<tr>';
        html += '    <td><span class="supplier-code">' + (item.supplier_code || 'N/A') + '</span></td>';
        html += '    <td><div class="supplier-name">' + (item.supplier_name || 'Unknown') + '</div></td>';
        html += '    <td><span class="pic-badge">' + (item.pic_order || '-') + '</span></td>';
        
        // DAY SHIFT PROGRESS BAR
        html += '    <td class="progress-cell">';
        html += '        <div class="progress-container">';
        html += '            <div class="progress-info">';
        html += '                <div class="progress-bar-horizontal">';
        
        var dsWidth = dsCompletion;
        if (dsCompletion > 0 && dsCompletion < 8) dsWidth = 8;
        
        html += '                    <div class="progress-fill ds" style="width: ' + dsWidth + '%">';
        html += '                        <span class="progress-text">' + dsIncoming.toLocaleString() + ' pcs</span>';
        html += '                    </div>';
        html += '                </div>';
        html += '            </div>';
        html += '        </div>';
        html += '    </td>';
        
        // NIGHT SHIFT PROGRESS BAR
        html += '    <td class="progress-cell">';
        html += '        <div class="progress-container">';
        html += '            <div class="progress-info">';
        html += '                <div class="progress-bar-horizontal">';
        
        var nsWidth = nsCompletion;
        if (nsCompletion > 0 && nsCompletion < 8) nsWidth = 8;
        
        html += '                    <div class="progress-fill ns" style="width: ' + nsWidth + '%">';
        html += '                        <span class="progress-text">' + nsIncoming.toLocaleString() + ' pcs</span>';
        html += '                    </div>';
        html += '                </div>';
        html += '            </div>';
        html += '        </div>';
        html += '    </td>';
        
        // ORDER
        html += '    <td class="quantity-cell">';
        html += '        <div class="quantity-display ' + orderClass + '">';
        html += '            <div class="quantity-value">' + totalOrder.toLocaleString() + '</div>';
        html += '            <div class="quantity-label">Order' + addOrderBadge + '</div>';
        html += '        </div>';
        html += '    </td>';
        
        // INCOMING
        html += '    <td class="quantity-cell">';
        html += '        <div class="quantity-display ' + incomingClass + '">';
        html += '            <div class="quantity-value">' + totalIncoming.toLocaleString() + '</div>';
        html += '            <div class="quantity-label">Incoming</div>';
        html += '        </div>';
        html += '    </td>';
        
        // REMAIN
        html += '    <td class="quantity-cell">';
        html += '        <div class="quantity-display ' + remainClass + '">';
        html += '            <div class="quantity-value">' + balance.toLocaleString() + '</div>';
        html += '            <div class="quantity-label">Remain</div>';
        html += '        </div>';
        html += '    </td>';
        
        // RATE
        html += '    <td style="text-align: center;"><span class="rate-display ' + rateClass + '">' + completionRate.toFixed(0) + '%</span></td>';
        
        // ========== STATUS - PURE DOANG, TANPA TAMENG, TANPA ICON ==========
        html += '    <td style="text-align: center;">';
        html += '        <span class="status-badge ' + statusClass + '">' + statusText + '</span>';
        html += '    </td>';
        html += '</tr>';
    }
    
    html += '    </tbody>';
    html += '</table>';
    
    document.getElementById('scrollingContent').innerHTML = html;
    
    // Update stats
    document.getElementById('totalSuppliers').innerText = totalSuppliers;
    document.getElementById('completedCount').innerText = completedCount;
    document.getElementById('onProgressCount').innerText = onProgressCount;
    document.getElementById('delayedCount').innerText = delayedCount;
    document.getElementById('overCount').innerText = overCount;
}
    
    // ========== FETCH LIVE TABLE DATA ==========
    function updateLiveTable() {
        $.ajax({
            url: 'api/get_live_supplier_data.php',
            type: 'GET',
            data: { 
                date: today, 
                _t: new Date().getTime() 
            },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                var data = null;
                if (response && response.success && response.data) {
                    data = response.data;
                } else if (Array.isArray(response)) {
                    data = response;
                }
                
                if (data && data.length > 0) {
                    renderSupplierTable(data);
                } else {
                    showNoDataState();
                }
                
                setTimeout(function() {
                    restoreScrollPosition();
                    if (isAutoScrolling) startAutoScroll();
                }, 100);
            },
            error: function() {
                showNoDataState();
            }
        });
    }
    
    // ========== SILENT UPDATE ==========
    function updateLiveTableSilent() {
        saveScrollPosition();
        
        $.ajax({
            url: 'api/get_live_supplier_data.php',
            type: 'GET',
            data: { 
                date: today, 
                _t: new Date().getTime()
            },
            dataType: 'json',
            timeout: 10000,
            beforeSend: function() {
                document.getElementById('refreshBtn').disabled = true;
                document.getElementById('refreshBtn').innerHTML = '<span class="loading-spinner"></span>';
            },
            success: function(response) {
                var data = null;
                if (response && response.success && response.data) {
                    data = response.data;
                } else if (Array.isArray(response)) {
                    data = response;
                }
                
                if (data && data.length > 0) {
                    renderSupplierTable(data);
                    restoreScrollPosition();
                }
                
                document.getElementById('refreshBtn').disabled = false;
                document.getElementById('refreshBtn').innerHTML = '<i class="fas fa-sync-alt"></i>';
            },
            error: function() {
                document.getElementById('refreshBtn').disabled = false;
                document.getElementById('refreshBtn').innerHTML = '<i class="fas fa-sync-alt"></i>';
            }
        });
    }
    
    function showNoDataState() {
        document.getElementById('scrollingContent').innerHTML = 
            '<div class="empty-state">' +
            '    <i class="fas fa-database"></i>' +
            '    <p>No data available</p>' +
            '    <small>Check connection or date filter</small>' +
            '</div>';
        
        document.getElementById('totalSuppliers').innerText = '0';
        document.getElementById('completedCount').innerText = '0';
        document.getElementById('onProgressCount').innerText = '0';
        document.getElementById('delayedCount').innerText = '0';
        document.getElementById('overCount').innerText = '0';
    }
    
    // ========== REFRESH & AUTO-REFRESH ==========
    function refreshAllData() {
        if (isRefreshing) return;
        isRefreshing = true;
        
        saveScrollPosition();
        
        var wasScrolling = isAutoScrolling;
        if (isAutoScrolling) stopAutoScroll();
        
        updateTodayGauge();
        updateHourlyChart();
        updateInformation();
        updateLiveTable();
        updateDateTime();
        
        setTimeout(function() {
            restoreScrollPosition();
            if (wasScrolling) {
                setTimeout(function() {
                    startAutoScroll();
                }, 500);
            }
            isRefreshing = false;
        }, 2000);
    }
    
    function smartAutoRefresh() {
        if (!isAutoRefresh) return;
        
        saveScrollPosition();
        updateTodayGauge();
        updateHourlyChart();
        updateInformation();
        updateLiveTableSilent();
        updateDateTime();
    }
    
    function toggleAutoRefresh() {
        isAutoRefresh = !isAutoRefresh;
        var btn = document.getElementById('autoRefreshBtn');
        var text = document.getElementById('autoRefreshText');
        
        if (isAutoRefresh) {
            startAutoRefresh();
            text.innerText = 'Auto: ON';
            btn.classList.remove('control-btn-secondary');
            btn.classList.add('control-btn-primary');
        } else {
            stopAutoRefresh();
            text.innerText = 'Auto: OFF';
            btn.classList.remove('control-btn-primary');
            btn.classList.add('control-btn-secondary');
        }
    }
    
    function startAutoRefresh() {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        autoRefreshInterval = setInterval(smartAutoRefresh, 8 * 60 * 1000);
    }
    
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }
    
    // ========== INITIALIZATION ==========
    $(document).ready(function() {
        console.log('🚀 LIVE DASHBOARD INITIALIZING...');
        
        updateDateTime();
        updateTodayGauge();
        updateHourlyChart();
        updateInformation();
        updateLiveTable();
        
        startAutoRefresh();
        setInterval(updateDateTime, 1000);
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoRefresh();
                stopAutoScroll();
                if (infoScrollInterval) clearInterval(infoScrollInterval);
            } else {
                if (isAutoRefresh) startAutoRefresh();
                if (isAutoScrolling) startAutoScroll();
                if (isInfoScrolling) startInfoAutoScroll();
                
                setTimeout(function() {
                    smartAutoRefresh();
                }, 1000);
            }
        });
        
        window.addEventListener('beforeunload', function() {
            saveScrollPosition();
            try {
                sessionStorage.setItem('lastScrollPosition', lastKnownScrollPosition);
            } catch(e) {}
        });
        
        try {
            var savedPosition = sessionStorage.getItem('lastScrollPosition');
            if (savedPosition) {
                lastKnownScrollPosition = parseInt(savedPosition);
                setTimeout(function() {
                    restoreScrollPosition();
                }, 500);
                sessionStorage.removeItem('lastScrollPosition');
            }
        } catch(e) {}
    });
    </script>
</body>
</html>
// assets/js/notifications.js - VERSION FIX (NO DUPLICATE INIT)
class NotificationSystem {
    constructor() {
        console.log('🔔 NotificationSystem constructor called');
        
        this.pollingInterval = null;
        this.deepCheckInterval = null;
        this.notificationCount = 0;
        this.lastCheckTime = null;
        this.isInitialized = false;
        this.pollingActive = false;
        this.retryCount = 0;
        this.maxRetries = 5;
        this.lastNotificationId = null;
        this.highlightActive = false;
        this.currentHighlightId = null;
        this.pendingHighlightData = null;
        
        // Panggil init setelah semua property siap
        setTimeout(() => {
            this.init();
        }, 100);
    }
    
    init() {
        // Cek apakah sudah diinisialisasi
        if (this.isInitialized) {
            console.log('🔔 NotificationSystem already initialized, skipping...');
            return;
        }
        
        console.log('🔔 NotificationSystem initialized at', new Date().toLocaleTimeString());
        
        // Setup event listeners dulu
        this.setupEventListeners();
        
        // Force start polling immediately
        this.startPollingImmediately();
        
        // Initial load
        this.loadInitialData();
        
        // Check URL parameter for notification highlight
        this.checkUrlForHighlight();
        
        // Cek apakah hari ini Senin (client-side)
        this.checkIfMonday();
        
        this.isInitialized = true;
    }
    
    // ==================== FUNGSI SETUP EVENT LISTENERS ====================
    setupEventListeners() {
        console.log('🔔 Setting up event listeners...');
        
        // Notification dropdown toggle
        $(document).on('show.bs.dropdown', '#notificationDropdown', () => {
            console.log('🔔 Notification dropdown opened');
            this.loadNotifications();
        });
        
        // Mark all as read button
        $(document).on('click', '#markAllRead', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔔 Mark all as read clicked');
            this.markAllAsRead();
        });
        
        // Manual refresh button
        $(document).on('click', '#refreshNotifications', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔔 Manual refresh clicked');
            this.forceCheck();
        });
        
        // Document visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('🔔 Page became visible, checking notifications...');
                this.forceCheck();
            }
        });
        
        console.log('🔔 Event listeners setup complete');
    }
    
    // ==================== FUNGSI CEK HARI SENIN ====================
    checkIfMonday() {
        const today = new Date();
        const day = today.getDay(); // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
        
        if (day === 1) { // Hari Senin
            console.log('📅 Today is Monday! Auto-reset notifications will happen on server');
            
            // Tampilkan pesan di console aja, reset dilakukan di server
            console.log('🧹 Server will auto-reset old notifications today');
            
            // Tambah class CSS untuk badge Senin
            $('.notification-bell').addClass('monday-indicator');
        } else {
            $('.notification-bell').removeClass('monday-indicator');
        }
    }
    
    checkUrlForHighlight() {
        // Implementation if needed
    }
    
    startPollingImmediately() {
        console.log('🔔 Starting immediate polling...');
        
        if (this.pollingInterval) clearInterval(this.pollingInterval);
        if (this.deepCheckInterval) clearInterval(this.deepCheckInterval);
        
        // IMMEDIATE CHECK
        setTimeout(() => {
            console.log('🔔 Executing immediate check...');
            this.checkNewNotifications(true);
        }, 500);
        
        // Regular polling every 5 seconds (increased from 3 to reduce load)
        this.pollingInterval = setInterval(() => {
            if (!this.pollingActive) {
                this.pollingActive = true;
                this.checkNewNotifications(false);
            }
        }, 5000);
        
        // Deep check every 15 seconds
        this.deepCheckInterval = setInterval(() => {
            if (!this.pollingActive) {
                this.pollingActive = true;
                this.checkNewNotifications(true);
            }
        }, 15000);
        
        console.log('🔔 Polling started successfully');
    }
    
    loadInitialData() {
        console.log('🔔 Loading initial data...');
        
        setTimeout(() => {
            this.checkNewNotifications(true);
        }, 1000);
        
        setTimeout(() => {
            this.checkNewNotifications(true);
        }, 3000);
    }
    
    checkNewNotifications(isDeepCheck = false) {
        if (this.pollingActive) {
            console.log('🔔 Polling already active, skipping...');
            return;
        }
        
        this.pollingActive = true;
        const timestamp = new Date().getTime();
        
        $.ajax({
            url: 'api/check_new_info.php',
            type: 'GET',
            data: { 
                _t: timestamp,
                deep: isDeepCheck ? 1 : 0
            },
            dataType: 'json',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeout: 10000, // Increased timeout
            beforeSend: () => {
                this.lastCheckTime = new Date();
            },
            success: (response) => {
                console.log('🔔 Response received:', {
                    success: response.success,
                    count: response.count,
                    assigned: response.assigned_to_me,
                    urgent: response.urgent_count
                });
                
                // ==================== CEK APAKAH ADA RESET MINGGUAN ====================
                if (response.weekly_reset && response.weekly_reset.success) {
                    console.log('🧹 Weekly notification reset executed:', response.weekly_reset.message);
                    console.log(`🧹 Reset count: ${response.weekly_reset.reset_count} notifications`);
                    
                    // Update UI kalau perlu
                    this.showToast('info', `Notifikasi minggu lalu diarsipkan (${response.weekly_reset.reset_count} notif)`);
                    
                    // Refresh notification dropdown
                    this.loadNotifications();
                    
                    // Trigger event
                    $(document).trigger('weeklyResetDone', [response.weekly_reset]);
                }
                
                if (response.success) {
                    const newCount = parseInt(response.count) || 0;
                    const assignedCount = parseInt(response.assigned_to_me) || 0;
                    const urgentCount = parseInt(response.urgent_count) || 0;
                    
                    this.retryCount = 0;
                    
                    // Jika ada notifikasi baru yang urgent
                    if (urgentCount > 0 && newCount > 0 && newCount > this.notificationCount) {
                        console.log(`🔔 New urgent notification detected!`);
                        this.showUrgentNotification(urgentCount, newCount);
                    }
                    
                    // Update badge if count changed
                    if (newCount !== this.notificationCount || isDeepCheck) {
                        console.log(`🔔 Count changed: ${this.notificationCount} → ${newCount}`);
                        
                        this.notificationCount = newCount;
                        this.updateBadge(newCount);
                        
                        if (urgentCount > 0 && newCount > 0) {
                            console.log(`🔔 Urgent notifications found: ${urgentCount}`);
                            this.showNewNotificationIndicator();
                        }
                        
                        if ($('#notificationDropdown').hasClass('show')) {
                            console.log(`🔔 Dropdown is open, refreshing...`);
                            this.loadNotifications();
                        }
                        
                        $(document).trigger('notificationsUpdated', [newCount, urgentCount]);
                    }
                    
                    this.updateTitleBadge(newCount);
                    
                } else {
                    console.error(`🔔 API returned error:`, response);
                }
                
                this.pollingActive = false;
            },
            error: (xhr, status, error) => {
                console.error(`🔔 Polling error:`, error, status);
                
                this.retryCount++;
                
                if (this.retryCount <= this.maxRetries) {
                    console.log(`🔔 Retrying... (${this.retryCount}/${this.maxRetries})`);
                    
                    const retryDelay = Math.min(1000 * Math.pow(2, this.retryCount), 30000);
                    
                    setTimeout(() => {
                        this.pollingActive = false;
                        this.checkNewNotifications(true);
                    }, retryDelay);
                } else {
                    console.error(`🔔 Max retries reached, stopping polling`);
                    this.pollingActive = false;
                }
            },
            complete: () => {
                setTimeout(() => {
                    this.pollingActive = false;
                }, 100);
            }
        });
    }
    
    showNewNotificationIndicator() {
        console.log('🔔 Showing new notification indicator...');
        
        const $bell = $('.notification-bell');
        $bell.addClass('animate__animated animate__tada');
        
        const $badge = $('#notificationBadge');
        $badge.addClass('animate__animated animate__heartBeat');
        
        setTimeout(() => {
            $bell.removeClass('animate__animated animate__tada');
            $badge.removeClass('animate__animated animate__heartBeat');
        }, 2000);
    }
    
    showUrgentNotification(urgentCount, totalCount) {
        const $badge = $('#notificationBadge');
        
        $badge.addClass('animate__animated animate__pulse animate__infinite');
        $badge.removeClass('bg-danger bg-warning bg-primary')
               .addClass('bg-danger');
        
        document.title = `(${totalCount}) Progress BO Control - ${urgentCount} URGENT!`;
        
        this.showVisualToast(urgentCount, totalCount);
    }
    
    showVisualToast(urgentCount, totalCount) {
        $('.urgent-toast').remove();
        
        const toast = $(`
            <div class="custom-toast urgent-toast" style="border-left-color: #dc3545">
                <div class="toast-icon" style="color: #dc3545">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">Notifikasi Baru!</div>
                    <div class="toast-message">
                        ${urgentCount} notifikasi urgent, total ${totalCount} notifikasi baru
                        <br><small class="text-muted">Klik notifikasi untuk melihat detail</small>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(() => toast.addClass('show'), 10);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    loadNotifications() {
        console.log('🔔 Loading notification list...');
        
        $.ajax({
            url: 'api/get_notifications.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            timeout: 5000,
            beforeSend: () => {
                $('#notificationContainer').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <span class="ms-2" style="color: #adb5bd;">Memuat notifikasi...</span>
                    </div>
                `);
            },
            success: (response) => {
                console.log('🔔 Notifications loaded:', {
                    count: response.notifications?.length,
                    unread: response.unread_count,
                    week_info: response.week_info
                });
                
                if (response.success) {
                    this.renderNotifications(response.notifications, response.unread_count);
                    this.updateBadge(response.unread_count);
                    
                    // Update week info display jika ada
                    if (response.week_info) {
                        $('#weekInfoDisplay').text(response.week_info.display_text);
                    }
                } else {
                    this.showEmptyState('Gagal memuat notifikasi');
                }
            },
            error: (xhr) => {
                console.error('🔔 Error loading notifications:', xhr.responseText);
                this.showEmptyState('Error memuat notifikasi');
            }
        });
    }
    
    renderNotifications(notifications, unreadCount) {
        const container = $('#notificationContainer');
        
        if (!notifications || notifications.length === 0) {
            this.showEmptyState();
            return;
        }
        
        let html = '';
        
        const informationNotifs = notifications.filter(n => n.type === 'information');
        const delayNotifs = notifications.filter(n => n.type === 'delay');
        
        const assignedNotifs = informationNotifs.filter(n => n.user_role === 'recipient');
        const viewerNotifs = informationNotifs.filter(n => n.user_role === 'viewer');
        
        if (assignedNotifs.length > 0) {
            html += this.createNotificationGroup('DITUGASKAN UNTUK ANDA', assignedNotifs, 'warning');
        }
        
        if (viewerNotifs.length > 0) {
            html += this.createNotificationGroup('INFORMASI UMUM', viewerNotifs, 'info');
        }
        
        if (delayNotifs.length > 0) {
            html += this.createNotificationGroup('KETERLAMBATAN PENGIRIMAN', delayNotifs, 'danger');
        }
        
        container.html(html);
        
        // Bind click events
        $('.notification-item').on('click', (e) => {
            const $item = $(e.currentTarget);
            const id = $item.data('id');
            const type = $item.data('type');
            const canReply = $item.data('can-reply');
            const userRole = $item.data('user-role');
            
            console.log('🔔 Notification clicked:', { id, type, canReply, userRole });
            
            this.markAsRead(id);
            this.scrollToRelatedInformation(id, type);
        });
    }
    
    createNotificationGroup(title, notifications, color) {
        let html = `
            <div class="notification-group mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-uppercase fw-bold d-block text-${color}">
                        <i class="bi ${this.getGroupIcon(color)} me-1"></i>${title}
                    </small>
                    <span class="badge bg-${color} rounded-pill">${notifications.length}</span>
                </div>
        `;
        
        notifications.forEach(notification => {
            html += this.createNotificationItem(notification, color === 'danger');
        });
        
        html += `</div><hr class="my-3 opacity-25">`;
        
        return html;
    }
    
    getGroupIcon(color) {
        switch(color) {
            case 'danger': return 'bi-exclamation-triangle-fill';
            case 'warning': return 'bi-person-check';
            case 'secondary': return 'bi-bell';
            default: return 'bi-info-circle';
        }
    }
    
    createNotificationItem(notification, isUrgent = false) {
        const isUnread = notification.is_unread || false;
        const timeAgo = this.getTimeAgo(notification.datetime_full);
        const displayMessage = notification.display_message || notification.message || '';
        const canReply = notification.can_reply || false;
        const userRole = notification.user_role || 'viewer';
        
        let iconColor = 'primary';
        let iconType = 'bi-info-circle';
        
        if (userRole === 'recipient') {
            iconColor = notification.badge_color === 'danger' ? 'danger' : 'warning';
            iconType = 'bi-person-check';
        } else if (notification.type === 'delay') {
            iconColor = 'danger';
            iconType = 'bi-clock';
        }
        
        let roleBadge = '';
        if (userRole === 'recipient') {
            roleBadge = `<span class="badge bg-warning ms-2" style="font-size: 0.6rem;">UNTUK ANDA</span>`;
        } else if (notification.type === 'delay') {
            roleBadge = `<span class="badge bg-danger ms-2" style="font-size: 0.6rem;">TERLAMBAT</span>`;
        } else {
            roleBadge = `<span class="badge bg-secondary ms-2" style="font-size: 0.6rem;">INFO</span>`;
        }
        
        let replyBadge = '';
        if (canReply) {
            replyBadge = `<span class="badge bg-success ms-1" style="font-size: 0.5rem;">BISA REPLY</span>`;
        }
        
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''} ${isUrgent ? 'urgent-blink' : ''}" 
                data-id="${notification.id}" 
                data-type="${notification.type}"
                data-can-reply="${canReply}"
                data-user-role="${userRole}"
                style="cursor: pointer; border-left: 3px solid ${userRole === 'recipient' ? '#ffc107' : '#0d6efd'};"
                title="Klik untuk melihat detail di tabel informasi">
                <div class="d-flex gap-3 align-items-start py-2">
                    <div class="notification-icon bg-${iconColor} rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px;">
                        <i class="bi ${iconType} text-white"></i>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="notification-title mb-0 text-truncate" style="font-size: 0.9rem; color: ${isUrgent ? '#dc3545' : '#1e293b'}">
                                ${notification.title} ${roleBadge} ${replyBadge}
                            </h6>
                            <span class="badge bg-${notification.badge_color} status-badge" 
                                style="font-size: 0.65rem; padding: 2px 6px;">
                                ${notification.status_text}
                            </span>
                        </div>
                        <p class="notification-message mb-2" style="font-size: 0.85rem; color: #64748b;">
                            ${displayMessage}
                        </p>
                        <div class="notification-meta d-flex justify-content-between align-items-center">
                            <span class="notification-time" style="font-size: 0.75rem; color: #94a3b8;">
                                <i class="bi bi-clock me-1"></i>${timeAgo}
                            </span>
                            <span class="notification-from" style="font-size: 0.75rem; color: #64748b;">
                                <i class="bi bi-person me-1"></i>${notification.from_user}
                                ${userRole === 'recipient' ? ' → <i class="bi bi-arrow-right me-1"></i>ANDA' : ''}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    getTimeAgo(dateTimeString) {
        if (!dateTimeString) return 'Baru saja';
        
        const now = new Date();
        const past = new Date(dateTimeString);
        const diffMs = now - past;
        const diffMins = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffMins < 1) return 'Baru saja';
        if (diffMins < 60) return `${diffMins} menit lalu`;
        if (diffHours < 24) return `${diffHours} jam lalu`;
        if (diffDays < 7) return `${diffDays} hari lalu`;
        
        return new Date(dateTimeString).toLocaleDateString('id-ID');
    }
    
    showEmptyState(message = 'Tidak ada notifikasi') {
        $('#notificationContainer').html(`
            <div class="empty-notifications text-center py-5">
                <i class="bi bi-bell-slash" style="font-size: 3rem; color: #94a3b8;"></i>
                <p class="mt-3 mb-1" style="color: #64748b;">${message}</p>
                <small style="color: #94a3b8;">Notifikasi baru akan muncul di sini</small>
            </div>
        `);
    }
    
    updateBadge(count) {
        const $badge = $('#notificationBadge');
        const $infoBadge = $('#info-badge');
        
        if (count > 0) {
            $badge.text(count).show().addClass('bg-danger');
            $infoBadge.text(count).show().addClass('bg-danger');
        } else {
            $badge.hide();
            $infoBadge.hide();
        }
    }
    
    updateTitleBadge(count) {
        if (count > 0) {
            document.title = `(${count}) Progress BO Control`;
        } else {
            document.title = "Progress BO Control";
        }
    }
    
    markAsRead(notificationId) {
        if (!notificationId) return;
        
        $.ajax({
            url: 'api/mark_notification_read.php',
            type: 'POST',
            data: { notification_id: notificationId },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    console.log(`✅ Marked notification ${notificationId} as read`);
                    $(`.notification-item[data-id="${notificationId}"]`).removeClass('unread');
                    this.checkNewNotifications(true);
                    
                    // Jika notifikasi lama, beri info
                    if (response.is_old) {
                        console.log('📅 This is an old notification');
                    }
                }
            },
            error: (xhr) => {
                console.error('Error marking as read:', xhr.responseText);
            }
        });
    }
    
    markAllAsRead() {
        const $unreadItems = $('.notification-item.unread');
        const notificationIds = [];
        
        $unreadItems.each(function() {
            const id = $(this).data('id');
            if (id) notificationIds.push(id);
        });
        
        if (notificationIds.length === 0) {
            this.showToast('info', 'Tidak ada notifikasi yang belum dibaca');
            return;
        }
        
        notificationIds.forEach(id => {
            this.markAsRead(id);
        });
        
        $unreadItems.removeClass('unread');
        
        this.showToast('success', `Marked ${notificationIds.length} notifications as read`);
    }
    
    scrollToRelatedInformation(notificationId, notificationType) {
        console.log(`🎯 Scroll to information: ${notificationId}, type: ${notificationType}`);
        
        if (notificationType === 'information') {
            $('#notificationDropdown').dropdown('hide');
            
            const $informationSection = $('#information-section');
            if ($informationSection.length) {
                $('html, body').stop().animate({
                    scrollTop: $informationSection.offset().top - 80
                }, 300);
            }
            
            setTimeout(() => {
                this.highlightInformationRow(notificationId);
            }, 50);
            
        } else if (notificationType === 'delay') {
            $('#notificationDropdown').dropdown('hide');
            this.showToast('info', 'Notification keterlambatan pengiriman');
        }
    }
    
    highlightInformationRow(notificationId) {
        console.log(`🎨 Highlighting row for notification ID: ${notificationId}`);
        
        $('.highlighted-row').removeClass('highlighted-row');
        
        let foundRow = null;
        foundRow = $(`tr[data-id="${notificationId}"]`);
        
        if (!foundRow.length) {
            $('#table-information tbody tr').each(function() {
                const $row = $(this);
                const rowId = $row.data('id');
                
                if (rowId == notificationId || 
                    $row.find('.btn-edit-info[data-id="' + notificationId + '"]').length ||
                    $row.find('.btn-reply-info[data-id="' + notificationId + '"]').length) {
                    foundRow = $row;
                    return false;
                }
            });
        }
        
        if (foundRow && foundRow.length) {
            console.log(`✅ Found row!`);
            
            foundRow.addClass('highlighted-row animate__animated animate__pulse');
            foundRow.css({
                'border-left': '4px solid #ff0000',
                'border-right': '4px solid #ff0000',
                'background-color': 'rgba(255, 0, 0, 0.15)',
                'box-shadow': '0 0 20px rgba(255, 0, 0, 0.4)'
            });
            
            setTimeout(() => {
                const rowPosition = foundRow.offset().top;
                $('html, body').stop().animate({
                    scrollTop: rowPosition - 150
                }, 200);
                
                foundRow.addClass('animate__flash');
                setTimeout(() => {
                    foundRow.removeClass('animate__flash');
                }, 1000);
                
            }, 100);
            
            setTimeout(() => {
                foundRow.removeClass('highlighted-row animate__animated animate__pulse');
                foundRow.css({
                    'border-left': '',
                    'border-right': '',
                    'background-color': '',
                    'box-shadow': ''
                });
            }, 10000);
            
        } else {
            console.log(`❌ Row not found`);
        }
    }
    
    showToast(type, message) {
        $('.custom-toast').remove();
        
        const iconMap = {
            'success': 'bi-check-circle-fill',
            'error': 'bi-exclamation-triangle-fill',
            'warning': 'bi-exclamation-triangle-fill',
            'info': 'bi-info-circle-fill'
        };
        
        const colorMap = {
            'success': '#10b981',
            'error': '#dc3545',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        
        const icon = iconMap[type] || 'bi-info-circle-fill';
        const color = colorMap[type] || '#3b82f6';
        const title = type === 'success' ? 'Success' : 
                     type === 'error' ? 'Error' : 
                     type === 'warning' ? 'Warning' : 'Info';
        
        const toast = $(`
            <div class="custom-toast" style="border-left-color: ${color}">
                <div class="toast-icon" style="color: ${color}">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(() => toast.addClass('show'), 10);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    // Public method untuk force check
    forceCheck() {
        console.log('🔔 Force check called');
        if (!this.pollingActive) {
            this.checkNewNotifications(true);
        } else {
            console.log('🔔 Polling active, skipping force check');
        }
    }
}

// Pastikan hanya satu instance yang dibuat
if (typeof window.notificationSystem === 'undefined') {
    $(document).ready(function() {
        console.log('🔔 Initializing NotificationSystem (single instance)...');
        
        // Cek apakah sudah ada instance
        if (!window.notificationSystem) {
            window.notificationSystem = new NotificationSystem();
            console.log('✅ NotificationSystem initialized');
        }
        
        // Expose global functions
        window.forceNotificationCheck = function() {
            if (window.notificationSystem) {
                window.notificationSystem.forceCheck();
            }
        };
        
        window.highlightNotificationRow = function(notificationId) {
            if (window.notificationSystem) {
                window.notificationSystem.highlightInformationRow(notificationId);
            }
        };
        
        // Tambah CSS untuk Monday indicator
        if (!$('#notification-style').length) {
            $('head').append(`
                <style id="notification-style">
                    .monday-indicator {
                        animation: mondayPulse 2s infinite;
                    }
                    
                    @keyframes mondayPulse {
                        0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
                        70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
                        100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
                    }
                    
                    .weekly-reset-toast {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                    }
                </style>
            `);
        }
    });
} else {
    console.log('🔔 NotificationSystem already exists, skipping initialization');
}
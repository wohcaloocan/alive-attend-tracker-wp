
<?php
/**
 * Admin Menu setup for Youth Alive Attendance Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class YAAT_Admin_Menu {
    
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Ajax handlers
        add_action('wp_ajax_yaat_delete_attendance', array($this, 'ajax_delete_attendance'));
        add_action('wp_ajax_yaat_add_manual_attendance', array($this, 'ajax_add_manual_attendance'));
        add_action('wp_ajax_yaat_update_user_tracking', array($this, 'ajax_update_user_tracking'));
    }
    
    /**
     * Register admin menu pages
     */
    public function register_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Youth Alive Attendance', 'youth-alive-attendance'),
            __('Youth Attendance', 'youth-alive-attendance'),
            'manage_options',
            'youth-alive-attendance',
            array($this, 'dashboard_page'),
            'dashicons-groups',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'youth-alive-attendance',
            __('Dashboard', 'youth-alive-attendance'),
            __('Dashboard', 'youth-alive-attendance'),
            'manage_options',
            'youth-alive-attendance',
            array($this, 'dashboard_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'youth-alive-attendance',
            __('Reports', 'youth-alive-attendance'),
            __('Reports', 'youth-alive-attendance'),
            'manage_options',
            'youth-alive-attendance-reports',
            array($this, 'reports_page')
        );
        
        // Export submenu
        add_submenu_page(
            'youth-alive-attendance',
            __('Export', 'youth-alive-attendance'),
            __('Export', 'youth-alive-attendance'),
            'manage_options',
            'youth-alive-attendance-export',
            array($this, 'export_page')
        );
        
        // Settings submenu - NEW
        add_submenu_page(
            'youth-alive-attendance',
            __('Settings', 'youth-alive-attendance'),
            __('Settings', 'youth-alive-attendance'),
            'manage_options',
            'youth-alive-attendance-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Dashboard page callback
     */
    public function dashboard_page() {
        include_once YAAT_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
    }
    
    /**
     * Reports page callback
     */
    public function reports_page() {
        include_once YAAT_PLUGIN_DIR . 'includes/admin/views/reports.php';
    }
    
    /**
     * Export page callback
     */
    public function export_page() {
        include_once YAAT_PLUGIN_DIR . 'includes/admin/views/export.php';
    }
    
    /**
     * Settings page callback - NEW
     */
    public function settings_page() {
        include_once YAAT_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }
    
    /**
     * Ajax handler for deleting attendance records
     */
    public function ajax_delete_attendance() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yaat_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'youth-alive-attendance')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'youth-alive-attendance')));
        }
        
        // Check for attendance ID
        if (empty($_POST['attendance_id'])) {
            wp_send_json_error(array('message' => __('No attendance ID provided.', 'youth-alive-attendance')));
        }
        
        $attendance_id = intval($_POST['attendance_id']);
        
        // Delete the attendance record
        $database = new YAAT_Database();
        $result = $database->delete_attendance($attendance_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Attendance record deleted successfully.', 'youth-alive-attendance')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete attendance record.', 'youth-alive-attendance')));
        }
    }
    
    /**
     * Ajax handler for adding manual attendance
     */
    public function ajax_add_manual_attendance() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yaat_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'youth-alive-attendance')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'youth-alive-attendance')));
        }
        
        // Check required fields
        if (empty($_POST['user_id']) || empty($_POST['attendance_date'])) {
            wp_send_json_error(array('message' => __('Missing required fields.', 'youth-alive-attendance')));
        }
        
        $user_id = intval($_POST['user_id']);
        $attendance_date = sanitize_text_field($_POST['attendance_date']);
        
        // Validate date format
        $date_obj = DateTime::createFromFormat('Y-m-d', $attendance_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $attendance_date) {
            wp_send_json_error(array('message' => __('Invalid date format. Use YYYY-MM-DD.', 'youth-alive-attendance')));
        }
        
        // Check if user exists
        if (!get_user_by('id', $user_id)) {
            wp_send_json_error(array('message' => __('User does not exist.', 'youth-alive-attendance')));
        }
        
        // Mark attendance
        $database = new YAAT_Database();
        
        // Check if already marked
        if ($database->has_marked_attendance($user_id, $attendance_date)) {
            wp_send_json_error(array('message' => __('Attendance already marked for this user on this date.', 'youth-alive-attendance')));
        }
        
        $result = $database->mark_attendance($user_id, $attendance_date);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Attendance added successfully.', 'youth-alive-attendance')));
        } else {
            wp_send_json_error(array('message' => __('Failed to add attendance.', 'youth-alive-attendance')));
        }
    }
    
    /**
     * Ajax handler for updating user tracking settings
     */
    public function ajax_update_user_tracking() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yaat_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'youth-alive-attendance')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'youth-alive-attendance')));
        }
        
        // Check for user ID and tracking status
        if (!isset($_POST['user_id']) || !isset($_POST['track'])) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'youth-alive-attendance')));
        }
        
        $user_id = intval($_POST['user_id']);
        $track = (bool) $_POST['track'];
        
        // Update user meta
        update_user_meta($user_id, 'yaat_track_attendance', $track ? '1' : '0');
        
        wp_send_json_success(array(
            'message' => $track ? 
                __('User is now being tracked for attendance.', 'youth-alive-attendance') : 
                __('User is no longer being tracked for attendance.', 'youth-alive-attendance')
        ));
    }
}

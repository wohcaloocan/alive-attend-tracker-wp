
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
}

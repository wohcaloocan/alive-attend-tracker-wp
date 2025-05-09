
<?php
/**
 * Attendance Dashboard functionality for Youth Alive Attendance Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class YAAT_Attendance_Dashboard {
    
    public function __construct() {
        // Ajax handlers for the dashboard
        add_action('wp_ajax_yaat_load_top_attendees', array($this, 'ajax_load_top_attendees'));
        add_action('wp_ajax_yaat_add_manual_attendance', array($this, 'ajax_add_manual_attendance'));
        add_action('wp_ajax_yaat_delete_attendance', array($this, 'ajax_delete_attendance'));
        add_action('wp_ajax_yaat_update_user_tracking', array($this, 'ajax_update_user_tracking'));
    }
    
    /**
     * Load top attendees via Ajax
     */
    public function ajax_load_top_attendees() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yaat_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'youth-alive-attendance')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to do this.', 'youth-alive-attendance')));
        }
        
        // Get filter parameters
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : 'month';
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $quarter = isset($_POST['quarter']) ? intval($_POST['quarter']) : ceil(date('n') / 3);
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        // Get top attendees
        $database = new YAAT_Database();
        $top_attendees = $database->get_top_attendees($filter_type, $year, $month, $quarter, $limit);
        
        wp_send_json_success(array(
            'attendees' => $top_attendees,
            'filter_display' => $this->get_filter_display($filter_type, $year, $month, $quarter)
        ));
    }
    
    /**
     * Add manual attendance via Ajax
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
        
        // Get parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $attendance_date = isset($_POST['attendance_date']) ? sanitize_text_field($_POST['attendance_date']) : '';
        
        if (empty($user_id) || empty($attendance_date)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'youth-alive-attendance')));
        }
        
        // Check if user is marked for tracking
        $track_attendance = get_user_meta($user_id, 'yaat_track_attendance', true);
        if ($track_attendance === '0') {
            wp_send_json_error(array('message' => __('This user is not marked for attendance tracking.', 'youth-alive-attendance')));
            return;
        }
        
        // Mark attendance
        $database = new YAAT_Database();
        $result = $database->mark_attendance($user_id, $attendance_date);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Attendance recorded successfully.', 'youth-alive-attendance')));
        } else {
            wp_send_json_error(array('message' => __('Failed to record attendance. User may already have attendance for this date.', 'youth-alive-attendance')));
        }
    }
    
    /**
     * Delete attendance via Ajax
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
        
        // Get parameters
        $attendance_id = isset($_POST['attendance_id']) ? intval($_POST['attendance_id']) : 0;
        
        if (empty($attendance_id)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'youth-alive-attendance')));
        }
        
        // Delete attendance
        $database = new YAAT_Database();
        $result = $database->delete_attendance($attendance_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Attendance deleted successfully.', 'youth-alive-attendance')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete attendance.', 'youth-alive-attendance')));
        }
    }
    
    /**
     * Update user tracking status via Ajax
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
        
        // Get parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $track = isset($_POST['track']) ? intval($_POST['track']) : 0;
        
        if (empty($user_id)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'youth-alive-attendance')));
        }
        
        // Update user meta
        update_user_meta($user_id, 'yaat_track_attendance', $track ? '1' : '0');
        
        wp_send_json_success(array('message' => __('User tracking status updated.', 'youth-alive-attendance')));
    }
    
    /**
     * Get display text for filter
     */
    private function get_filter_display($filter_type, $year, $month, $quarter) {
        switch ($filter_type) {
            case 'month':
                return date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year));
                
            case 'quarter':
                return sprintf(__('Q%d %d', 'youth-alive-attendance'), $quarter, $year);
                
            case 'year':
                return $year;
                
            case 'all':
                return __('All Time', 'youth-alive-attendance');
                
            default:
                return '';
        }
    }
}

// Initialize the dashboard functionality
new YAAT_Attendance_Dashboard();


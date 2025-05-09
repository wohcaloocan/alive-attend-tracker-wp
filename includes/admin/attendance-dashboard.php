
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

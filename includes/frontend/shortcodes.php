
<?php
/**
 * Shortcodes for Youth Alive Attendance Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class YAAT_Shortcodes {
    
    public function __construct() {
        // Register shortcodes
        add_shortcode('attendance_button', array($this, 'attendance_button_shortcode'));
        
        // Ajax handler for marking attendance
        add_action('wp_ajax_yaat_mark_attendance', array($this, 'ajax_mark_attendance'));
        add_action('wp_ajax_nopriv_yaat_mark_attendance', array($this, 'ajax_not_logged_in'));
    }
    
    /**
     * Attendance button shortcode callback
     */
    public function attendance_button_shortcode($atts) {
        // Not logged in users see login prompt
        if (!is_user_logged_in()) {
            return '<div class="yaat-attendance-container">
                <p>' . __('You must be logged in to mark attendance.', 'youth-alive-attendance') . '</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="yaat-login-button">' . __('Log In', 'youth-alive-attendance') . '</a>
            </div>';
        }
        
        // Get current user
        $user_id = get_current_user_id();
        
        // Check if user is tracked
        $track_attendance = get_user_meta($user_id, 'yaat_track_attendance', true);
        if ($track_attendance === '0') {
            return '<div class="yaat-attendance-container">
                <div class="yaat-attendance-restricted">
                    <p>' . __('Your attendance is not being tracked. Please contact an administrator.', 'youth-alive-attendance') . '</p>
                </div>
            </div>';
        }
        
        // Check if user has already marked attendance today
        $database = new YAAT_Database();
        $already_marked = $database->has_marked_attendance($user_id);
        
        // Generate the output
        ob_start();
        ?>
        <div class="yaat-attendance-container">
            <h3><?php echo date_i18n('F j, Y'); ?></h3>
            
            <?php if ($already_marked): ?>
                <div class="yaat-attendance-marked">
                    <div class="yaat-checkmark">✓</div>
                    <p><?php _e('You have already marked your attendance for today.', 'youth-alive-attendance'); ?></p>
                </div>
            <?php else: ?>
                <button id="yaat-mark-attendance" class="yaat-button">
                    <?php _e('Mark My Attendance', 'youth-alive-attendance'); ?>
                </button>
                <div id="yaat-message" class="yaat-message" style="display: none;"></div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Ajax handler for marking attendance
     */
    public function ajax_mark_attendance() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'yaat_frontend_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'youth-alive-attendance')));
        }
        
        // Get current user
        $user_id = get_current_user_id();
        
        // Check if user is tracked
        $track_attendance = get_user_meta($user_id, 'yaat_track_attendance', true);
        if ($track_attendance === '0') {
            wp_send_json_error(array('message' => __('Your attendance is not being tracked. Please contact an administrator.', 'youth-alive-attendance')));
            return;
        }
        
        // Check if already marked
        $database = new YAAT_Database();
        if ($database->has_marked_attendance($user_id)) {
            wp_send_json_error(array('message' => __('You have already marked your attendance for today.', 'youth-alive-attendance')));
            return;
        }
        
        // Mark attendance
        $result = $database->mark_attendance($user_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Your attendance has been marked successfully!', 'youth-alive-attendance')));
        } else {
            wp_send_json_error(array('message' => __('There was an error marking your attendance. Please try again.', 'youth-alive-attendance')));
        }
    }
    
    /**
     * Handler for not logged in users
     */
    public function ajax_not_logged_in() {
        wp_send_json_error(array(
            'message' => __('You must be logged in to mark attendance.', 'youth-alive-attendance'),
            'login_url' => wp_login_url()
        ));
    }
}

// Initialize shortcodes
new YAAT_Shortcodes();


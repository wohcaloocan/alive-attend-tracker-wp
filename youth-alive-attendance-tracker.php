
<?php
/**
 * Plugin Name: Youth Alive Attendance Tracker
 * Plugin URI: https://youthalive.org
 * Description: A WordPress plugin for tracking Youth Alive member attendance with frontend marking capability and administrative management features.
 * Version: 1.0.0
 * Author: Lovable
 * Text Domain: youth-alive-attendance
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YAAT_VERSION', '1.0.0');
define('YAAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YAAT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once YAAT_PLUGIN_DIR . 'includes/database.php';
require_once YAAT_PLUGIN_DIR . 'includes/admin/admin-menu.php';
require_once YAAT_PLUGIN_DIR . 'includes/admin/attendance-dashboard.php';
require_once YAAT_PLUGIN_DIR . 'includes/admin/reports.php';
require_once YAAT_PLUGIN_DIR . 'includes/admin/export.php';
require_once YAAT_PLUGIN_DIR . 'includes/frontend/shortcodes.php';

// Initialize the plugin
class Youth_Alive_Attendance_Tracker {
    
    // Constructor
    public function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Initialize the database
        add_action('plugins_loaded', array($this, 'init_database'));
        
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        
        // Initialize admin and frontend components
        new YAAT_Admin_Menu();
    }
    
    // Plugin activation
    public function activate() {
        // Create necessary database tables
        $database = new YAAT_Database();
        $database->create_tables();
        
        // Set version
        update_option('yaat_version', YAAT_VERSION);
    }
    
    // Initialize database
    public function init_database() {
        $database = new YAAT_Database();
    }
    
    // Load text domain for translations
    public function load_textdomain() {
        load_plugin_textdomain('youth-alive-attendance', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    // Load admin scripts and styles
    public function admin_scripts($hook) {
        // Only load on plugin admin pages
        if (strpos($hook, 'youth-alive-attendance') !== false) {
            wp_enqueue_style('yaat-admin-styles', YAAT_PLUGIN_URL . 'assets/css/admin-styles.css', array(), YAAT_VERSION);
            wp_enqueue_script('yaat-admin-scripts', YAAT_PLUGIN_URL . 'assets/js/admin-scripts.js', array('jquery', 'jquery-ui-datepicker'), YAAT_VERSION, true);
            wp_localize_script('yaat-admin-scripts', 'yaat_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yaat_admin_nonce'),
                'confirm_delete' => __('Are you sure you want to delete this attendance record?', 'youth-alive-attendance'),
                'add_attendance_success' => __('Attendance record added successfully.', 'youth-alive-attendance'),
                'add_attendance_error' => __('Failed to add attendance record.', 'youth-alive-attendance')
            ));
            wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        }
    }
    
    // Load frontend scripts and styles
    public function frontend_scripts() {
        wp_enqueue_style('yaat-frontend-styles', YAAT_PLUGIN_URL . 'assets/css/frontend-styles.css', array(), YAAT_VERSION);
        wp_enqueue_script('yaat-frontend-scripts', YAAT_PLUGIN_URL . 'assets/js/frontend-scripts.js', array('jquery'), YAAT_VERSION, true);
        wp_localize_script('yaat-frontend-scripts', 'yaat_front', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yaat_frontend_nonce'),
            'success_message' => __('Your attendance has been marked successfully!', 'youth-alive-attendance'),
            'already_marked' => __('You have already marked your attendance for today.', 'youth-alive-attendance'),
            'error_message' => __('There was an error marking your attendance. Please try again.', 'youth-alive-attendance')
        ));
    }
}

// Initialize the plugin
$youth_alive_attendance_tracker = new Youth_Alive_Attendance_Tracker();


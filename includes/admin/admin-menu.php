
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
        
        // Settings submenu
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
     * Settings page callback
     */
    public function settings_page() {
        include_once YAAT_PLUGIN_DIR . 'includes/admin/views/settings.php';
    }
}


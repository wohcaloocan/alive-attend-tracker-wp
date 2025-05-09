
<?php
/**
 * Database setup and management for Youth Alive Attendance Tracker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class YAAT_Database {
    
    private $attendance_table;
    
    public function __construct() {
        global $wpdb;
        $this->attendance_table = $wpdb->prefix . 'yaat_attendance';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Attendance table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->attendance_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            attendance_date date NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY attendance_date (attendance_date),
            UNIQUE KEY user_date (user_id, attendance_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if a user has marked attendance on a specific date
     */
    public function has_marked_attendance($user_id, $date = null) {
        global $wpdb;
        
        if ($date === null) {
            $date = current_time('Y-m-d');
        }
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->attendance_table} WHERE user_id = %d AND attendance_date = %s",
                $user_id,
                $date
            )
        );
        
        return !empty($result);
    }
    
    /**
     * Mark attendance for a user
     */
    public function mark_attendance($user_id, $date = null) {
        global $wpdb;
        
        if ($date === null) {
            $date = current_time('Y-m-d');
        }
        
        // Check if already marked
        if ($this->has_marked_attendance($user_id, $date)) {
            return false;
        }
        
        $result = $wpdb->insert(
            $this->attendance_table,
            array(
                'user_id' => $user_id,
                'attendance_date' => $date,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete an attendance record
     */
    public function delete_attendance($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->attendance_table,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Get attendance records with filtering options
     */
    public function get_attendance_records($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => 0,
            'year' => '',
            'month' => '',
            'start_date' => '',
            'end_date' => '',
            'orderby' => 'attendance_date',
            'order' => 'DESC',
            'limit' => 0,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $values = array();
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }
        
        if (!empty($args['year'])) {
            $where[] = 'YEAR(attendance_date) = %d';
            $values[] = $args['year'];
        }
        
        if (!empty($args['month'])) {
            $where[] = 'MONTH(attendance_date) = %d';
            $values[] = $args['month'];
        }
        
        if (!empty($args['start_date'])) {
            $where[] = 'attendance_date >= %s';
            $values[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = 'attendance_date <= %s';
            $values[] = $args['end_date'];
        }
        
        $sql = "SELECT * FROM {$this->attendance_table}";
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Add orderby
        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        
        // Add limit
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $values[] = $args['limit'];
            
            if ($args['offset'] > 0) {
                $sql .= " OFFSET %d";
                $values[] = $args['offset'];
            }
        }
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get attendance count for each user
     */
    public function get_attendance_counts($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'year' => '',
            'month' => '',
            'quarter' => '',
            'start_date' => '',
            'end_date' => '',
            'limit' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $values = array();
        
        if (!empty($args['year'])) {
            $where[] = 'YEAR(attendance_date) = %d';
            $values[] = $args['year'];
        }
        
        if (!empty($args['month'])) {
            $where[] = 'MONTH(attendance_date) = %d';
            $values[] = $args['month'];
        }
        
        if (!empty($args['quarter'])) {
            $quarter = intval($args['quarter']);
            $start_month = ($quarter - 1) * 3 + 1;
            $end_month = $quarter * 3;
            
            $where[] = 'MONTH(attendance_date) BETWEEN %d AND %d';
            $values[] = $start_month;
            $values[] = $end_month;
        }
        
        if (!empty($args['start_date'])) {
            $where[] = 'attendance_date >= %s';
            $values[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = 'attendance_date <= %s';
            $values[] = $args['end_date'];
        }
        
        $sql = "SELECT user_id, COUNT(*) as count FROM {$this->attendance_table}";
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " GROUP BY user_id ORDER BY count DESC";
        
        if ($args['limit'] > 0) {
            $sql .= " LIMIT %d";
            $values[] = $args['limit'];
        }
        
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get attendance summary data for dashboard
     */
    public function get_attendance_grid($year, $month) {
        global $wpdb;
        
        // Get all users who have attendance records
        $user_query = new WP_User_Query(array(
            'meta_key' => 'wp_capabilities',
            'meta_value' => 'subscriber',
            'meta_compare' => 'LIKE',
        ));
        
        $users = $user_query->get_results();
        
        // Get start and end dates for the selected month
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Get all attendance records for this period
        $records = $this->get_attendance_records(array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
        
        // Format data for grid display
        $data = array();
        foreach ($users as $user) {
            $user_data = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'dates' => array()
            );
            
            // Initialize all dates to false
            $current_date = $start_date;
            while ($current_date <= $end_date) {
                $user_data['dates'][$current_date] = false;
                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
            }
            
            $data[$user->ID] = $user_data;
        }
        
        // Fill in attendance data
        foreach ($records as $record) {
            if (isset($data[$record->user_id])) {
                $data[$record->user_id]['dates'][$record->attendance_date] = $record->id;
            }
        }
        
        return array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'users' => $data
        );
    }
}

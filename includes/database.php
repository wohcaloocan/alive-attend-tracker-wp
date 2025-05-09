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
     * Get top attendees based on filter criteria
     * 
     * @param string $filter_type The type of filter (month, quarter, year, all)
     * @param int $year The year to filter by (if applicable)
     * @param int $month The month to filter by (if applicable)
     * @param int $quarter The quarter to filter by (if applicable)
     * @param int $limit The maximum number of records to return
     * @return array Array of user objects with attendance counts
     */
    public function get_top_attendees($filter_type = 'month', $year = null, $month = null, $quarter = null, $limit = 5) {
        global $wpdb;
        
        $year = $year ?? date('Y');
        $month = $month ?? date('n');
        
        $args = array(
            'limit' => $limit
        );
        
        switch ($filter_type) {
            case 'month':
                $args['year'] = $year;
                $args['month'] = $month;
                break;
                
            case 'quarter':
                $args['year'] = $year;
                $args['quarter'] = $quarter;
                break;
                
            case 'year':
                $args['year'] = $year;
                break;
                
            case 'all':
                // No additional filters for all-time
                break;
        }
        
        $attendees = $this->get_attendance_counts($args);
        $result = array();
        
        foreach ($attendees as $attendee) {
            $user = get_user_by('id', $attendee->user_id);
            if ($user) {
                $result[] = array(
                    'user_id' => $attendee->user_id,
                    'name' => $user->display_name,
                    'count' => $attendee->count
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get users with attendance data for specified period
     */
    public function get_users_with_attendance($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'year' => date('Y'),
            'month' => date('n'),
            'per_page' => 10,
            'page' => 1,
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        $year = $args['year'];
        $month = $args['month'];
        
        // Get start and end dates for the selected month
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        // Get users who should be tracked for attendance
        $tracked_users = $this->get_tracked_users($args);
        
        if (empty($tracked_users)) {
            return array(
                'users' => array(),
                'total' => 0
            );
        }
        
        // Get attendance data for this period
        $attendance_records = $this->get_attendance_records(array(
            'start_date' => $start_date,
            'end_date' => $end_date
        ));
        
        $user_attendance = array();
        foreach ($attendance_records as $record) {
            if (!isset($user_attendance[$record->user_id])) {
                $user_attendance[$record->user_id] = array();
            }
            $user_attendance[$record->user_id][$record->attendance_date] = true;
        }
        
        // Calculate total for pagination
        $total_users = count($tracked_users);
        
        // Apply pagination
        $users = array_slice($tracked_users, ($args['page'] - 1) * $args['per_page'], $args['per_page']);
        
        // Build user data with attendance info
        $data = array();
        foreach ($users as $user) {
            $attendance_days = isset($user_attendance[$user->ID]) ? count($user_attendance[$user->ID]) : 0;
            $data[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'attendance_count' => $attendance_days,
                'total_days' => date('t', strtotime($start_date))
            );
        }
        
        return array(
            'users' => $data,
            'total' => $total_users
        );
    }
    
    /**
     * Get users who should be tracked for attendance
     */
    public function get_tracked_users($args = array()) {
        $defaults = array(
            'search' => '',
            'role' => 'subscriber',
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        $meta_query = array();
        
        // Only include users who are marked for tracking, or who don't have the meta key set at all (default is to track)
        $meta_query['relation'] = 'OR';
        $meta_query[] = array(
            'key' => 'yaat_track_attendance',
            'value' => '1',
            'compare' => '='
        );
        $meta_query[] = array(
            'key' => 'yaat_track_attendance',
            'compare' => 'NOT EXISTS'
        );
        
        $user_query = new WP_User_Query(array(
            'role' => $args['role'],
            'meta_query' => $meta_query,
            'search' => $args['search'],
            'orderby' => $args['orderby'],
            'order' => $args['order']
        ));
        
        return $user_query->get_results();
    }
    
    /**
     * Get all registered users for settings page
     */
    public function get_all_users($args = array()) {
        $defaults = array(
            'search' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'display_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $user_query = new WP_User_Query(array(
            'search' => $args['search'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'number' => $args['per_page'],
            'paged' => $args['page']
        ));
        
        $users = $user_query->get_results();
        $total = $user_query->get_total();
        
        $data = array();
        foreach ($users as $user) {
            $track_attendance = get_user_meta($user->ID, 'yaat_track_attendance', true);
            $track = ($track_attendance === '') ? true : ($track_attendance === '1');
            
            $data[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => $user->roles[0],
                'track' => $track
            );
        }
        
        return array(
            'users' => $data,
            'total' => $total
        );
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

<?php
/**
 * Plugin Name: LearnPress Stats Dashboard
 * Description: Plugin hiển thị thống kê dữ liệu LearnPress ngoài Dashboard Admin và Frontend qua Shortcode.
 * Version: 1.0.0
 * Author: Tên Của Bạn
 * Text Domain: lp-stats-addon
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Ngăn truy cập trực tiếp
}

/**
 * 1. Hàm truy vấn dữ liệu từ LearnPress bằng $wpdb
 */
function lp_stats_get_data() {
    global $wpdb;

    if ( ! class_exists( 'LearnPress' ) ) {
        return false;
    }

    // Tổng số khóa học hiện có (post_type = lp_course và status = publish)
    $total_courses = wp_count_posts( 'lp_course' )->publish;

    // Tên bảng dữ liệu học viên của LearnPress
    $table_user_items = $wpdb->prefix . 'learnpress_user_items';

    // Tổng số học viên đã đăng ký (Đếm user_id không trùng lặp)
    $total_students = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$table_user_items} WHERE item_type = %s",
        'lp_course'
    ) );

    // Số lượng khóa học đã được hoàn thành (Status = completed)
    $completed_courses = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_user_items} WHERE item_type = %s AND status = %s",
        'lp_course',
        'completed'
    ) );

    return array(
        'courses'   => $total_courses ? (int) $total_courses : 0,
        'students'  => $total_students ? (int) $total_students : 0,
        'completed' => $completed_courses ? (int) $completed_courses : 0,
    );
}

/**
 * 2. Tạo Dashboard Widget trong trang quản trị Admin
 */
function lp_stats_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'lp_stats_dashboard_widget', 
        'Thống Kê LearnPress', 
        'lp_stats_dashboard_widget_render'
    );
}
add_action( 'wp_dashboard_setup', 'lp_stats_add_dashboard_widget' );

function lp_stats_dashboard_widget_render() {
    $stats = lp_stats_get_data();

    if ( ! $stats ) {
        echo '<p>Vui lòng cài đặt và kích hoạt LearnPress.</p>';
        return;
    }

    echo '<ul style="font-size: 14px; line-height: 2;">';
    echo '<li><strong>Tổng số khóa học:</strong> ' . esc_html( $stats['courses'] ) . '</li>';
    echo '<li><strong>Tổng số học viên đã đăng ký:</strong> ' . esc_html( $stats['students'] ) . '</li>';
    echo '<li><strong>Khóa học đã hoàn thành:</strong> ' . esc_html( $stats['completed'] ) . '</li>';
    echo '</ul>';
}

/**
 * 3. Tạo Shortcode [lp_total_stats] hiển thị ra Frontend
 */
function lp_stats_shortcode_render() {
    $stats = lp_stats_get_data();

    if ( ! $stats ) {
        return '<p>Hệ thống thống kê đang tạm ngưng.</p>';
    }

    ob_start();
    ?>
    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #fafafa; max-width: 300px;">
        <h3 style="margin-top: 0;">Thống Kê Hệ Thống</h3>
        <p>📚 Khóa học hiện có: <strong><?php echo esc_html( $stats['courses'] ); ?></strong></p>
        <p>👨‍🎓 Học viên đăng ký: <strong><?php echo esc_html( $stats['students'] ); ?></strong></p>
        <p>✅ Khóa học hoàn thành: <strong><?php echo esc_html( $stats['completed'] ); ?></strong></p>
    </div>
    <?php
    return ob_get_clean(); 
}
add_shortcode( 'lp_total_stats', 'lp_stats_shortcode_render' );
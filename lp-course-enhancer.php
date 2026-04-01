<?php
/**
 * Plugin Name: LearnPress Course Enhancer
 * Description: Thêm Notification Bar, Shortcode thông tin khóa học và Tùy biến màu nút bấm LearnPress.
 * Version: 1.0.0
 * Author: Tên Của Bạn
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Ngăn truy cập trực tiếp
}

/**
 * ==========================================================
 * YÊU CẦU 1: Hiển thị thông báo "Học viên mới" (Notification Bar)
 * ==========================================================
 */
add_action( 'wp_footer', 'lp_enhancer_notification_bar' );
function lp_enhancer_notification_bar() {
    // Chỉ hiển thị ở trang chi tiết khóa học (post type: lp_course)
    if ( ! is_singular( 'lp_course' ) ) {
        return;
    }

    $message = '';
    // Kiểm tra trạng thái đăng nhập
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $message = 'Chào ' . esc_html( $current_user->display_name ) . ', bạn đã sẵn sàng bắt đầu bài học hôm nay chưa?';
    } else {
        $message = 'Đăng nhập để lưu tiến độ học tập!';
    }

    // Hiển thị thanh thông báo (Fixed Top)
    echo '<div style="position: fixed; top: 0; left: 0; width: 100%; background-color: #0073aa; color: #fff; text-align: center; padding: 12px; z-index: 99999; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); font-size: 15px;">' . $message . '</div>';
    
    // Đẩy nội dung trang xuống một chút để không bị thanh thông báo che khuất header
    echo '<style>body { padding-top: 45px !important; }</style>';
}

/**
 * ==========================================================
 * YÊU CẦU 2: Hàm thống kê chi tiết khóa học (Shortcode)
 * ==========================================================
 */
add_shortcode( 'lp_course_info', 'lp_enhancer_course_info_shortcode' );
function lp_enhancer_course_info_shortcode( $atts ) {
    // Kiểm tra xem LearnPress có đang hoạt động không
    if ( ! class_exists( 'LearnPress' ) ) {
        return '<p>LearnPress chưa được cài đặt.</p>';
    }

    // Lấy ID từ tham số shortcode [lp_course_info id="xxx"]
    $atts = shortcode_atts( array( 'id' => 0 ), $atts );
    $course_id = intval( $atts['id'] );

    // Nếu không nhập ID nhưng đang đứng ở trang khóa học, tự lấy ID của trang đó
    if ( ! $course_id ) {
        global $post;
        if ( $post && $post->post_type === 'lp_course' ) {
            $course_id = $post->ID;
        } else {
            return '<p>Vui lòng cung cấp ID khóa học (Ví dụ: [lp_course_info id="123"]).</p>';
        }
    }

    // Khởi tạo đối tượng khóa học từ LearnPress
    $course = learn_press_get_course( $course_id );
    if ( ! $course ) {
        return '<p>Khóa học không tồn tại.</p>';
    }

    // 1. Số lượng bài học (lp_lesson)
    $lesson_count = count( $course->get_items( 'lp_lesson' ) );
    
    // 2. Tổng thời gian
    $duration = $course->get_duration(); 

    // 3. Trạng thái của người dùng
    $status_text = 'Chưa đăng ký';
    if ( is_user_logged_in() ) {
        $user = learn_press_get_current_user();
        if ( $user ) {
            $status = $user->get_course_status( $course_id );
            if ( $status == 'enrolled' ) {
                $status_text = 'Đã đăng ký (Đang học)';
            } elseif ( $status == 'completed' ) {
                $status_text = 'Đã hoàn thành';
            }
        }
    }

    // Render giao diện thông số
    ob_start();
    ?>
    <div style="background: #fdfdfd; border-left: 5px solid #ff6600; padding: 15px; margin: 20px 0; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h4 style="margin-top: 0; color: #333;">Thông tin chi tiết khóa học</h4>
        <ul style="list-style: none; padding-left: 0; font-size: 16px; margin-bottom: 0;">
            <li style="margin-bottom: 8px;">📖 <strong>Số bài học:</strong> <?php echo esc_html( $lesson_count ); ?> bài</li>
            <li style="margin-bottom: 8px;">⏱️ <strong>Thời gian dự kiến:</strong> <?php echo esc_html( $duration ); ?></li>
            <li>👤 <strong>Trạng thái của bạn:</strong> <span style="color: #ff6600; font-weight: bold;"><?php echo esc_html( $status_text ); ?></span></li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * ==========================================================
 * YÊU CẦU 3: Tùy biến Style (Custom CSS)
 * ==========================================================
 */
add_action( 'wp_head', 'lp_enhancer_custom_css' );
function lp_enhancer_custom_css() {
    // Chèn CSS vào thẻ <head> của giao diện
    ?>
    <style>
        /* Đổi màu các nút Ghi danh (Enroll) và Hoàn thành (Finish) sang màu Cam (#ff6600) */
        form.enroll-course .lp-button,
        .learn-press .button-enroll-course,
        .learn-press .button-finish-course,
        .lp-course-buttons .lp-button {
            background-color: #ff6600 !important;
            border-color: #cc5200 !important;
            color: #ffffff !important;
            border-radius: 5px !important;
            transition: background-color 0.3s ease !important;
        }
        
        /* Hiệu ứng khi hover chuột vào nút */
        form.enroll-course .lp-button:hover,
        .learn-press .button-enroll-course:hover,
        .learn-press .button-finish-course:hover,
        .lp-course-buttons .lp-button:hover {
            background-color: #cc5200 !important;
        }
    </style>
    <?php
}
<?php
/**
 * Plugin Name: Cookie Popups
 * Plugin URI: https://github.com/amuzcorp/cookie-popups
 * Description: 쿠키 팝업 관리 플러그인
 * Version: 1.0.0
 * Author: xiso
 * Text Domain: cookie-popups
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 상수 정의
define('COOKIE_POPUPS_VERSION', '1.0.0');
define('COOKIE_POPUPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOKIE_POPUPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoload
if (file_exists(COOKIE_POPUPS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once COOKIE_POPUPS_PLUGIN_DIR . 'vendor/autoload.php';
}

// 플러그인 초기화
function cookie_popups_init() {
    // Custom Post Type 등록
    register_post_type('cookie-popup', [
        'labels' => [
            'name' => __('쿠키 팝업', 'cookie-popups'),
            'singular_name' => __('쿠키 팝업', 'cookie-popups'),
            'add_new' => __('새 팝업 추가', 'cookie-popups'),
            'add_new_item' => __('새 팝업 추가', 'cookie-popups'),
            'edit_item' => __('팝업 수정', 'cookie-popups'),
            'view_item' => __('팝업 보기', 'cookie-popups'),
            'search_items' => __('팝업 검색', 'cookie-popups'),
            'not_found' => __('팝업을 찾을 수 없습니다.', 'cookie-popups'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-welcome-write-blog',
    ]);

    // 메타박스 등록
    add_action('add_meta_boxes', 'cookie_popups_add_meta_boxes');
    add_action('save_post_cookie-popup', 'cookie_popups_save_meta_boxes');

    // 프론트엔드 스크립트 및 스타일 등록
    add_action('wp_enqueue_scripts', 'cookie_popups_enqueue_scripts');

    // AJAX 핸들러 등록
    add_action('wp_ajax_cookie_popups_get_active_popup', 'cookie_popups_get_active_popup');
    add_action('wp_ajax_nopriv_cookie_popups_get_active_popup', 'cookie_popups_get_active_popup');
    add_action('wp_ajax_cookie_popups_dismiss', 'cookie_popups_dismiss_popup');
    add_action('wp_ajax_nopriv_cookie_popups_dismiss', 'cookie_popups_dismiss_popup');
}
add_action('init', 'cookie_popups_init');

// 메타박스 추가
function cookie_popups_add_meta_boxes() {
    add_meta_box(
        'cookie_popup_settings',
        __('팝업 설정', 'cookie-popups'),
        'cookie_popups_meta_box_callback',
        'cookie-popup',
        'normal',
        'high'
    );
}

// 메타박스 콜백
function cookie_popups_meta_box_callback($post) {
    wp_nonce_field('cookie_popups_meta_box', 'cookie_popups_meta_box_nonce');

    $start_date = get_post_meta($post->ID, '_cookie_popup_start_date', true);
    $end_date = get_post_meta($post->ID, '_cookie_popup_end_date', true);
    $reappear_days = get_post_meta($post->ID, '_cookie_popup_reappear_days', true);
    $target_page_id = get_post_meta($post->ID, '_cookie_popup_target_page', true);

    ?>
    <p>
        <label for="cookie_popup_start_date"><?php _e('시작일시:', 'cookie-popups'); ?></label>
        <input type="datetime-local" id="cookie_popup_start_date" name="cookie_popup_start_date" value="<?php echo esc_attr($start_date); ?>">
    </p>
    <p>
        <label for="cookie_popup_end_date"><?php _e('종료일시:', 'cookie-popups'); ?></label>
        <input type="datetime-local" id="cookie_popup_end_date" name="cookie_popup_end_date" value="<?php echo esc_attr($end_date); ?>">
    </p>
    <p>
        <label for="cookie_popup_reappear_days"><?php _e('재활성화 일자:', 'cookie-popups'); ?></label>
        <input type="number" id="cookie_popup_reappear_days" name="cookie_popup_reappear_days" value="<?php echo esc_attr($reappear_days); ?>">
    </p>
    <p>
        <label for="cookie_popup_target_page"><?php _e('표시할 페이지:', 'cookie-popups'); ?></label>
        <?php
        wp_dropdown_pages([
            'name' => 'cookie_popup_target_page',
            'id' => 'cookie_popup_target_page',
            'selected' => $target_page_id,
            'show_option_none' => __('페이지 선택', 'cookie-popups'),
        ]);
        ?>
    </p>
    <?php
}

// 메타박스 저장
function cookie_popups_save_meta_boxes($post_id) {
    if (!isset($_POST['cookie_popups_meta_box_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['cookie_popups_meta_box_nonce'], 'cookie_popups_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'cookie_popup_start_date',
        'cookie_popup_end_date',
        'cookie_popup_reappear_days',
        'cookie_popup_target_page',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}

// 프론트엔드 스크립트 및 스타일 등록
function cookie_popups_enqueue_scripts() {
    wp_enqueue_style(
        'cookie-popups',
        COOKIE_POPUPS_PLUGIN_URL . 'assets/css/cookie-popups.css',
        [],
        COOKIE_POPUPS_VERSION
    );

    wp_enqueue_script(
        'cookie-popups',
        COOKIE_POPUPS_PLUGIN_URL . 'assets/js/cookie-popups.js',
        ['jquery'],
        COOKIE_POPUPS_VERSION,
        true
    );

    wp_localize_script('cookie-popups', 'cookiePopupsData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cookie_popups_nonce'),
        'debug' => WP_DEBUG
    ]);
}

// AJAX 핸들러 함수들
function cookie_popups_get_active_popup() {
    error_log('AJAX request received');
    
    if (!check_ajax_referer('cookie_popups_nonce', 'nonce', false)) {
        error_log('Nonce verification failed');
        wp_send_json_error(['message' => 'Nonce verification failed']);
        return;
    }

    $current_page_id = get_the_ID();
    $current_time = current_time('Y-m-d\TH:i');

    error_log('Current Page ID: ' . $current_page_id);
    error_log('Current Time: ' . $current_time);

    // 먼저 현재 페이지에 대한 팝업이 있는지 확인
    $args = [
        'post_type' => 'cookie-popup',
        'posts_per_page' => 1,
//        'meta_query' => [
//            [
//                'key' => '_cookie_popup_target_page',
//                'value' => $current_page_id
//            ]
//        ]
    ];

    error_log('Query Args: ' . print_r($args, true));

    $query = new WP_Query($args);
    error_log('Query Results: ' . print_r($query->request, true));
    error_log('Found Posts: ' . $query->found_posts);

    if ($query->have_posts()) {
        $popup = $query->posts[0];
        error_log('Found popup: ' . $popup->ID);

        // 시작일시와 종료일시 확인
        $start_date = get_post_meta($popup->ID, '_cookie_popup_start_date', true);
        $end_date = get_post_meta($popup->ID, '_cookie_popup_end_date', true);

        error_log('Start date: ' . $start_date);
        error_log('End date: ' . $end_date);

        // 날짜 형식 변환
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $current_timestamp = strtotime($current_time);
        error_log('Timestamps: ' . print_r([
            'start' => $start_timestamp,
            'end' => $end_timestamp,
            'current' => $current_timestamp
        ], true));

        if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
            // 쿠키 체크
            if (isset($_COOKIE['cookie_popup_' . $popup->ID])) {
                error_log('Popup dismissed by cookie: ' . $popup->ID);
                wp_send_json_error(['message' => 'Popup dismissed by cookie']);
                return;
            }

            $reappear_days = get_post_meta($popup->ID, '_cookie_popup_reappear_days', true);
            error_log('Reappear days: ' . $reappear_days);

            wp_send_json_success([
                'popup' => [
                    'id' => $popup->ID,
                    'title' => $popup->post_title,
                    'content' => apply_filters('the_content', $popup->post_content),
                    'reappear_days' => $reappear_days
                ]
            ]);
        } else {
            error_log('Popup not in active time range');
            wp_send_json_error([
                    'message' => 'Popup not in active time range',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'current_date' => $current_time,
            ]);
        }
    } else {
        error_log('No popup found for current page');
        wp_send_json_error(['message' => 'No popup found for current page']);
    }
}

function cookie_popups_dismiss_popup() {
    check_ajax_referer('cookie_popups_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $days = isset($_POST['days']) ? intval($_POST['days']) : 0;

    if ($post_id && $days) {
        $expires = time() + ($days * 24 * 60 * 60);
        setcookie('cookie_popup_' . $post_id, '1', $expires, '/');
        wp_send_json_success();
    }

    wp_send_json_error();
} 
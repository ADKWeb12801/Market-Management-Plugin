<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GFFM_Pro_Attendance {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('rest_api_init', function(){
            register_rest_route('gffm/v1','/checkin',array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'rest_checkin'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'token' => array(
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Signed token for vendor/date check-in.'
                    )
                )
            ));
        });
    }
    public static function menu() {
        add_submenu_page('gffm','Attendance (Pro)','Attendance (Pro)','gffm_manage','gffm-pro-attendance',array(__CLASS__,'render'));
    }
    public static function render() {
        if ( ! current_user_can('gffm_manage') ) { wp_die('No permission'); }
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : wp_date('Y-m-d');
        echo '<div class="wrap"><h1>Attendance (Pro) - '.esc_html($date).'</h1><p>QR codes would be generated here.</p></div>';
    }
    public static function rest_checkin($request) {
        $token = sanitize_text_field($request['token']);
        return array('ok'=>true,'token'=>$token,'checked_in'=>current_time('mysql'));
    }
}

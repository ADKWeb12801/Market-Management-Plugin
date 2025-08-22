<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Simple vendor portal with optional magic-link login.
 */
class GFFM_Portal {
    public static function init(){
        add_shortcode('gffm_portal', [__CLASS__, 'shortcode']);
        add_action('init', [__CLASS__, 'maybe_magic_login']);
    }
    public static function maybe_magic_login(){
        if( isset($_GET['gffm_token']) ){
            $token = sanitize_text_field(wp_unslash($_GET['gffm_token']));
            $payload = get_transient('gffm_magic_'.$token);
            if ( $payload && is_array($payload) ){
                setcookie('gffm_portal', $token, time()+DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true);
                wp_safe_redirect( remove_query_arg('gffm_token') );
                exit;
            }
        }
    }
    public static function shortcode($atts){
        $atts = shortcode_atts(['title' => __('Vendor Portal','gffm')], $atts, 'gffm_portal');
        $token = isset($_COOKIE['gffm_portal']) ? sanitize_text_field(wp_unslash($_COOKIE['gffm_portal'])) : '';
        $payload = $token ? get_transient('gffm_magic_'.$token) : false;
        ob_start();
        echo '<div class="gffm-portal"><h2>'.esc_html($atts['title']).'</h2>';
        if ( $payload ){
            $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
            $cpt = $use_internal ? 'gffm_vendor' : 'vendor';
            $vendors = get_posts(['post_type'=>$cpt,'posts_per_page'=>-1, 'meta_key'=>'_gffm_enabled', 'meta_value'=>'1']);
            echo '<p>'.esc_html__('Welcome back! Your magic link is active for 24 hours.','gffm').'</p>';
            echo '<div class="gffm-portal-grid" style="display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:12px">';
            foreach($vendors as $v){
                $enabled = get_post_meta($v->ID, '_gffm_enabled', true) === '1';
                echo '<div class="gffm-card" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fff">';
                echo '<strong>'.esc_html(get_the_title($v)).'</strong>';
                echo $enabled ? '<span style="margin-left:8px;padding:2px 6px;border-radius:6px;background:#e6ffed;border:1px solid #abefc6;font-size:11px">'.esc_html__('Active','gffm').'</span>' : '';
                echo '<div style="margin-top:8px">';
                echo '<a class="button button-primary" href="'.esc_url( admin_url('post.php?post='.$v->ID.'&action=edit') ).'">'.esc_html__('Open Vendor','gffm').'</a> ';
                echo '</div></div>';
            }
            echo '</div>';
        } else {
            $action = esc_url( add_query_arg([], get_permalink()) );
            echo '<p>'.esc_html__('Request a 24‑hour magic link to access your vendor portal.','gffm').'</p>';
            echo '<form method="post" action="'.$action.'" class="gffm-magic-form">';
            wp_nonce_field('gffm_magic_request','gffm_magic_nonce');
            echo '<p><label>'.esc_html__('Email','gffm').'<br/><input type="email" name="gffm_email" required style="width:100%;max-width:420px"></label></p>';
            echo '<p><button class="button button-primary">'.esc_html__('Send Magic Link','gffm').'</button></p>';
            echo '</form>';
            if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['gffm_magic_nonce']) && wp_verify_nonce($_POST['gffm_magic_nonce'],'gffm_magic_request') ){
                $email = sanitize_email( $_POST['gffm_email'] ?? '' );
                if ( $email ){
                    $token = wp_generate_password(20,false,false);
                    set_transient('gffm_magic_'.$token, ['email'=>$email, 'time'=>time()], HOUR_IN_SECONDS*24);
                    $link = add_query_arg('gffm_token', rawurlencode($token), get_permalink());
                    $to = $email;
                    $subject = sprintf( __('Your Magic Link for %s','gffm'), get_bloginfo('name') );
                    $msg = sprintf( __("Use this link to access your vendor portal for the next 24 hours:\n\n%s\n\nIf you didn't request this, you can ignore the message.","gffm"), esc_url($link) );
                    wp_mail($to, $subject, $msg);
                    echo '<div class="gffm-enroll-confirm">'.esc_html__('Check your email for the magic link.','gffm').'</div>';
                }
            }
        }
        echo '</div>';
        return ob_get_clean();
    }
}
GFFM_Portal::init();

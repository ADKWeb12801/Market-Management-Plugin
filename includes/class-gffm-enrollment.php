<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Enrollment {
    public static function shortcode($atts, $content=''){
        $atts = shortcode_atts([
            'redirect' => '',
        ], $atts, 'gffm_enroll');

        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['gffm_enroll_nonce']) && wp_verify_nonce($_POST['gffm_enroll_nonce'],'gffm_enroll')){
            $name  = sanitize_text_field($_POST['vendor_name'] ?? '');
            $email = sanitize_email($_POST['vendor_email'] ?? '');
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');

            // capacity check
            $max = absint(get_option('gffm_max_vendors', 0));
            $active_enabled = self::count_enabled_vendors();
            $to_waitlist = ($max > 0 && $active_enabled >= $max);

            $post_id = wp_insert_post([
                'post_type' => 'gffm_enrollment',
                'post_title' => $name ? $name : ('Enrollment '.current_time('mysql')),
                'post_status' => 'publish',
            ]);
            if($post_id){
                update_post_meta($post_id, '_email', $email);
                update_post_meta($post_id, '_notes', $notes);
                update_post_meta($post_id, '_status', $to_waitlist ? 'waitlist' : 'pending');

                // email
                $to = GFFM_Settings::notify_email();
                $subject = $to_waitlist ? __('New Waitlist Signup','gffm') : __('New Vendor Enrollment','gffm');
                $msg = sprintf("Name: %s\nEmail: %s\nNotes: %s\nStatus: %s", $name, $email, $notes, ($to_waitlist ? 'WAITLIST' : 'PENDING'));
                wp_mail($to, $subject, $msg);
            }

            if($atts['redirect']){
                wp_safe_redirect(esc_url_raw($atts['redirect']));
                exit;
            }
            return '<div class="gffm-enroll-confirm">' . esc_html__("Thanks! Your submission has been received.",'gffm') . '</div>';}

        ob_start(); ?>
        <form method="post" class="gffm-enroll-form">
            <?php wp_nonce_field('gffm_enroll','gffm_enroll_nonce'); ?>
            <p>
                <label><?php _e('Business Name','gffm'); ?><br/>
                <input type="text" name="vendor_name" required></label>
            </p>
            <p>
                <label><?php _e('Email','gffm'); ?><br/>
                <input type="email" name="vendor_email" required></label>
            </p>
            <p>
                <label><?php _e('Notes','gffm'); ?><br/>
                <textarea name="notes" rows="4"></textarea></label>
            </p>
            <p><button type="submit" class="button button-primary"><?php _e('Submit','gffm'); ?></button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    private static function count_enabled_vendors(){
        $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
        $cpt = $use_internal ? 'gffm_vendor' : 'vendor';
        $q = new WP_Query([
            'post_type' => $cpt,
            'posts_per_page' => 1,
            'meta_key' => '_gffm_enabled',
            'meta_value' => '1',
            'fields' => 'ids',
        ]);
        return (int)$q->found_posts;
    }
}

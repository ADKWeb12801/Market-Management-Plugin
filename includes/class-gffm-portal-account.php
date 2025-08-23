<?php
defined('ABSPATH') || exit;

class GFFM_Portal_Account {
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'meta_box']);
        add_action('admin_post_gffm_portal_account', [__CLASS__, 'handle']);
        add_action('admin_notices', [__CLASS__, 'notices']);
    }

    public static function vendor_cpt(): string {
        if ( class_exists('GFFM_Vendor_Link') ) {
            return GFFM_Vendor_Link::vendor_cpt();
        }
        return get_option('gffm_use_internal_vendors', 'no') === 'yes' ? 'gffm_vendor' : 'vendor';
    }

    public static function meta_box() {
        add_meta_box('gffm_portal_account', __('Portal Account','gffm'), [__CLASS__, 'render_meta'], self::vendor_cpt(), 'side');
    }

    public static function render_meta($post) {
        if ( ! current_user_can('gffm_manage') && ! current_user_can('manage_options') ) {
            return;
        }
        $linked = (int) get_post_meta($post->ID, '_gffm_linked_user', true);
        $user   = $linked ? get_userdata($linked) : false;
        $username = $user ? $user->user_login : '';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<p><label>'.esc_html__('Username','gffm').'<br/><input type="text" name="gffm_username" class="widefat" value="'.esc_attr($username).'"/></label></p>';
        echo '<p><label>'.esc_html__('Password','gffm').'<br/><input type="password" name="gffm_password" class="widefat"/></label></p>';
        echo '<input type="hidden" name="vendor_id" value="'.absint($post->ID).'"/>';
        echo '<input type="hidden" name="action" value="gffm_portal_account"/>';
        wp_nonce_field('gffm_portal_account','gffm_portal_account_nonce');
        echo '<p><button class="button" name="gffm_action" value="link">'.esc_html__('Create/Link Account','gffm').'</button></p>';
        if ( $user ) {
            echo '<p><button class="button" name="gffm_action" value="setpass">'.esc_html__('Set/Update Password','gffm').'</button></p>';
            echo '<p><button class="button" name="gffm_action" value="reset">'.esc_html__('Send Password Reset','gffm').'</button></p>';
            echo '<p><button class="button" name="gffm_action" value="revoke">'.esc_html__('Revoke Access','gffm').'</button></p>';
        }
        echo '</form>';
    }

    public static function handle() {
        if ( ! current_user_can('gffm_manage') && ! current_user_can('manage_options') ) {
            wp_die(__('You do not have permission.','gffm'));
        }
        if ( ! isset($_POST['gffm_portal_account_nonce']) || ! wp_verify_nonce($_POST['gffm_portal_account_nonce'], 'gffm_portal_account') ) {
            wp_die(__('Invalid nonce.','gffm'));
        }
        $vendor_id = isset($_POST['vendor_id']) ? absint($_POST['vendor_id']) : 0;
        $action = isset($_POST['gffm_action']) ? sanitize_text_field($_POST['gffm_action']) : '';
        $username = isset($_POST['gffm_username']) ? sanitize_user(wp_unslash($_POST['gffm_username']), true) : '';
        $password = isset($_POST['gffm_password']) ? wp_unslash($_POST['gffm_password']) : '';
        $linked = (int) get_post_meta($vendor_id, '_gffm_linked_user', true);
        $user = $linked ? get_userdata($linked) : false;

        if ( 'link' === $action ) {
            if ( ! $username ) {
                self::redirect('fail', $vendor_id);
            }
            $existing = get_user_by('login', $username);
            if ( $existing && ( ! $user || $existing->ID !== $user->ID ) ) {
                self::redirect('user_exists', $vendor_id);
            }
            if ( ! $existing ) {
                $email = get_post_meta($vendor_id, '_email', true);
                $pass = $password ? $password : wp_generate_password(12, true);
                $uid = wp_create_user($username, $pass, $email);
                if ( is_wp_error($uid) ) {
                    self::redirect('fail', $vendor_id);
                }
                $existing = get_user_by('id', $uid);
            } else {
                $existing->set_role('gffm_vendor');
            }
            update_user_meta($existing->ID, '_gffm_vendor_id', $vendor_id);
            update_post_meta($vendor_id, '_gffm_linked_user', $existing->ID);
            self::redirect('linked', $vendor_id);
        } elseif ( 'setpass' === $action && $user ) {
            if ( ! $password ) {
                self::redirect('fail', $vendor_id);
            }
            wp_update_user(['ID'=>$user->ID,'user_pass'=>$password]);
            self::redirect('pass', $vendor_id);
        } elseif ( 'reset' === $action && $user ) {
            retrieve_password($user->user_login);
            self::redirect('reset', $vendor_id);
        } elseif ( 'revoke' === $action && $user ) {
            delete_user_meta($user->ID, '_gffm_vendor_id');
            delete_post_meta($vendor_id, '_gffm_linked_user');
            $user->set_role('subscriber');
            self::redirect('revoked', $vendor_id);
        }
        self::redirect('fail', $vendor_id);
    }

    private static function redirect(string $flag, int $vendor_id = 0) {
        $target = wp_get_referer();
        if ( ! $target && $vendor_id ) {
            $target = get_edit_post_link($vendor_id, 'raw');
        }
        if ( ! $target ) {
            $target = admin_url('edit.php?post_type=' . self::vendor_cpt());
        }
        wp_safe_redirect( add_query_arg('gffm_portal_account', $flag, $target) );
        exit;
    }

    public static function notices() {
        if ( isset($_GET['gffm_portal_account']) ) {
            $msg = '';
            switch ( $_GET['gffm_portal_account'] ) {
                case 'linked':
                    $msg = __('Account linked.','gffm');
                    break;
                case 'reset':
                    $msg = __('Password reset sent.','gffm');
                    break;
                case 'pass':
                    $msg = __('Password updated.','gffm');
                    break;
                case 'revoked':
                    $msg = __('Access revoked.','gffm');
                    break;
                case 'user_exists':
                    $msg = __('Username already exists.','gffm');
                    break;
                default:
                    $msg = __('Operation failed.','gffm');
            }
            echo '<div class="notice notice-info"><p>'.esc_html($msg).'</p></div>';
        }
    }
}
GFFM_Portal_Account::init();

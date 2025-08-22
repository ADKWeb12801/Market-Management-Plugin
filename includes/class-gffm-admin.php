<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Admin {
    public static function render_assignment(){
        if( ! current_user_can('gffm_manage')) wp_die(__('You do not have permission.','gffm'));
        $nonce_action = 'gffm_assign_vendors';

        // Handle toggle requests
        if( isset($_GET['gffm_toggle_vendor']) ){
            $vid = absint($_GET['gffm_toggle_vendor']);
            if( check_admin_referer('gffm_toggle_vendor_'.$vid) ){
                $enabled = get_post_meta($vid, '_gffm_enabled', true) === '1';
                if( $enabled ){
                    delete_post_meta($vid, '_gffm_enabled');
                    $msg = 'disabled';
                } else {
                    update_post_meta($vid, '_gffm_enabled', '1');
                    $msg = 'enabled';
                }
                wp_safe_redirect( add_query_arg('gffm_msg', $msg, admin_url('admin.php?page=gffm_assignment')) );
                exit;
            }
        }

        // Handle bulk enable
        if( isset($_POST['gffm_assign_submit']) && check_admin_referer($nonce_action) ){
            $ids = isset($_POST['gffm_vendor_ids']) ? array_map('absint', (array)$_POST['gffm_vendor_ids']) : [];
            foreach($ids as $vid){
                update_post_meta($vid, '_gffm_enabled', '1');
            }
            echo '<div class="updated"><p>'.sprintf(esc_html__('%d vendors enabled for GFFM.','gffm'), count($ids)).'</p></div>';
        }

        if( isset($_GET['gffm_msg']) ){
            $msg = sanitize_key($_GET['gffm_msg']);
            if( $msg === 'enabled' ){
                echo '<div class="updated"><p>'.esc_html__('Vendor enabled for GFFM.','gffm').'</p></div>';
            } elseif( $msg === 'disabled' ){
                echo '<div class="updated"><p>'.esc_html__('Vendor disabled for GFFM.','gffm').'</p></div>';
            }
        }

        // detect CPT to read from
        $use_internal = get_option('gffm_use_internal_vendors','no') === 'yes';
        $cpt = $use_internal ? 'gffm_vendor' : 'vendor';

        // query vendors
        $vendors = get_posts([
            'post_type' => $cpt,
            'posts_per_page' => 200,
            'post_status' => ['publish','draft','pending','private'],
        ]);

        echo '<div class="wrap gffm-admin"><h1>'.esc_html__('Vendor Assignment','gffm').'</h1>';
        echo '<p>'.esc_html__('Check vendors to enable them for Market Manager features. This will mark the vendor with meta _gffm_enabled=1.','gffm').'</p>';
        echo '<form method="post">';
        wp_nonce_field($nonce_action);
        echo '<p><label><input type="checkbox" data-gffm-select-all="1"/> '.esc_html__('Select All','gffm').'</label></p>';
        echo '<table class="widefat striped"><thead><tr><th></th><th>'.esc_html__('Vendor','gffm').'</th><th>'.esc_html__('Status','gffm').'</th><th>'.esc_html__('Assigned?','gffm').'</th></tr></thead><tbody>';
        foreach($vendors as $v){
            $enabled = get_post_meta($v->ID, '_gffm_enabled', true) === '1';
            $toggle_url = wp_nonce_url( add_query_arg('gffm_toggle_vendor', $v->ID, admin_url('admin.php?page=gffm_assignment')), 'gffm_toggle_vendor_'.$v->ID );
            echo '<tr>';
            echo '<td><input type="checkbox" name="gffm_vendor_ids[]" value="'.esc_attr($v->ID).'" data-gffm-row="1"/></td>';
            echo '<td><a href="'.get_edit_post_link($v->ID).'">'.esc_html(get_the_title($v->ID)).'</a></td>';
            echo '<td>'.esc_html($v->post_status).'</td>';
            echo '<td>';
            if( $enabled ){
                echo '<span class="dashicons dashicons-yes"></span> <a href="'.esc_url($toggle_url).'">'.esc_html__('Disable','gffm').'</a>';
            } else {
                echo '&mdash; <a href="'.esc_url($toggle_url).'">'.esc_html__('Enable','gffm').'</a>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        submit_button(__('Enable Selected Vendors','gffm'));
        echo '</form></div>';
    }
}

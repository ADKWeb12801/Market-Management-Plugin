<?php
defined('ABSPATH') || exit;

class GFFM_Vendor_Link {
  public static function init() {
    add_action('add_meta_boxes', [__CLASS__, 'meta_box']);
    add_action('save_post', [__CLASS__, 'save_meta']);
  }

  public static function vendor_cpt(): string {
    return get_option('gffm_use_internal_vendors', 'no') === 'yes' ? 'gffm_vendor' : 'vendor';
  }

  public static function meta_box() {
    add_meta_box('gffm_vendor_portal', __('Vendor Portal Access','gffm'), [__CLASS__,'render_meta'], self::vendor_cpt(), 'side');
  }

  public static function render_meta($post) {
    $enabled = get_post_meta($post->ID, '_gffm_portal_enabled', true) === '1';
    $linked  = (int) get_post_meta($post->ID, '_gffm_linked_user', true);
    $user    = $linked ? get_userdata($linked) : false;
    wp_nonce_field('gffm_vendor_portal','gffm_vendor_portal_nonce');
    echo '<p><label><input type="checkbox" name="gffm_portal_enabled" value="1" '.checked($enabled,true,false).' /> '.esc_html__('Enable portal access','gffm').'</label></p>';
    echo '<p>'.esc_html__('Linked user:','gffm').' '.($user ? esc_html($user->user_email) : '&mdash;').'</p>';
  }

  public static function save_meta($post_id) {
    if ( ! isset($_POST['gffm_vendor_portal_nonce']) || ! wp_verify_nonce($_POST['gffm_vendor_portal_nonce'],'gffm_vendor_portal') ) {
      return;
    }
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;
    $enabled = isset($_POST['gffm_portal_enabled']) ? '1' : '';
    update_post_meta($post_id, '_gffm_portal_enabled', $enabled);
  }

}
GFFM_Vendor_Link::init();

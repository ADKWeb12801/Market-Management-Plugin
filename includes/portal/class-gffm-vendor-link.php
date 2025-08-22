<?php
defined('ABSPATH') || exit;

class GFFM_Vendor_Link {
  public static function init() {
    add_action('add_meta_boxes', [__CLASS__, 'meta_box']);
    add_action('save_post', [__CLASS__, 'save_meta']);
    add_action('admin_post_gffm_invite_vendor', [__CLASS__, 'handle_invite']);
    add_action('admin_notices', [__CLASS__, 'admin_notices']);
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
    echo '<hr/>'; 
    echo '<p><strong>'.esc_html__('Send Magic Link','gffm').'</strong></p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    echo '<input type="hidden" name="action" value="gffm_invite_vendor"/>';
    echo '<input type="hidden" name="vendor_id" value="'.absint($post->ID).'"/>';
    wp_nonce_field('gffm_invite_vendor','gffm_invite_nonce');
    echo '<p><input type="email" required name="gffm_invite_email" class="widefat" placeholder="'.esc_attr__('vendor@example.com','gffm').'"/></p>';
    echo '<p><button class="button">'.esc_html__('Send Invite','gffm').'</button></p>';
    echo '</form>';
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

  public static function handle_invite() {
    if ( ! current_user_can('manage_options') && ! current_user_can('gffm_manage') ) {
      wp_die(__('You do not have permission.','gffm'));
    }
    if ( ! isset($_POST['gffm_invite_nonce']) || ! wp_verify_nonce($_POST['gffm_invite_nonce'],'gffm_invite_vendor') ) {
      wp_die(__('Invalid nonce.','gffm'));
    }
    $vendor_id = isset($_POST['vendor_id']) ? absint($_POST['vendor_id']) : 0;
    $email = isset($_POST['gffm_invite_email']) ? sanitize_email(wp_unslash($_POST['gffm_invite_email'])) : '';
    if ( ! $vendor_id || ! $email ) {
      wp_safe_redirect( wp_get_referer() );
      exit;
    }
    $user = get_user_by('email', $email);
    if ( ! $user ) {
      $pwd = wp_generate_password(12, false);
      $uid = wp_create_user($email, $pwd, $email);
      if ( is_wp_error($uid) ) {
        wp_safe_redirect( add_query_arg('gffm_invite','fail', wp_get_referer()) );
        exit;
      }
      $user = get_user_by('id', $uid);
    }
    $user_id = $user->ID;
    if ( get_option('gffm_append_vendor_role','no') === 'yes' ) {
      $user->add_role('gffm_vendor');
    } else {
      $user->set_role('gffm_vendor');
    }
    update_user_meta($user_id, '_gffm_vendor_id', $vendor_id);
    update_post_meta($vendor_id, '_gffm_linked_user', $user_id);
    update_post_meta($vendor_id, '_gffm_portal_enabled', '1');

    $token = GFFM_Magic::issue_token($user_id);
    $portal_url = home_url('/vendor-portal/') . '?gffm_magic=' . rawurlencode($token);
    $subject = get_option('gffm_invite_subject', 'Your Vendor Portal Link – {site_name}');
    $body    = get_option('gffm_invite_body', "Hello {vendor_title},\n\nYour one-click sign-in link:\n{portal_url}\n\nThis link will expire in 24 hours.\n– {site_name}");
    $vendor_title = get_the_title($vendor_id);
    $repl = [
      '{site_name}'    => get_bloginfo('name'),
      '{vendor_title}' => $vendor_title,
      '{portal_url}'   => $portal_url,
    ];
    $subject = strtr($subject, $repl);
    $body    = strtr($body, $repl);
    wp_mail($email, $subject, $body);

    wp_safe_redirect( add_query_arg('gffm_invite','sent', wp_get_referer()) );
    exit;
  }

  public static function admin_notices() {
    if ( isset($_GET['gffm_invite']) && $_GET['gffm_invite'] === 'sent' ) {
      echo '<div class="notice notice-success"><p>'.esc_html__('Invitation email sent.','gffm').'</p></div>';
    } elseif ( isset($_GET['gffm_invite']) && $_GET['gffm_invite'] === 'fail' ) {
      echo '<div class="notice notice-error"><p>'.esc_html__('Failed to create or retrieve user.','gffm').'</p></div>';
    }
  }
}
GFFM_Vendor_Link::init();

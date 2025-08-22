<?php
defined('ABSPATH') || exit;

class GFFM_Portal {
  public static function init() {
    add_shortcode('gffm_portal', [__CLASS__, 'shortcode']);
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
  }

  public static function default_mapping(): string {
    return json_encode([
      'vendor_phone' => ['label'=>'Phone','type'=>'text'],
      'vendor_description' => ['label'=>'About','type'=>'textarea'],
      'vendor_website' => ['label'=>'Website','type'=>'url'],
      'vendor_facebook' => ['label'=>'Facebook','type'=>'url'],
      'vendor_instagram' => ['label'=>'Instagram','type'=>'url'],
      'vendor_logo' => ['label'=>'Logo','type'=>'image'],
    ], JSON_PRETTY_PRINT);
  }

  public static function menu() {
    add_submenu_page('gffm', __('Vendor Portal','gffm'), __('Vendor Portal','gffm'), 'gffm_manage', 'gffm_vendor_portal', [__CLASS__,'render_settings']);
  }

  public static function register_settings() {
    register_setting('gffm_vendor_portal', 'gffm_week_start_day', [
      'type' => 'string',
      'sanitize_callback' => function($v){ $valid=['monday','tuesday','wednesday','thursday','friday','saturday','sunday']; $v=strtolower($v); return in_array($v,$valid,true)?$v:'saturday'; },
      'default' => 'saturday',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_profile_map_json', [
      'type' => 'string',
      'sanitize_callback' => function($v){ return wp_kses_post($v); },
      'default' => self::default_mapping(),
    ]);
    register_setting('gffm_vendor_portal', 'gffm_invite_subject', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'Your Vendor Portal Link – {site_name}',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_invite_body', [
      'type'=>'string',
      'sanitize_callback'=>'wp_kses_post',
      'default'=>"Hello {vendor_title},\n\nYour one-click sign-in link:\n{portal_url}\n\nThis link will expire in 24 hours.\n– {site_name}",
    ]);
  }

  public static function render_settings() {
    if ( ! current_user_can('gffm_manage') ) wp_die(__('You do not have permission.','gffm'));
    echo '<div class="wrap"><h1>'.esc_html__('Vendor Portal','gffm').'</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('gffm_vendor_portal');
    echo '<table class="form-table" role="presentation">';
    $day = get_option('gffm_week_start_day','saturday');
    echo '<tr><th><label for="gffm_week_start_day">'.esc_html__('Week Starts On','gffm').'</label></th><td><select name="gffm_week_start_day" id="gffm_week_start_day">';
    foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $d){
      echo '<option value="'.esc_attr($d).'" '.selected($day,$d,false).'>'.esc_html(ucfirst($d)).'</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th><label for="gffm_profile_map_json">'.esc_html__('Profile Field Mapping (JSON)','gffm').'</label></th>';
    echo '<td><textarea name="gffm_profile_map_json" id="gffm_profile_map_json" rows="10" cols="50" class="large-text code">'.esc_textarea(get_option('gffm_profile_map_json', self::default_mapping())).'</textarea></td></tr>';
    echo '<tr><th><label for="gffm_invite_subject">'.esc_html__('Invite Email Subject','gffm').'</label></th>';
    echo '<td><input type="text" name="gffm_invite_subject" id="gffm_invite_subject" class="regular-text" value="'.esc_attr(get_option('gffm_invite_subject','Your Vendor Portal Link – {site_name}')).'"/></td></tr>';
    echo '<tr><th><label for="gffm_invite_body">'.esc_html__('Invite Email Body','gffm').'</label></th>';
    echo '<td><textarea name="gffm_invite_body" id="gffm_invite_body" rows="6" cols="50" class="large-text code">'.esc_textarea(get_option('gffm_invite_body',"Hello {vendor_title},\n\nYour one-click sign-in link:\n{portal_url}\n\nThis link will expire in 24 hours.\n– {site_name}")).'</textarea><p class="description">'.esc_html__('Placeholders: {site_name}, {vendor_title}, {portal_url}','gffm').'</p></td></tr>';
    echo '</table>';
    submit_button();
    echo '</form></div>';
  }

  public static function shortcode($atts, $content = '') {
    if ( ! is_user_logged_in() ) {
      return '<p>'.esc_html__('Please check your email for a magic link to access the vendor portal.','gffm').'</p>';
    }
    $vendor_id = GFFM_Util::current_user_vendor_id();
    if ( ! $vendor_id ) {
      return '<p>'.esc_html__('Your account is not linked to a vendor.','gffm').'</p>';
    }
    if ( ! GFFM_Util::can_edit_vendor($vendor_id) ) {
      return '<p>'.esc_html__('You do not have permission to access this vendor.','gffm').'</p>';
    }
    wp_enqueue_style('gffm-portal', GFFM_URL.'assets/portal.css', [], GFFM_VERSION);
    wp_enqueue_script('gffm-portal', GFFM_URL.'assets/portal.js', ['jquery'], GFFM_VERSION, true);
    wp_enqueue_media();

    $out = '';
    $notice = '';
    if ( isset($_POST['gffm_profile_nonce']) && wp_verify_nonce($_POST['gffm_profile_nonce'],'gffm_profile_save') ) {
      $notice = self::handle_profile_save($vendor_id);
    }
    if ( isset($_POST['gffm_highlight_nonce']) && wp_verify_nonce($_POST['gffm_highlight_nonce'],'gffm_highlight_save') ) {
      $notice = self::handle_highlight_save($vendor_id);
    }

    if ( $notice ) {
      $out .= '<div class="gffm-notice">'.esc_html($notice).'</div>';
    }

    $map = json_decode(get_option('gffm_profile_map_json', self::default_mapping()), true);
    if ( ! is_array($map) ) $map = [];

    $out .= '<div class="gffm-portal-tabs">';
    $out .= '<ul class="gffm-tab-nav"><li class="active" data-tab="profile">'.esc_html__('Profile','gffm').'</li><li data-tab="highlight">'.esc_html__('Weekly Highlight','gffm').'</li></ul>';
    $out .= '<div class="gffm-tab-content active" id="gffm-tab-profile">';
    $out .= '<form method="post">';
    wp_nonce_field('gffm_profile_save','gffm_profile_nonce');
    foreach ($map as $key => $field) {
      $type = $field['type'] ?? 'text';
      $label = $field['label'] ?? $key;
      $value = get_post_meta($vendor_id, $key, true);
      $out .= '<p><label>'.esc_html($label).'<br/>';
      if ( 'textarea' === $type ) {
        $out .= '<textarea name="pf['.esc_attr($key).']" class="widefat">'.esc_textarea($value).'</textarea>';
      } elseif ( 'image' === $type ) {
        $img = $value ? wp_get_attachment_image($value, 'thumbnail') : '';
        $out .= '<input type="hidden" name="pf['.esc_attr($key).']" value="'.esc_attr($value).'" class="gffm-img-field" />';
        $out .= '<span class="gffm-img-preview">'.$img.'</span><button type="button" class="button gffm-img-btn" data-target="pf['.esc_attr($key).']">'.esc_html__('Choose Image','gffm').'</button>';
      } else {
        $out .= '<input type="'.esc_attr($type).'" name="pf['.esc_attr($key).']" value="'.esc_attr($value).'" class="widefat"/>';
      }
      $out .= '</label></p>';
    }
    $out .= '<p><button class="button button-primary">'.esc_html__('Save Profile','gffm').'</button></p>';
    $out .= '</form></div>'; // profile tab

    // Highlight tab
    $week_key = GFFM_Util::week_key();
    $current = get_posts([
      'post_type' => 'gffm_highlight',
      'author' => get_current_user_id(),
      'meta_key' => '_gffm_week_key',
      'meta_value' => $week_key,
      'post_status' => ['draft','publish'],
      'posts_per_page' => 1,
    ]);
    $highlight = $current ? $current[0] : null;
    $title = $highlight ? $highlight->post_title : '';
    $content = $highlight ? $highlight->post_content : '';
    $image_id = $highlight ? get_post_thumbnail_id($highlight->ID) : 0;

    $out .= '<div class="gffm-tab-content" id="gffm-tab-highlight">';
    $out .= '<form method="post">';
    wp_nonce_field('gffm_highlight_save','gffm_highlight_nonce');
    $out .= '<p><label>'.esc_html__('Headline','gffm').'<br/><input type="text" name="hl_title" class="widefat" value="'.esc_attr($title).'"/></label></p>';
    $out .= '<p><label>'.esc_html__('Details','gffm').'<br/><textarea name="hl_content" class="widefat">'.esc_textarea($content).'</textarea></label></p>';
    $out .= '<p><label>'.esc_html__('Image','gffm').'<br/><input type="hidden" name="hl_image" value="'.esc_attr($image_id).'" class="gffm-img-field" /><span class="gffm-img-preview">'.($image_id ? wp_get_attachment_image($image_id,'thumbnail') : '').'</span><button type="button" class="button gffm-img-btn" data-target="hl_image">'.esc_html__('Choose Image','gffm').'</button></label></p>';
    $out .= '<p><button class="button button-primary">'.esc_html__('Save Highlight','gffm').'</button></p>';
    $out .= '</form></div>'; // highlight tab

    $out .= '</div>'; // tabs wrapper
    return $out;
  }

  private static function handle_profile_save(int $vendor_id): string {
    if ( ! GFFM_Util::can_edit_vendor($vendor_id) ) {
      return __('Permission denied.','gffm');
    }
    $map = json_decode(get_option('gffm_profile_map_json', self::default_mapping()), true);
    $fields = isset($_POST['pf']) && is_array($_POST['pf']) ? $_POST['pf'] : [];
    foreach ($fields as $key => $val) {
      if ( ! isset($map[$key]) ) continue;
      $type = $map[$key]['type'] ?? 'text';
      if ( 'textarea' === $type ) {
        $clean = wp_kses_post($val);
      } elseif ( 'email' === $type ) {
        $clean = sanitize_email($val);
      } elseif ( 'url' === $type ) {
        $clean = esc_url_raw($val);
      } elseif ( 'image' === $type ) {
        $clean = absint($val);
      } else {
        $clean = sanitize_text_field($val);
      }
      update_post_meta($vendor_id, $key, $clean);
    }
    return __('Profile saved.','gffm');
  }

  private static function handle_highlight_save(int $vendor_id): string {
    if ( ! GFFM_Util::can_edit_vendor($vendor_id) ) {
      return __('Permission denied.','gffm');
    }
    $title = isset($_POST['hl_title']) ? sanitize_text_field(wp_unslash($_POST['hl_title'])) : '';
    $content = isset($_POST['hl_content']) ? wp_kses_post(wp_unslash($_POST['hl_content'])) : '';
    $image = isset($_POST['hl_image']) ? absint($_POST['hl_image']) : 0;
    $week_key = GFFM_Util::week_key();
    $existing = get_posts([
      'post_type' => 'gffm_highlight',
      'author' => get_current_user_id(),
      'meta_key' => '_gffm_week_key',
      'meta_value' => $week_key,
      'post_status' => ['draft','publish'],
      'posts_per_page' => 1,
    ]);
    if ( $existing ) {
      $post_id = $existing[0]->ID;
      wp_update_post(['ID'=>$post_id,'post_title'=>$title,'post_content'=>$content]);
    } else {
      $post_id = wp_insert_post([
        'post_type'=>'gffm_highlight',
        'post_title'=>$title,
        'post_content'=>$content,
        'post_status'=>'publish',
        'post_author'=>get_current_user_id(),
      ]);
    }
    if ( ! is_wp_error($post_id) ) {
      update_post_meta($post_id, '_gffm_week_key', $week_key);
      if ( $image ) {
        set_post_thumbnail($post_id, $image);
      }
    }
    return __('Highlight saved.','gffm');
  }
}
GFFM_Portal::init();

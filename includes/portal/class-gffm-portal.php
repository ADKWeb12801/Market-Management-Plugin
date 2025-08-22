<?php
defined('ABSPATH') || exit;

if ( ! class_exists( 'GFFM_Portal' ) ) {
class GFFM_Portal {
  public static function init() {
    add_shortcode('gffm_portal', [__CLASS__, 'shortcode']);
    add_action('admin_menu', [__CLASS__, 'menu']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
    add_action('wp_ajax_gffm_profile_save', [__CLASS__, 'ajax_profile_save']);
    add_action('wp_ajax_gffm_highlight_save', [__CLASS__, 'ajax_highlight_save']);
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
    register_setting('gffm_vendor_portal', 'gffm_auth_enabled_methods', [
      'type' => 'array',
      'sanitize_callback' => function($v){
        $valid = ['password','magic','google','facebook'];
        if (!is_array($v)) $v = [];
        return array_values(array_intersect($valid, $v));
      },
      'default' => ['password','magic','google','facebook'],
    ]);
    register_setting('gffm_vendor_portal', 'gffm_auth_google_client_id', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_auth_google_client_secret', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_auth_facebook_app_id', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_auth_facebook_app_secret', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
    ]);
    register_setting('gffm_vendor_portal', 'gffm_auth_login_branding', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
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
    echo '<h2>'.esc_html__('Authentication','gffm').'</h2>';
    $methods = get_option('gffm_auth_enabled_methods', ['password','magic','google','facebook']);
    echo '<table class="form-table" role="presentation">';
    echo '<tr><th>'.esc_html__('Enabled Methods','gffm').'</th><td>';
    $opts = ['password'=>__('Password','gffm'),'magic'=>__('Magic','gffm'),'google'=>__('Google','gffm'),'facebook'=>__('Facebook','gffm')];
    foreach($opts as $key=>$label){ echo '<label><input type="checkbox" name="gffm_auth_enabled_methods[]" value="'.esc_attr($key).'" '.checked(in_array($key,$methods,true),true,false).'/> '.esc_html($label).'</label><br/>'; }
    echo '</td></tr>';
    if (in_array('google',$methods,true)) {
      echo '<tr><th><label for="gffm_auth_google_client_id">'.esc_html__('Google Client ID','gffm').'</label></th><td><input type="text" id="gffm_auth_google_client_id" name="gffm_auth_google_client_id" class="regular-text" value="'.esc_attr(get_option('gffm_auth_google_client_id','')).'"/></td></tr>';
      echo '<tr><th><label for="gffm_auth_google_client_secret">'.esc_html__('Google Client Secret','gffm').'</label></th><td><input type="text" id="gffm_auth_google_client_secret" name="gffm_auth_google_client_secret" class="regular-text" value="'.esc_attr(get_option('gffm_auth_google_client_secret','')).'"/></td></tr>';
    }
    if (in_array('facebook',$methods,true)) {
      echo '<tr><th><label for="gffm_auth_facebook_app_id">'.esc_html__('Facebook App ID','gffm').'</label></th><td><input type="text" id="gffm_auth_facebook_app_id" name="gffm_auth_facebook_app_id" class="regular-text" value="'.esc_attr(get_option('gffm_auth_facebook_app_id','')).'"/></td></tr>';
      echo '<tr><th><label for="gffm_auth_facebook_app_secret">'.esc_html__('Facebook App Secret','gffm').'</label></th><td><input type="text" id="gffm_auth_facebook_app_secret" name="gffm_auth_facebook_app_secret" class="regular-text" value="'.esc_attr(get_option('gffm_auth_facebook_app_secret','')).'"/></td></tr>';
    }
    echo '<tr><th>'.esc_html__('Redirect URI','gffm').'</th><td><input type="text" readonly class="regular-text" value="'.esc_attr(home_url('/vendor-portal/')).'"/></td></tr>';
    echo '<tr><th><label for="gffm_auth_login_branding">'.esc_html__('Login Branding','gffm').'</label></th><td><input type="text" id="gffm_auth_login_branding" name="gffm_auth_login_branding" class="regular-text" value="'.esc_attr(get_option('gffm_auth_login_branding','')).'"/></td></tr>';
    echo '</table>';
    submit_button();
    echo '</form></div>';
  }

  public static function shortcode($atts, $content = '') {
    if ( ! is_user_logged_in() ) {
      wp_enqueue_style('gffm-portal', GFFM_URL.'assets/portal.css', [], GFFM_VERSION);
      wp_enqueue_script('gffm-portal', GFFM_URL.'assets/portal.js', ['jquery'], GFFM_VERSION, true);
      wp_localize_script('gffm-portal', 'gffmPortal', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'i18n' => [
          'show' => __('Show password','gffm'),
          'hide' => __('Hide password','gffm'),
          'select' => __('Select Image','gffm'),
        ],
      ]);
      $methods = get_option('gffm_auth_enabled_methods', ['password','magic','google','facebook']);
      $out = '<div class="gffm-login-container">';
      $branding = get_option('gffm_auth_login_branding', '');
      if ( $branding ) {
        $out .= '<h2>'.esc_html($branding).'</h2>';
      }
      $error = '';
      if ( isset($_POST['gffm_login_nonce']) && wp_verify_nonce($_POST['gffm_login_nonce'], 'gffm_login') ) {
        $creds = [
          'user_login'    => isset($_POST['gffm_username']) ? sanitize_user(wp_unslash($_POST['gffm_username'])) : '',
          'user_password' => isset($_POST['gffm_password']) ? $_POST['gffm_password'] : '',
          'remember'      => true,
        ];
        $user = wp_signon($creds, false);
        if ( is_wp_error($user) ) {
          $error = __('Invalid username or password.','gffm');
        } else {
          $vendor_id = (int) get_user_meta($user->ID, '_gffm_vendor_id', true);
          if ( ! $vendor_id || ! get_post_meta($vendor_id, '_gffm_portal_enabled', true) ) {
            wp_logout();
            $error = __('Your account is not linked to a vendor or portal access is disabled.','gffm');
          } else {
            wp_safe_redirect(home_url('/vendor-portal/'));
            exit;
          }
        }
      }
      if ( $error ) {
        $out .= '<div class="gffm-notice gffm-notice-error" aria-live="polite">'.esc_html($error).'</div>';
      }
      if ( in_array('password', $methods, true) ) {
        $out .= '<form method="post" class="gffm-login-form"><fieldset>';
        $out .= '<p class="gffm-login-field"><label for="gffm_username">'.esc_html__('Username','gffm').'</label><input type="text" id="gffm_username" name="gffm_username" /></p>';
        $out .= '<p class="gffm-login-field gffm-password-field"><label for="gffm_password">'.esc_html__('Password','gffm').'</label><input type="password" id="gffm_password" name="gffm_password" /><button type="button" class="gffm-toggle-pass" aria-label="'.esc_attr__('Show password','gffm').'">&#128065;</button></p>';
        wp_nonce_field('gffm_login','gffm_login_nonce');
        $out .= '<p><button class="button button-primary">'.esc_html__('Sign In','gffm').'</button></p>';
        $out .= '</fieldset></form>';
      }
      if ( in_array('google', $methods, true) && get_option('gffm_auth_google_client_id') && get_option('gffm_auth_google_client_secret') ) {
        $state = wp_create_nonce('gffm_oauth_google');
        set_transient('gffm_oauth_state_'.$state, 1, HOUR_IN_SECONDS);
        $url = add_query_arg(['gffm_oauth'=>'google','state'=>$state], home_url('/vendor-portal/'));
        $out .= '<p><a class="button" href="'.esc_url($url).'">'.esc_html__('Sign in with Google','gffm').'</a></p>';
      }
      if ( in_array('facebook', $methods, true) && get_option('gffm_auth_facebook_app_id') && get_option('gffm_auth_facebook_app_secret') ) {
        $state = wp_create_nonce('gffm_oauth_facebook');
        set_transient('gffm_oauth_state_'.$state, 1, HOUR_IN_SECONDS);
        $url = add_query_arg(['gffm_oauth'=>'facebook','state'=>$state], home_url('/vendor-portal/'));
        $out .= '<p><a class="button" href="'.esc_url($url).'">'.esc_html__('Sign in with Facebook','gffm').'</a></p>';
      }
      if ( in_array('magic', $methods, true) ) {
        $out .= '<p>'.esc_html__('Please check your email for a magic link to access the vendor portal.','gffm').'</p>';
      }
      $out .= '</div>';
      return $out;
    }
    $vendor_id = GFFM_Util::current_user_vendor_id();
    if ( ! $vendor_id || ! get_post_meta($vendor_id, '_gffm_portal_enabled', true) ) {
      return '<p>'.esc_html__('Your account is not linked to a vendor or portal access is disabled.','gffm').'</p>';
    }
    if ( ! GFFM_Util::can_edit_vendor($vendor_id) ) {
      return '<p>'.esc_html__('You do not have permission to access this vendor.','gffm').'</p>';
    }
    wp_enqueue_style('gffm-portal', GFFM_URL.'assets/portal.css', [], GFFM_VERSION);
    wp_enqueue_script('gffm-portal', GFFM_URL.'assets/portal.js', ['jquery'], GFFM_VERSION, true);
    wp_localize_script('gffm-portal', 'gffmPortal', [
      'ajaxurl' => admin_url('admin-ajax.php'),
      'i18n' => [
        'show' => __('Show password','gffm'),
        'hide' => __('Hide password','gffm'),
        'select' => __('Select Image','gffm'),
      ],
    ]);
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
      $out .= '<div class="gffm-notice gffm-notice-info" aria-live="polite">'.esc_html($notice).'</div>';
    }

    $map = json_decode(get_option('gffm_profile_map_json', self::default_mapping()), true);
    if ( ! is_array($map) ) $map = [];

    $out .= '<div class="gffm-portal-tabs">';
    $out .= '<ul class="gffm-tab-nav" role="tablist"><li id="gffm-tab-profile-label" class="active" role="tab" tabindex="0" aria-controls="gffm-tab-profile" aria-selected="true" data-tab="profile">'.esc_html__('Profile','gffm').'</li><li id="gffm-tab-highlight-label" role="tab" tabindex="0" aria-controls="gffm-tab-highlight" aria-selected="false" data-tab="highlight">'.esc_html__('Weekly Highlight','gffm').'</li></ul>';
    $out .= '<div class="gffm-tab-content active" id="gffm-tab-profile" role="tabpanel" aria-labelledby="gffm-tab-profile-label">';
    $out .= '<form method="post" id="gffm-profile-form">';
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

    $out .= '<div class="gffm-tab-content" id="gffm-tab-highlight" role="tabpanel" aria-labelledby="gffm-tab-highlight-label" hidden>';
    $out .= '<form method="post" id="gffm-highlight-form">';
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

  public static function ajax_profile_save() {
    check_ajax_referer('gffm_profile_save', 'nonce');
    $vendor_id = GFFM_Util::current_user_vendor_id();
    if ( ! $vendor_id ) {
      wp_send_json_error(['message'=>__('Vendor not found.','gffm')]);
    }
    $msg = self::handle_profile_save($vendor_id);
    wp_send_json_success(['message'=>$msg]);
  }

  public static function ajax_highlight_save() {
    check_ajax_referer('gffm_highlight_save', 'nonce');
    $vendor_id = GFFM_Util::current_user_vendor_id();
    if ( ! $vendor_id ) {
      wp_send_json_error(['message'=>__('Vendor not found.','gffm')]);
    }
    $msg = self::handle_highlight_save($vendor_id);
    wp_send_json_success(['message'=>$msg]);
  }
}
GFFM_Portal::init();
}

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
      'sanitize_callback' => function($v){
        $prev = get_option('gffm_profile_map_json');
        $arr  = json_decode(wp_unslash($v), true);
        if ( json_last_error() === JSON_ERROR_NONE && is_array($arr) ) {
          return wp_json_encode($arr, JSON_PRETTY_PRINT);
        }
        add_action('admin_notices', function(){
          echo '<div class="notice notice-error"><p>'.esc_html__('Invalid JSON; previous mapping restored.','gffm').'</p></div>';
        });
        return $prev ?: self::default_mapping();
      },
      'default' => self::default_mapping(),
    ]);
    register_setting('gffm_vendor_portal', 'gffm_append_vendor_role', [
      'type' => 'string',
      'sanitize_callback' => function($v){ return $v === 'yes' ? 'yes' : 'no'; },
      'default' => 'no',
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
    register_setting('gffm_vendor_portal', 'gffm_auth_login_branding', [
      'type'=>'string',
      'sanitize_callback'=>'sanitize_text_field',
      'default'=>'',
    ]);
  }

  public static function render_settings() {
    if ( ! current_user_can('gffm_manage') ) wp_die(__('You do not have permission.','gffm'));
    echo '<div class="wrap"><h1>'.esc_html__('Vendor Portal','gffm').'</h1>';
    echo '<p class="description">'.esc_html__('In Breakdance/Elementor, use a Shortcode element and paste [gffm_portal]. If you see raw text, make sure shortcode execution is enabled for that element.','gffm').'</p>';
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
    echo '<td><textarea name="gffm_profile_map_json" id="gffm_profile_map_json" rows="10" cols="50" class="large-text code">'.esc_textarea(get_option('gffm_profile_map_json', self::default_mapping())).'</textarea> <span id="gffm-json-valid" aria-live="polite"></span></td></tr>';
    echo '<tr><th><label for="gffm_invite_subject">'.esc_html__('Invite Email Subject','gffm').'</label></th>';
    echo '<td><input type="text" name="gffm_invite_subject" id="gffm_invite_subject" class="regular-text" value="'.esc_attr(get_option('gffm_invite_subject','Your Vendor Portal Link – {site_name}')).'"/></td></tr>';
    echo '<tr><th><label for="gffm_invite_body">'.esc_html__('Invite Email Body','gffm').'</label></th>';
    echo '<td><textarea name="gffm_invite_body" id="gffm_invite_body" rows="6" cols="50" class="large-text code">'.esc_textarea(get_option('gffm_invite_body',"Hello {vendor_title},\n\nYour one-click sign-in link:\n{portal_url}\n\nThis link will expire in 24 hours.\n– {site_name}")).'</textarea><p class="description">'.esc_html__('Placeholders: {site_name}, {vendor_title}, {portal_url}','gffm').'</p></td></tr>';
    $redir = esc_url(home_url('/vendor-portal/'));
    echo '<tr><th>'.esc_html__('Redirect URI','gffm').'</th><td><input type="text" readonly class="regular-text" value="'.$redir.'"/> <button type="button" class="button gffm-copy-redirect" data-copy="'.$redir.'">'.esc_html__('Copy','gffm').'</button> <span class="gffm-copy-feedback" style="display:none;">'.esc_html__('Copied!','gffm').'</span></td></tr>';
    echo '<tr><th><label for="gffm_auth_login_branding">'.esc_html__('Login Branding','gffm').'</label></th><td><input type="text" id="gffm_auth_login_branding" name="gffm_auth_login_branding" class="regular-text" value="'.esc_attr(get_option('gffm_auth_login_branding','')).'"/><p class="description">'.esc_html__('Some hosts may display a "Weak Password" page; resetting the password may be required.','gffm').'</p></td></tr>';
    $append = get_option('gffm_append_vendor_role','no');
    echo '<tr><th><label for="gffm_append_vendor_role">'.esc_html__('Append Vendor Role','gffm').'</label></th><td><input type="checkbox" id="gffm_append_vendor_role" name="gffm_append_vendor_role" value="yes" '.checked($append,'yes',false).'/> '.esc_html__('Add gffm_vendor role in addition to existing roles when inviting users.','gffm').'</td></tr>';
    echo '</table>';
    submit_button();
    echo '</form></div>';
  }

  public static function shortcode($atts, $content = '') {
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
    if ( is_user_logged_in() ) {
      wp_enqueue_media();
    }
    if ( ! is_user_logged_in() ) {
      $out = '<div class="gffm-login-container">';
      $branding = get_option('gffm_auth_login_branding', '');
      if ( $branding ) {
        $out .= '<h2>'.esc_html($branding).'</h2>';
      }
      $error = '';
      if ( isset($_POST['gffm_login_nonce']) && wp_verify_nonce($_POST['gffm_login_nonce'], 'gffm_login') ) {
        $enforce = get_option('gffm_enforce_email_login','yes');
        $email = isset($_POST['gffm_email']) ? sanitize_email(wp_unslash($_POST['gffm_email'])) : '';
        $pass  = isset($_POST['gffm_password']) ? (string) $_POST['gffm_password'] : '';
        if ( 'yes' === $enforce && ! is_email($email) ) {
          $error = __('Please enter a valid email address.','gffm');
        } else {
          $u = get_user_by('email', $email);
          if ( ! $u ) {
            if ( 'yes' !== $enforce && ! is_email($email) ) {
              $error = __('Please enter a valid email address.','gffm');
            } else {
              $error = __('We couldn’t find an account with that email.','gffm');
            }
          } else {
            $creds = [
              'user_login'    => $u->user_login,
              'user_password' => $pass,
              'remember'      => true,
            ];
            $user = wp_signon($creds, false);
            if ( is_wp_error($user) ) {
              $msg = $user->get_error_message();
              $error = $msg ? esc_html($msg) : __('Invalid email or password.','gffm');
            } else {
              $vendor_id = (int) get_user_meta($user->ID, '_gffm_vendor_id', true);
              $enabled   = $vendor_id ? get_post_meta($vendor_id, '_gffm_portal_enabled', true) : '';
              if ( ! $vendor_id || $enabled !== '1' ) {
                wp_logout();
                $error = __('Your account is not linked to a vendor or portal access is disabled.','gffm');
              } else {
                wp_safe_redirect(home_url('/vendor-portal/'));
                exit;
              }
            }
          }
        }
      }
      if ( $error ) {
        $out .= '<div class="gffm-notice gffm-notice-error" aria-live="polite">'.esc_html($error).'</div>';
      }
      $out .= '<form method="post" class="gffm-login-form"><fieldset>';
      $out .= '<p class="gffm-login-field"><label for="gffm_email">'.esc_html__('Email','gffm').'</label><input type="email" id="gffm_email" name="gffm_email" required /></p>';
      $out .= '<p class="gffm-login-field gffm-password-field"><label for="gffm_password">'.esc_html__('Password','gffm').'</label><input type="password" id="gffm_password" name="gffm_password" /><button type="button" class="gffm-toggle-pass" aria-label="'.esc_attr__('Show password','gffm').'">&#128065;</button></p>';
      wp_nonce_field('gffm_login','gffm_login_nonce');
      $out .= '<p><button type="submit" class="button button-primary">'.esc_html__('Sign In','gffm').'</button></p>';
      $out .= '<p class="gffm-login-note">'.esc_html__('If your host shows a “Weak Password” page, reset your password and sign in again.','gffm').'</p>';
      $out .= '</fieldset></form>';
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

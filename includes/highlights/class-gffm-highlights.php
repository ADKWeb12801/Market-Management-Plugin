<?php
defined('ABSPATH') || exit;

class GFFM_Highlights {
  public static function init() {
    add_action('init', [__CLASS__, 'register']);
    add_action('save_post_gffm_highlight', [__CLASS__, 'save_week_key'], 10, 3);
    add_shortcode('gffm_this_week', [__CLASS__, 'shortcode']);
  }

  public static function register() {
    register_post_type('gffm_highlight', [
      'label' => __('Weekly Highlights','gffm'),
      'public' => true,
      'show_in_rest' => true,
      'supports' => ['title','editor','thumbnail','author'],
    ]);
  }

  public static function save_week_key($post_id, $post, $update) {
    if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) return;
    $week = GFFM_Util::week_key($post->post_date);
    update_post_meta($post_id, '_gffm_week_key', $week);
  }

  public static function shortcode($atts) {
    $atts = shortcode_atts(['date' => ''], $atts, 'gffm_this_week');
    $date = $atts['date'] ? sanitize_text_field($atts['date']) : null;
    $week_key = GFFM_Util::week_key($date);
    $posts = get_posts([
      'post_type' => 'gffm_highlight',
      'post_status' => 'publish',
      'meta_key' => '_gffm_week_key',
      'meta_value' => $week_key,
      'posts_per_page' => -1,
    ]);
    if ( ! $posts ) {
      return '<p>'.esc_html__('No highlights this week.','gffm').'</p>';
    }
    $out = '<div class="gffm-highlight-grid">';
    foreach ( $posts as $p ) {
      $out .= '<div class="gffm-highlight-card">';
      if ( has_post_thumbnail($p) ) {
        $out .= get_the_post_thumbnail($p, 'medium');
      }
      $out .= '<h3>'.esc_html(get_the_title($p)).'</h3>';
      $out .= '<div class="gffm-highlight-content">'.wp_kses_post(wpautop($p->post_content)).'</div>';
      $out .= '</div>';
    }
    $out .= '</div>';
    return $out;
  }
}
GFFM_Highlights::init();

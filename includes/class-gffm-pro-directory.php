<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GFFM_Pro_Directory {
    public static function init() {
        add_shortcode( 'gffm_directory', array( __CLASS__, 'shortcode' ) );
    }
    public static function shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'date' => wp_date('Y-m-d'),
            'limit' => 20,
        ), $atts, 'gffm_directory' );
        $date = esc_html( $atts['date'] );
        $limit = max(1, intval($atts['limit']));
        $vendors = get_posts(array(
            'post_type' => 'vendor',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        $out = '<div class="gffm-pro-directory"><h3>Vendor Directory for '. $date .'</h3>';
        if ( $vendors ) {
            $out .= '<ul>';
            foreach( $vendors as $vid ) { $out .= '<li>'. esc_html( get_the_title($vid) ) .'</li>'; }
            $out .= '</ul>';
        } else {
            $out .= '<p>No vendors found.</p>';
        }
        $out .= '</div>';
        return $out;
    }
}

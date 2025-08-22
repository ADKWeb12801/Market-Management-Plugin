<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GFFM_Pro_Map {
    public static function init() {
        add_shortcode( 'gffm_booth_map', array( __CLASS__, 'shortcode' ) );
    }
    public static function shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'date' => wp_date('Y-m-d'),
        ), $atts, 'gffm_booth_map' );
        $date = esc_html( $atts['date'] );
        return '<div class="gffm-pro-map"><h3>Booth Map for '. $date .'</h3><div style="padding:10px;border:1px dashed #999;background:#fafafa">Map placeholder (Pro)</div></div>';
    }
}
